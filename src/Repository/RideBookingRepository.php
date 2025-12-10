<?php

namespace App\Repository;

use App\Entity\RideBooking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RideBooking>
 */
class RideBookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RideBooking::class);
    }

    /**
     * Trouve tous les bookings en litige (DISPUTED)
     * @return RideBooking[]
     */
    public function findDisputedBookings(): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.status = :status')
            ->setParameter('status', \App\Enum\BookingStatus::DISPUTED)
            ->orderBy('b.feedbackAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return RideBooking[] Returns an array of RideBooking objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?RideBooking
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
