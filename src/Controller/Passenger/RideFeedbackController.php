<?php

namespace App\Controller\Passenger;

use App\Entity\Review;
use App\Entity\RideBooking;
use App\Enum\BookingStatus;
use App\Service\RideCompletionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/passenger/feedback')]
#[IsGranted('ROLE_USER')]
class RideFeedbackController extends AbstractController
{
    public function __construct(
        private RideCompletionService $completionService
    ) {}

    #[Route('/{id}', name: 'passenger_ride_feedback', methods: ['GET', 'POST'])]
    public function giveFeedback(
        RideBooking $booking,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        // Vérifier que c'est bien le passager de cette réservation
        if ($booking->getPassenger() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas donner un avis sur cette réservation.');
        }

        // Empêcher la re-soumission
        if ($booking->getFeedbackAt()) {
            $this->addFlash('warning', 'Vous avez déjà donné votre avis sur ce trajet.');
            return $this->redirectToRoute('app_profile');
        }

        if ($request->isMethod('POST')) {
            $isSuccessful = $request->request->get('successful') === '1';
            $feedback = trim($request->request->get('feedback', ''));
            $rating = (int) $request->request->get('rating', 0);

            // Définir le statut du booking
            $booking->setStatus($isSuccessful ? BookingStatus::COMPLETED : BookingStatus::DISPUTED);
            $booking->setFeedbackAt(new \DateTimeImmutable());

            // Sauvegarder le rating et feedback uniquement s'ils sont fournis
            if ($rating > 0) {
                $booking->setRating($rating);
            }
            if (!empty($feedback)) {
                $booking->setFeedback($feedback);
            }

            // Créer une Review UNIQUEMENT si rating ET feedback sont fournis
            if ($rating > 0 && !empty($feedback)) {
                $review = new Review();
                $review->setAuthor($this->getUser());
                $review->setDriver($booking->getRide()->getDriver());
                $review->setRide($booking->getRide());
                $review->setBooking($booking);
                $review->setContent($feedback);
                $review->setRating($rating);
                // Le statut est déjà défini à PENDING par le constructeur

                $em->persist($review);
            }

            $em->flush();

            // Envoyer notification de litige si nécessaire
            if ($booking->getStatus() === BookingStatus::DISPUTED) {
                $this->completionService->notifyDispute($booking);
            }

            // Vérifier et traiter les crédits du conducteur
            $this->completionService->processCreditsAfterValidation($booking->getRide());

            $this->addFlash('success', 'Merci pour votre retour !');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('passenger/ride_feedback.html.twig', [
            'booking' => $booking,
        ]);
    }
}
