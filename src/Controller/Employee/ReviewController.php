<?php

namespace App\Controller\Employee;

use App\Entity\Review;
use App\Entity\User;
use App\Service\ReviewManager;
use App\Repository\ReviewRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/employee/review')]
#[IsGranted(User::ROLE_EMPLOYE)]
class ReviewController extends AbstractController
{
    public function __construct(
        private ReviewManager $reviewManager,
        private ReviewRepository $reviewRepository
    ) {}

    #[Route('s', name: 'employee_reviews_list', methods: ['GET'])]
    public function list(): Response
    {
        $pendingReviews = $this->reviewRepository->findPendingReviews();

        return $this->render('employee/reviews/list.html.twig', [
            'pendingReviews' => $pendingReviews,
        ]);
    }

    #[Route('/{id}/validate', name: 'employee_review_validate', methods: ['POST'])]
    public function validate(Review $review): Response
    {
        $this->reviewManager->validateReview($review, $this->getUser());

        $this->addFlash('success', 'Avis validé avec succès.');
        return $this->redirectToRoute('employee_reviews_list');
    }

    #[Route('/{id}/refuse', name: 'employee_review_refuse', methods: ['POST'])]
    public function refuse(Review $review): Response
    {
        $this->reviewManager->refuseReview($review, $this->getUser());

        $this->addFlash('success', 'Avis refusé.');
        return $this->redirectToRoute('employee_reviews_list');
    }
}
