<?php

namespace App\Http\Resources;

use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Reminder
 */
class ReminderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'remind_at' => $this->remind_at->toIso8601String(),
            'message' => $this->message,
            'interview_id' => $this->interview_id,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'application' => $this->whenLoaded('jobApplication', fn () => [
                'id' => $this->jobApplication->id,
                'company' => $this->jobApplication->company,
                'position' => $this->jobApplication->position,
            ]),
        ];
    }
}
