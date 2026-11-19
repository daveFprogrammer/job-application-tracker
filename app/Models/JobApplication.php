<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\WorkMode;
use Carbon\CarbonImmutable;
use Database\Factories\JobApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $company
 * @property string $position
 * @property string|null $job_url
 * @property string|null $location
 * @property WorkMode|null $work_mode
 * @property string|null $salary_range
 * @property ApplicationStatus $status
 * @property int $position_in_column
 * @property CarbonImmutable|null $applied_at
 * @property string|null $description
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 */
#[Fillable([
    'company', 'position', 'job_url', 'location', 'work_mode',
    'salary_range', 'status', 'position_in_column', 'applied_at', 'description',
])]
class JobApplication extends Model
{
    /** @use HasFactory<JobApplicationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'work_mode' => WorkMode::class,
            'applied_at' => 'date',
            'position_in_column' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Interview, $this> */
    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class)->orderBy('scheduled_at');
    }

    /** @return HasMany<Note, $this> */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class)->latest();
    }

    /** @return HasMany<Reminder, $this> */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class)->orderBy('remind_at');
    }

    /**
     * Board filters: free-text search on company, role and location (case
     * insensitive), work mode, and optionally hiding the rejected column.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function filter(Builder $query, ?string $search, ?WorkMode $workMode, bool $hideRejected): void
    {
        $search = trim((string) $search);

        if ($search !== '') {
            // Treat the user input literally: escape the LIKE wildcards.
            $pattern = '%'.addcslashes($search, '\%_').'%';

            $query->where(function (Builder $query) use ($pattern) {
                foreach (['company', 'position', 'location'] as $column) {
                    $query->orWhereRaw("lower({$column}) like lower(?)", [$pattern]);
                }
            });
        }

        if ($workMode !== null) {
            $query->where('work_mode', $workMode);
        }

        if ($hideRejected) {
            $query->where('status', '!=', ApplicationStatus::Rejected);
        }
    }
}
