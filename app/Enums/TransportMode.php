<?php

namespace App\Enums;

enum TransportMode: string
{
    case Truck = 'truck';
    case ElectricTruck = 'electric_truck';
    case Train = 'train';
    case Ship = 'ship';
    case Plane = 'plane';

    public function label(): string
    {
        return match ($this) {
            self::Truck => __('Truck (diesel)'),
            self::ElectricTruck => __('Electric truck'),
            self::Train => __('Freight train'),
            self::Ship => __('Cargo ship'),
            self::Plane => __('Air cargo'),
        };
    }

    /**
     * Indicative emission factor in kg CO2e per tonne-kilometre.
     * Educational values: cite your source in the report.
     */
    public function emissionFactor(): float
    {
        return match ($this) {
            self::Truck => 0.100,
            self::ElectricTruck => 0.030,
            self::Train => 0.025,
            self::Ship => 0.015,
            self::Plane => 0.600,
        };
    }
}
