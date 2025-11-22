<?php

namespace App\Enums;

enum ShipmentStatusEnum: string
{
    case PENDING = 'pending';
    case PREPARING = 'preparing';
    case READY_TO_SHIP = 'ready_to_ship';
    case PICKED_UP = 'picked_up';
    case IN_TRANSIT = 'in_transit';
    case CUSTOMS_CLEARANCE = 'customs_clearance';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case RETURNED = 'returned';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PREPARING => 'Preparing',
            self::READY_TO_SHIP => 'Ready to Ship',
            self::PICKED_UP => 'Picked Up',
            self::IN_TRANSIT => 'In Transit',
            self::CUSTOMS_CLEARANCE => 'Customs Clearance',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::DELIVERED => 'Delivered',
            self::CANCELLED => 'Cancelled',
            self::RETURNED => 'Returned',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING, self::PREPARING => 'gray',
            self::READY_TO_SHIP, self::PICKED_UP => 'info',
            self::IN_TRANSIT, self::CUSTOMS_CLEARANCE => 'warning',
            self::OUT_FOR_DELIVERY => 'primary',
            self::DELIVERED => 'success',
            self::CANCELLED, self::RETURNED => 'danger',
        };
    }
}