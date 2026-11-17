<?php

namespace App\Models;

use App\Enums\InterviewType;
use Carbon\CarbonImmutable;
use Database\Factories\InterviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $job_application_id
 * @property CarbonImmutable $scheduled_at
 * @property InterviewType $type
 * @property string|null $location
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read JobApplication $jobApplication
 * @property-read Reminder|null $reminder
 */
#[Fillable(['scheduled_at', 'type', 'location', 'notes'])]
class Interview extends Model
{
    /** @use HasFactory<InterviewFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'type' => InterviewType::class,
        ];
    }

    /** @return BelongsTo<JobApplication, $this> */
    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    /** @return HasOne<Reminder, $this> */
    public function reminder(): HasOne
    {
        return $this->hasOne(Reminder::class);
    }
}
