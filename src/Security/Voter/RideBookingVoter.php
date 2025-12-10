<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Entity\RideBooking;
use App\Enum\RideStatus;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RideBookingVoter extends Voter
{
    public const FEEDBACK = 'FEEDBACK';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::FEEDBACK && $subject instanceof RideBooking;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var RideBooking $booking */
        $booking = $subject;

        return match($attribute) {
            self::FEEDBACK => $this->canGiveFeedback($booking, $user),
            default => false,
        };
    }

    private function canGiveFeedback(RideBooking $booking, User $user): bool
    {
        // Doit être le passager de cette réservation
        if ($booking->getPassenger() !== $user) {
            return false;
        }

        // Le trajet doit être terminé
        if ($booking->getRide()->getStatus() !== RideStatus::COMPLETED) {
            return false;
        }

        // Ne peut pas donner un feedback deux fois
        if ($booking->getFeedbackAt() !== null) {
            return false;
        }

        return true;
    }
}
