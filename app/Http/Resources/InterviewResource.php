<?php

namespace App\Http\Resources;

use App\Models\Interview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Interview
 */
class InterviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'scheduled_at' => $this->scheduled_at->toIso8601String(),
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'location' => $this->location,
            'notes' => $this->notes,
            'has_reminder' => $this->whenLoaded('reminder', fn () => $this->reminder !== null),
            'application' => $this->whenLoaded('jobApplication', fn () => [
                'id' => $this->jobApplication->id,
                'company' => $this->jobApplication->company,
                'position' => $this->jobApplication->position,
            ]),
        ];
    }
}
