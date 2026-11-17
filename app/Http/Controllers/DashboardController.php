<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Http\Resources\InterviewResource;
use App\Http\Resources\ReminderResource;
use App\Models\Interview;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array<string, int> $counts */
        $counts = $user->jobApplications()
            ->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->all();

        $ownedBy = fn ($query) => $query->where('user_id', $user->id);

        $upcomingInterviews = Interview::query()
            ->whereHas('jobApplication', $ownedBy)
            ->with('jobApplication')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        $pendingReminders = Reminder::query()
            ->whereHas('jobApplication', $ownedBy)
            ->with('jobApplication')
            ->pending()
            ->orderBy('remind_at')
            ->limit(5)
            ->get();

        $total = array_sum($counts);
        $responded = ($counts[ApplicationStatus::Interviewing->value] ?? 0)
            + ($counts[ApplicationStatus::Offer->value] ?? 0);
        $sent = $total - ($counts[ApplicationStatus::Wishlist->value] ?? 0);

        return Inertia::render('Dashboard', [
            'stats' => [
                'total' => $total,
                'byStatus' => array_map(fn (array $status) => [
                    ...$status,
                    'count' => $counts[$status['value']] ?? 0,
                ], ApplicationStatus::options()),
                'responseRate' => $sent > 0 ? (int) round($responded / $sent * 100) : null,
            ],
            'upcomingInterviews' => InterviewResource::collection($upcomingInterviews),
            'pendingReminders' => ReminderResource::collection($pendingReminders),
        ]);
    }
}
