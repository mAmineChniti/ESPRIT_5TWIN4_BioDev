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
     * The role that signs for this stage of the chain.
     *
     * A product is registered as "Produced" by the producer who grows it, a
     * processor signs for "Processed", and a distributor signs for
     * "Distributed". Keeping this on the enum is what stops one role from
     * recording another's hand-off and corrupting the audit trail.
     */
    public function requiredRole(): string
    {
        return match ($this) {
            self::Produced => 'producer',
            self::Processed => 'processor',
            self::Distributed => 'distributor',
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

    /**
     * How many stages a complete chain contains.
     */
    public static function total(): int
    {
        return count(self::cases());
    }

    /**
     * The stage that must follow the given one. A null stage means the chain
     * has not started yet, so the first stage is required. Returns null once
     * the last stage is reached.
     *
     * Anything other than this exact stage is a skip or a reversal, and both
     * mean a stage was invented.
     */
    public static function next(?self $stage): ?self
    {
        if ($stage === null) {
            return self::cases()[0];
        }

        $index = array_search($stage->value, self::order(), true);

        return $index === false ? null : (self::cases()[$index + 1] ?? null);
    }

    /**
     * Whether the given stage has already been recorded on a chain.
     *
     * @param  list<string>  $recorded
     */
    public static function isComplete(array $recorded): bool
    {
        return count(array_unique($recorded)) >= self::total();
    }
}
