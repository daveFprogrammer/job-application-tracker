<?php

namespace App\Http\Resources;

use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JobApplication
 */
class JobApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company' => $this->company,
            'position' => $this->position,
            'job_url' => $this->job_url,
            'location' => $this->location,
            'work_mode' => $this->work_mode?->value,
            'salary_range' => $this->salary_range,
            'status' => $this->status->value,
            'applied_at' => $this->applied_at?->toDateString(),
            'description' => $this->description,
            'next_interview_at' => $this->whenHas('next_interview_at'),
            'interviews_count' => $this->whenCounted('interviews'),
            'notes_count' => $this->whenCounted('notes'),
            'interviews' => InterviewResource::collection($this->whenLoaded('interviews')),
            'notes' => NoteResource::collection($this->whenLoaded('notes')),
            'reminders' => ReminderResource::collection($this->whenLoaded('reminders')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
