<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\EmailReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class EmailActivityController
{
    /**
     * Display the email activity dashboard for the authenticated user.
     */
    public function index(Request $request): Response
    {
        $userId = Auth::id();
        $account = $request->query('account');

        $recentActivity = EmailReply::query()
            ->where('user_id', $userId)
            ->when($account, fn ($query) => $query->where('account', $account))
            ->whereIn('status', ['sent', 'failed'])
            ->orderBy('sent_at', 'desc')
            ->orderBy('failed_at', 'desc')
            ->limit(20)
            ->get(['id', 'email_id', 'account', 'status', 'recipient_email', 'subject', 'sent_at', 'failed_at', 'error_message']);

        $stats = [
            'total_sent' => EmailReply::where('user_id', $userId)
                ->when($account, fn ($query) => $query->where('account', $account))
                ->where('status', 'sent')
                ->count(),

            'sent_today' => EmailReply::where('user_id', $userId)
                ->when($account, fn ($query) => $query->where('account', $account))
                ->where('status', 'sent')
                ->whereDate('sent_at', today())
                ->count(),

            'sent_this_week' => EmailReply::where('user_id', $userId)
                ->when($account, fn ($query) => $query->where('account', $account))
                ->where('status', 'sent')
                ->whereBetween('sent_at', [now()->startOfWeek(), now()])
                ->count(),

            'failed_count' => EmailReply::where('user_id', $userId)
                ->when($account, fn ($query) => $query->where('account', $account))
                ->where('status', 'failed')
                ->count(),
        ];

        $accountStats = EmailReply::query()
            ->where('user_id', $userId)
            ->whereIn('status', ['sent', 'failed'])
            ->selectRaw('account, status, COUNT(*) as count')
            ->groupBy('account', 'status')
            ->get()
            ->groupBy('account')
            ->map(function ($accountGroup) {
                return [
                    'sent' => $accountGroup->where('status', 'sent')->sum('count'),
                    'failed' => $accountGroup->where('status', 'failed')->sum('count'),
                ];
            });

        return Inertia::render('EmailActivity/Index', [
            'recentActivity' => $recentActivity,
            'stats' => $stats,
            'accountStats' => $accountStats,
            'currentAccount' => $account,
        ]);
    }

    /**
     * Get email activity data for API calls (for real-time updates).
     */
    public function apiActivity(Request $request)
    {
        $userId = Auth::id();
        $account = $request->query('account');

        $activity = EmailReply::query()
            ->where('user_id', $userId)
            ->when($account, fn ($query) => $query->where('account', $account))
            ->whereIn('status', ['sent', 'failed', 'sending'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get(['id', 'email_id', 'account', 'status', 'recipient_email', 'subject', 'sent_at', 'failed_at', 'error_message', 'updated_at']);

        return response()->json($activity);
    }
}
