<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case Preparing = 'preparing';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Preparing => __('Preparing'),
            self::InTransit => __('In transit'),
            self::Delivered => __('Delivered'),
            self::Cancelled => __('Cancelled'),
        };
    }
}
