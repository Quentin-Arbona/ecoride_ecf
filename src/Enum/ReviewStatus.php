<?php 

namespace App\Enum;

enum ReviewStatus: string
{
    case PENDING = 'en_attente';
    case VALIDATED = 'valide';
    case REFUSED = 'refuse';
}
