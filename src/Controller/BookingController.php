<?php

// src/Controller/BookingController.php
namespace App\Controller;

use App\Entity\Ride;
use App\Entity\RideBooking;
use App\Enum\BookingStatus;
use App\Repository\RideRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BookingController extends AbstractController
{
    #[Route('/booking/confirm/{rideId}', name: 'app_booking_confirm', methods: ['GET'])]
    public function confirm(Ride $rideId, RideRepository $rideRepository, Request $request): Response
    {
        $ride = $rideRepository->find($rideId);
        if (!$ride) {
            throw $this->createNotFoundException('Trajet non trouvé');
        }

        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$ride->hasAvailableSeats()) {
            $this->addFlash('error', 'Il n\'y a plus de places disponibles pour ce trajet.');
            return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
        }

        if ($user->hasBookedRide($ride)) {
            $this->addFlash('error', 'Vous avez déjà réservé ce trajet.');
            return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
        }

        if ($user->getCredits() < $ride->getPricePerSeat()) {
            $this->addFlash('error', 'Vous n\'avez pas assez de crédits pour réserver ce trajet.');
            return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
        }

        return $this->render('booking/confirm.html.twig', [
            'ride' => $ride,
            'userCredits' => $user->getCredits(),
        ]);
    }

    #[Route('/booking/create/{rideId}', name: 'app_booking_create', methods: ['POST'])]
    public function create(
        int $rideId,
        RideRepository $rideRepository,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $ride = $rideRepository->find($rideId);
        if (!$ride) {
            throw $this->createNotFoundException('Trajet non trouvé');
        }
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('booking_' . $ride->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
        }

        if (!$ride->hasAvailableSeats()) {
            $this->addFlash('error', 'Il n\'y a plus de places disponibles.');
            return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
        }

        if ($user->hasBookedRide($ride)) {
            $this->addFlash('error', 'Vous avez déjà réservé ce trajet.');
            return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
        }

        if ($user->getCredits() < $ride->getPricePerSeat()) {
            $this->addFlash('error', 'Crédits insuffisants.');
            return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
        }

        // Créer la réservation
        $booking = new RideBooking();
        $booking->setRide($ride);
        $booking->setPassenger($user);
        $booking->setSeatsBooked(1);
        $booking->calculateTotalPrice();
        $booking->setStatus(BookingStatus::CONFIRMED);

        // Mettre à jour les données
        $ride->setAvailableSeats($ride->getAvailableSeats() - 1);
        $user->subtractCredits($ride->getPricePerSeat());

        $em->persist($booking);
        $em->flush();

        $this->addFlash('success', 'Votre réservation a été enregistrée avec succès !');
        return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
    }
}

