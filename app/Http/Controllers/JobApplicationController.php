<?php

namespace App\Http\Controllers;

use App\Actions\MoveJobApplication;
use App\Enums\ApplicationStatus;
use App\Enums\InterviewType;
use App\Enums\WorkMode;
use App\Http\Requests\Applications\JobApplicationRequest;
use App\Http\Requests\Applications\MoveJobApplicationRequest;
use App\Http\Resources\JobApplicationResource;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class JobApplicationController extends Controller
{
    /**
     * The kanban board.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        // Read leniently: a bad value in the URL just means "no filter".
        $filters = [
            'search' => mb_substr(trim($request->string('search')->toString()), 0, 100),
            'work_mode' => WorkMode::tryFrom($request->string('work_mode')->toString()),
            'hide_rejected' => $request->boolean('hide_rejected'),
        ];

        $applications = $user->jobApplications()
            ->filter($filters['search'], $filters['work_mode'], $filters['hide_rejected'])
            ->withCount(['interviews', 'notes'])
            ->addSelect(['next_interview_at' => function ($query) {
                $query->selectRaw('min(scheduled_at)')
                    ->from('interviews')
                    ->whereColumn('interviews.job_application_id', 'job_applications.id')
                    ->where('scheduled_at', '>=', now());
            }])
            ->orderBy('position_in_column')
            ->orderBy('id')
            ->get();

        return Inertia::render('applications/Board', [
            'applications' => JobApplicationResource::collection($applications),
            'statuses' => ApplicationStatus::options(),
            'workModes' => WorkMode::options(),
            'filters' => [
                'search' => $filters['search'],
                'work_mode' => $filters['work_mode']?->value,
                'hide_rejected' => $filters['hide_rejected'],
            ],
            'total' => $user->jobApplications()->count(),
        ]);
    }

    public function create(Request $request): Response
    {
        $status = ApplicationStatus::tryFrom((string) $request->query('status')) ?? ApplicationStatus::Wishlist;

        return Inertia::render('applications/Create', [
            'defaultStatus' => $status->value,
            ...$this->formOptions(),
        ]);
    }

    public function store(JobApplicationRequest $request, MoveJobApplication $mover): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $status = ApplicationStatus::from($request->string('status')->toString());

        $application = $user->jobApplications()->create([
            ...$request->validated(),
            'applied_at' => $request->validated('applied_at')
                ?? ($status === ApplicationStatus::Wishlist ? null : now()->toDateString()),
            'position_in_column' => $mover->nextPosition($user->id, $status),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application added.')]);

        return to_route('applications.show', $application);
    }

    public function show(JobApplication $application): Response
    {
        Gate::authorize('manage', $application);

        $application->load(['interviews.reminder', 'notes', 'reminders']);

        return Inertia::render('applications/Show', [
            'application' => new JobApplicationResource($application),
            ...$this->formOptions(),
        ]);
    }

    public function edit(JobApplication $application): Response
    {
        Gate::authorize('manage', $application);

        return Inertia::render('applications/Edit', [
            'application' => new JobApplicationResource($application),
            ...$this->formOptions(),
        ]);
    }

    public function update(JobApplicationRequest $request, JobApplication $application, MoveJobApplication $mover): RedirectResponse
    {
        Gate::authorize('manage', $application);

        $status = ApplicationStatus::from($request->string('status')->toString());
        $statusChanged = $status !== $application->status;

        $application->update($request->safe()->except('status'));

        if ($statusChanged) {
            // Changing column from the form drops the card at the bottom of the new column.
            $mover->handle($application, $status, PHP_INT_MAX);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application updated.')]);

        return to_route('applications.show', $application);
    }

    /**
     * Drag and drop on the board.
     */
    public function move(MoveJobApplicationRequest $request, JobApplication $application, MoveJobApplication $mover): RedirectResponse
    {
        Gate::authorize('manage', $application);

        $mover->handle(
            $application,
            ApplicationStatus::from($request->string('status')->toString()),
            $request->integer('position'),
        );

        return back();
    }

    public function destroy(JobApplication $application): RedirectResponse
    {
        Gate::authorize('manage', $application);

        $application->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application deleted.')]);

        return to_route('applications.index');
    }

    /**
     * @return array<string, list<array{value: string, label: string}>>
     */
    private function formOptions(): array
    {
        return [
            'statuses' => ApplicationStatus::options(),
            'workModes' => WorkMode::options(),
            'interviewTypes' => InterviewType::options(),
        ];
    }
}
