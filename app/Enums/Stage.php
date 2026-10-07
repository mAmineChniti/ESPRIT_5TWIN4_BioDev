<?php

namespace App\Enums;

/**
 * The supply chain stages, in the order a product passes through them.
 */
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
     * The position of this stage in the chain, starting at zero.
     */
    public function position(): int
    {
        return (int) array_search($this->value, self::order(), true);
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
