<?php

namespace App\Enums;

enum InterviewType: string
{
    case Phone = 'phone';
    case Video = 'video';
    case Onsite = 'onsite';
    case Technical = 'technical';

    public function label(): string
    {
        return match ($this) {
            self::Phone => 'Phone screen',
            self::Video => 'Video call',
            self::Onsite => 'On-site',
            self::Technical => 'Technical',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => ['value' => $type->value, 'label' => $type->label()],
            self::cases(),
        );
    }
}
