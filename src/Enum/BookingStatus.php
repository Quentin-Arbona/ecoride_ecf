<?php

namespace App\Enum;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';
    case DISPUTED = 'disputed';
    
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::CONFIRMED => 'Confirmée',
            self::CANCELLED => 'Annulée',
            self::COMPLETED => 'Terminée',
            self::DISPUTED => 'Contestée',
        };
    }
    
    public function getBadgeClass(): string
    {
        return match($this) {
            self::PENDING => 'badge-warning',
            self::CONFIRMED => 'badge-success',
            self::CANCELLED => 'badge-secondary',
            self::COMPLETED => 'badge-info',
            self::DISPUTED => 'badge-danger',
        };
    }
}