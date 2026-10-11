<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Deal;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardApiController extends BaseApiController
{
    /**
     * Admin dashboard summary analytics.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $totalRevenue = (float) Payment::where('status', 'completed')->sum('amount');
        $activeMembers = User::where('account_status', '!=', 'free')->count();
        $totalMembers = User::count();
        $completedPayments = Payment::where('status', 'completed')->count();
        $totalDeals = Deal::count();
        $activeDeals = Deal::where('type', '!=', 'portfolio')->where('status', 'active')->count();

        // Signups over time (last 30 days)
        $memberCounts = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Subscriptions count by date
        $subscriptionCounts = Subscription::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Plan distribution
        $planDistribution = Subscription::selectRaw('plan, COUNT(*) as count')
            ->groupBy('plan')
            ->get();

        // Payment method distribution
        $paymentMethods = Payment::where('status', 'completed')
            ->selectRaw('payment_method, COUNT(*) as count')
            ->groupBy('payment_method')
            ->get();

        return $this->success([
            'metrics' => [
                'total_revenue' => $totalRevenue,
                'active_members' => $activeMembers,
                'total_members' => $totalMembers,
                'completed_payments' => $completedPayments,
                'total_deals' => $totalDeals,
                'active_deals' => $activeDeals,
            ],
            'charts' => [
                'member_signups' => $memberCounts,
                'subscriptions' => $subscriptionCounts,
                'plan_distribution' => $planDistribution,
                'payment_methods' => $paymentMethods,
            ],
        ]);
    }
}
