<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ReminderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $job_application_id
 * @property int|null $interview_id
 * @property CarbonImmutable $remind_at
 * @property string $message
 * @property CarbonImmutable|null $queued_at
 * @property CarbonImmutable|null $sent_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read JobApplication $jobApplication
 * @property-read Interview|null $interview
 */
#[Fillable(['remind_at', 'message', 'interview_id'])]
class Reminder extends Model
{
    /** @use HasFactory<ReminderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'remind_at' => 'datetime',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<JobApplication, $this> */
    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    /** @return BelongsTo<Interview, $this> */
    public function interview(): BelongsTo
    {
        return $this->belongsTo(Interview::class);
    }

    /**
     * Reminders whose time has come and that were not handed to the queue yet.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function due(Builder $query): void
    {
        $query->where('remind_at', '<=', now())->whereNull('queued_at');
    }

    /**
     * Reminders still waiting to be sent.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->whereNull('sent_at');
    }
}
