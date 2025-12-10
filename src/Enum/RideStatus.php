<?php

namespace App\Enum;

enum RideStatus: string
{
    case PENDING = 'pending';        // En attente de départ
    case ACTIVE = 'active';          // En cours
    case COMPLETED = 'completed';    // Terminé
    case CANCELLED = 'cancelled';    // Annulé
    
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::ACTIVE => 'En cours',
            self::COMPLETED => 'Terminé',
            self::CANCELLED => 'Annulé',
        };
    }
    
    public function getBadgeClass(): string
    {
        return match($this) {
            self::PENDING => 'badge-warning',
            self::ACTIVE => 'badge-success',
            self::COMPLETED => 'badge-secondary',
            self::CANCELLED => 'badge-danger',
        };
    }
}