<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\MailController;
use App\Models\CampaignSendLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMailApiController extends BaseApiController
{
    /**
     * List campaign logs and reachable recipients summary.
     */
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $accountStatus = $request->query('account_status');
        $paymentStatus = $request->query('payment_status');

        $users = User::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                });
            })
            ->when($accountStatus, fn ($q) => $q->where('account_status', $accountStatus))
            ->when($paymentStatus, fn ($q) => $q->where('payment_status', $paymentStatus))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'account_status', 'payment_status', 'is_approved']);

        $campaignLogs = CampaignSendLog::with('sender:id,name,email')
            ->latest('sent_at')
            ->latest()
            ->paginate((int) $request->query('per_page', 20));

        return $this->success([
            'recipients_count' => $users->count(),
            'recipients' => $users,
            'campaign_logs' => $campaignLogs,
        ]);
    }

    /**
     * Send email campaign broadcast (forwards to MailController).
     */
    public function send(Request $request): JsonResponse
    {
        $mailController = app(MailController::class);

        // Run send via existing MailController logic
        $response = $mailController->send($request);

        return $this->success(null, 'Campaign broadcast processed.');
    }

    /**
     * Delete a single campaign send log.
     */
    public function destroyLog(CampaignSendLog $campaignSendLog): JsonResponse
    {
        $campaignSendLog->delete();

        return $this->success(null, 'Log deleted.');
    }

    /**
     * Delete all campaign send logs (superadmin only).
     */
    public function destroyAllLogs(Request $request): JsonResponse
    {
        abort_unless($request->user()->role === 'superadmin', 403, 'Superadmin access required to clear logs.');

        CampaignSendLog::truncate();

        return $this->success(null, 'All logs cleared.');
    }
}
