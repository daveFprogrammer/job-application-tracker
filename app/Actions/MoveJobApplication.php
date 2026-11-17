<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use Illuminate\Support\Facades\DB;

/**
 * Moves a card on the kanban board: changes its column (status) and
 * re-numbers the positions of the affected columns so they stay contiguous.
 */
class MoveJobApplication
{
    public function handle(JobApplication $application, ApplicationStatus $status, int $position): void
    {
        DB::transaction(function () use ($application, $status, $position) {
            $previousStatus = $application->status;

            $ids = $this->columnIds($application->user_id, $status, except: $application->id);
            array_splice($ids, min($position, count($ids)), 0, [$application->id]);

            $application->status = $status;

            if ($status === ApplicationStatus::Applied && $application->applied_at === null) {
                $application->applied_at = now();
            }

            $application->save();

            $this->renumber($ids);

            if ($previousStatus !== $status) {
                $this->renumber($this->columnIds($application->user_id, $previousStatus));
            }
        });
    }

    /**
     * Next free position at the bottom of a column.
     */
    public function nextPosition(int $userId, ApplicationStatus $status): int
    {
        $max = JobApplication::query()
            ->where('user_id', $userId)
            ->where('status', $status)
            ->max('position_in_column');

        return $max === null ? 0 : ((int) $max) + 1;
    }

    /**
     * @return list<int>
     */
    private function columnIds(int $userId, ApplicationStatus $status, ?int $except = null): array
    {
        /** @var list<int> */
        return JobApplication::query()
            ->where('user_id', $userId)
            ->where('status', $status)
            ->when($except, fn ($query) => $query->whereKeyNot($except))
            ->orderBy('position_in_column')
            ->orderBy('id')
            ->lockForUpdate()
            ->pluck('id')
            ->all();
    }

    /**
     * @param  list<int>  $ids
     */
    private function renumber(array $ids): void
    {
        foreach ($ids as $index => $id) {
            JobApplication::query()
                ->whereKey($id)
                ->where('position_in_column', '!=', $index)
                ->update(['position_in_column' => $index]);
        }
    }
}
