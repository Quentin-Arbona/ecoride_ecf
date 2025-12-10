<?php

namespace App\Controller\Employee;

use App\Entity\User;
use App\Entity\RideBooking;
use App\Enum\BookingStatus;
use App\Service\RideCompletionService;
use App\Repository\RideBookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/employee/dispute')]
#[IsGranted(User::ROLE_EMPLOYE)]
class DisputeController extends AbstractController
{
    public function __construct(
        private RideBookingRepository $bookingRepository,
        private RideCompletionService $completionService
    ) {}

    #[Route('s', name: 'employee_disputes_list', methods: ['GET'])]
    public function list(): Response
    {
        $disputes = $this->bookingRepository->findDisputedBookings();

        return $this->render('employee/disputes/list.html.twig', [
            'disputes' => $disputes,
        ]);
    }

    #[Route('/{id}', name: 'employee_dispute_show', methods: ['GET', 'POST'])]
    public function show(
        RideBooking $booking,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        // Vérifier que c'est bien un litige
        if ($booking->getStatus() !== BookingStatus::DISPUTED) {
            $this->addFlash('error', 'Cette réservation n\'est pas contestée.');
            return $this->redirectToRoute('employee_disputes_list');
        }

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            $resolutionNotes = $request->request->get('resolution_notes', '');

            if ($action === 'resolve') {
                // Résoudre en faveur du passager - convertir en COMPLETED
                $booking->setStatus(BookingStatus::COMPLETED);
                $booking->setResolutionNotes($resolutionNotes);
                $booking->setResolvedBy($this->getUser());
                $booking->setResolvedAt(new \DateTimeImmutable());

                $em->flush();

                // Déclencher le calcul des crédits (le conducteur est maintenant crédité)
                $this->completionService->processCreditsAfterValidation($booking->getRide());

                $this->addFlash('success', 'Litige résolu favorablement. Le conducteur a été crédité.');
            } elseif ($action === 'reject') {
                // Garder le statut DISPUTED mais ajouter des notes
                $booking->setResolutionNotes($resolutionNotes);
                $booking->setResolvedBy($this->getUser());
                $booking->setResolvedAt(new \DateTimeImmutable());

                $em->flush();

                $this->addFlash('success', 'Notes de résolution enregistrées. Le litige reste en attente.');
            }

            return $this->redirectToRoute('employee_disputes_list');
        }

        return $this->render('employee/disputes/show.html.twig', [
            'booking' => $booking,
        ]);
    }
}
