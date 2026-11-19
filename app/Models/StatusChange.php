<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One step of an application's journey through the pipeline. The first
 * entry of every application has no `from_status` (the card was created).
 *
 * @property int $id
 * @property int $job_application_id
 * @property ApplicationStatus|null $from_status
 * @property ApplicationStatus $to_status
 * @property CarbonImmutable $changed_at
 * @property-read JobApplication $jobApplication
 */
#[Fillable(['from_status', 'to_status', 'changed_at'])]
class StatusChange extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'from_status' => ApplicationStatus::class,
            'to_status' => ApplicationStatus::class,
            'changed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<JobApplication, $this> */
    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }
}
