<?php

namespace App\Services;

use App\Models\MembershipOrder;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionTier;
use App\Models\User;
use App\Support\MembershipPolicies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MembershipEvidence
{
    public function begin(Request $request, User $user, Subscription $subscription, SubscriptionTier $tier, string $transaction, string $currency): MembershipOrder
    {
        $policies = MembershipPolicies::checkoutPages();
        $plan = [
            'slug' => $tier->slug, 'name' => $tier->name, 'price_yearly' => $tier->price_yearly,
            'features_included' => $tier->includedFeatureLines(),
            'features_excluded' => $tier->excludedFeatureLines(), 'term' => 'Annual',
        ];

        // Retain the purchased plan, not a live reference to prices an admin can change later.
        foreach ($policies as &$page) {
            foreach ($page['blocks'] as &$block) {
                if ($block['type'] === 'plans') {
                    $block = ['type' => 'purchased_plan', 'plan' => $plan];
                }
            }
            unset($block);
        }
        unset($page);

        return MembershipOrder::create([
            'reference' => 'BAN-'.Str::ulid(), 'user_id' => $user->id,
            'subscription_id' => $subscription->id, 'merchant_txnid' => $transaction,
            'plan_slug' => $tier->slug, 'amount' => $tier->price_yearly, 'currency' => $currency,
            'customer_snapshot' => $user->only(['name', 'email', 'phone', 'address', 'primary_country', 'company_name']),
            'plan_snapshot' => $plan, 'business_snapshot' => config('business'),
            'policy_snapshot' => $policies, 'policy_version' => MembershipPolicies::version(),
            'acknowledgement' => MembershipPolicies::ACKNOWLEDGEMENT,
            'accepted_at' => now(), 'client_accepted_at' => $request->input('policy_accepted_at'),
            'accepted_ip' => $request->ip(), 'accepted_user_agent' => mb_substr($request->userAgent() ?? '', 0, 2000),
        ]);
    }

    public function sendReceipt(Payment $payment): void
    {
        $order = MembershipOrder::where('payment_id', $payment->id)->first();
        if (! $order || ! $order->delivered_at) {
            return;
        }

        $record = $order->records()->firstOrCreate(
            ['deduplication_key' => $order->reference.':receipt'],
            [
                'kind' => 'confirmation_email', 'subject' => 'BAN membership receipt '.$order->reference,
                'recipient' => $order->customer_snapshot['email'], 'status' => 'pending',
                'body' => view('emails.membership-receipt', compact('order', 'payment'))->render(),
            ]
        );
        if (! $record->wasRecentlyCreated) {
            return;
        }

        try {
            Mail::html($record->body, function ($message) use ($record) {
                $message->to($record->recipient)->subject($record->subject);
            });
            $record->update(['status' => 'sent']);
        } catch (\Throwable $exception) {
            $record->update(['status' => 'failed']);
            Log::warning('Membership receipt email failed', ['order' => $order->reference]);
        }
    }
}
