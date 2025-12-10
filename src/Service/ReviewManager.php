<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Review;
use App\Enum\ReviewStatus;
use Doctrine\ORM\EntityManagerInterface;

class ReviewManager
{
    public function __construct(private EntityManagerInterface $em) {}

    public function validateReview(Review $review, User $validatedBy): void
    {
        $review->setStatus(ReviewStatus::VALIDATED);
        $review->setValidatedBy($validatedBy);
        $review->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();
    }

    public function refuseReview(Review $review, User $validatedBy): void
    {
        $review->setStatus(ReviewStatus::REFUSED);
        $review->setValidatedBy($validatedBy);
        $review->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();
    }
}
