<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JourneyStep extends Model
{
    use HasFactory;

    protected $table = 'journey_steps';

    /**
     * The canonical workflow, in the order a product moves through it.
     *
     * Written once here: the validation rules, the step form and the workflow
     * tracker all read this list, so adding a stage cannot leave a stale copy
     * behind.
     *
     * @var list<string>
     */
    public const FLOW = ['origin', 'transport', 'storage', 'sale'];

    protected $fillable = [
        'journey_id',
        'step_order',
        'type',
        'location',
        'step_date',
        'description',
        'farm_id',
        'shipment_id',
    ];

    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
            'step_date' => 'date',
        ];
    }

    public function journey(): BelongsTo
    {
        return $this->belongsTo(Journey::class);
    }

    /**
     * Every workflow type with its display label.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'origin' => 'Origin',
            'transport' => 'Transport',
            'storage' => 'Storage',
            'sale' => 'Sale',
        ];
    }

    /**
     * The display label for this step's type.
     */
    public function label(): string
    {
        return self::labels()[$this->type] ?? ucfirst((string) $this->type);
    }

    /**
     * The first workflow stage with nothing recorded yet.
     *
     * When every stage is recorded the journey is complete and the final
     * stage is suggested again, so the call to action stays valid.
     *
     * @param  iterable<string>  $recordedTypes
     */
    public static function suggestedType(iterable $recordedTypes): string
    {
        $recorded = [];

        foreach ($recordedTypes as $type) {
            $recorded[] = strtolower((string) $type);
        }

        foreach (self::FLOW as $type) {
            if (! in_array($type, $recorded, true)) {
                return $type;
            }
        }

        return end(self::FLOW);
    }
}
