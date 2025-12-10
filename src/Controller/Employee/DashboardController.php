<?php

namespace App\Controller\Employee;

use App\Entity\User;
use App\Repository\ReviewRepository;
use App\Repository\RideBookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    public function __construct(
        private ReviewRepository $reviewRepository,
        private RideBookingRepository $bookingRepository
    ) {}

    #[Route('/employee', name: 'employee_dashboard')]
    #[IsGranted(User::ROLE_EMPLOYE)]
    public function index(): Response
    {
        $pendingReviewsCount = count($this->reviewRepository->findPendingReviews());
        $disputesCount = count($this->bookingRepository->findDisputedBookings());

        return $this->render('employee/dashboard.html.twig', [
            'pendingReviewsCount' => $pendingReviewsCount,
            'disputesCount' => $disputesCount,
        ]);
    }
}
