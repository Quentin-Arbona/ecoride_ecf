<?php

namespace App\Security\Voter;

use App\Entity\Ride;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RideVoter extends Voter
{
    public const DRIVE = 'DRIVE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::DRIVE && $subject instanceof Ride;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return $subject->getDriver() === $user;
    }
}
