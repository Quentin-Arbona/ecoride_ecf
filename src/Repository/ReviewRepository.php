<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Review;
use App\Enum\ReviewStatus;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function findPendingReviews()
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', ReviewStatus::PENDING)
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findReviewsByDriver(User $driver)
    {
        return $this->createQueryBuilder('r')
            ->where('r.driver = :driver')
            ->andWhere('r.status = :status')
            ->setParameter('driver', $driver)
            ->setParameter('status', ReviewStatus::VALIDATED)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
