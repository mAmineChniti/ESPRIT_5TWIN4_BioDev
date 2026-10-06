<?php

namespace App\Enums;

enum Stage: string
{
    case Produced = 'produced';
    case Processed = 'processed';
    case Distributed = 'distributed';

    public function label(): string
    {
        return match ($this) {
            self::Produced => 'Produced',
            self::Processed => 'Processed',
            self::Distributed => 'Distributed',
        };
    }

    /**
     * The supply chain order, used to detect skipped or backwards steps.
     *
     * @return list<string>
     */
    public static function order(): array
    {
        return array_column(self::cases(), 'value');
    }
}
