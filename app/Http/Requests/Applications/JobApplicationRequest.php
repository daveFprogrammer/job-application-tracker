<?php

namespace App\Http\Requests\Applications;

use App\Enums\ApplicationStatus;
use App\Enums\WorkMode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobApplicationRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'job_url' => ['nullable', 'url', 'max:2048'],
            'location' => ['nullable', 'string', 'max:255'],
            'work_mode' => ['nullable', Rule::enum(WorkMode::class)],
            'salary_range' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(ApplicationStatus::class)],
            'applied_at' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
