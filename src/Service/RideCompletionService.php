<?php

namespace App\Service;

use App\Entity\Ride;
use App\Entity\RideBooking;
use App\Enum\BookingStatus;
use App\Enum\RideStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class RideCompletionService
{
    public function __construct(
        private EntityManagerInterface $em,
        private MailerInterface $mailer
    ) {}

    public function notifyPassengers(Ride $ride): void
    {
        foreach ($ride->getConfirmedBookings() as $booking) {
            $email = (new TemplatedEmail())
                ->from('noreply@ecoride.fr')
                ->to($booking->getPassenger()->getEmail())
                ->subject('Votre trajet est terminé')
                ->htmlTemplate('emails/ride_completed.html.twig')
                ->context([
                    'ride' => $ride,
                    'booking' => $booking,
                ]);

            $this->mailer->send($email);
        }
    }

    /**
     * Traite les crédits après validation des passagers.
     * Crédite le conducteur dès que tous les passagers ont validé,
     * mais uniquement pour les bookings COMPLETED (pas les DISPUTED).
     */
    public function processCreditsAfterValidation(Ride $ride): void
    {
        $confirmedBookings = $ride->getConfirmedBookings();

        // Vérifier si tous les passagers ont fourni un feedback
        $allValidated = true;
        foreach ($confirmedBookings as $booking) {
            // Un booking est validé s'il a un feedbackAt (= COMPLETED ou DISPUTED)
            if (!$booking->getFeedbackAt()) {
                $allValidated = false;
                break;
            }
        }

        if (!$allValidated) {
            return; // Pas tous les passagers ont validé
        }

        // Calculer les crédits depuis les bookings COMPLETED uniquement
        $totalCredits = 0;
        foreach ($confirmedBookings as $booking) {
            // Créditer uniquement les COMPLETED, pas les DISPUTED
            if ($booking->getStatus() === BookingStatus::COMPLETED && !$booking->getValidatedAt()) {
                $totalCredits += (float) $booking->getTotalPrice();
                // Marquer comme validé
                $booking->setValidatedAt(new \DateTimeImmutable());
            }
        }

        // Créditer le conducteur
        if ($totalCredits > 0) {
            $driver = $ride->getDriver();
            $driver->addCredits((int) $totalCredits);
            $this->em->flush();
        }
    }

    public function notifyDispute(RideBooking $booking): void
    {
        $email = (new TemplatedEmail())
            ->from('noreply@ecoride.fr')
            ->to('admin@ecoride.fr') // Email fictif pour le projet
            ->subject('Nouveau litige signalé')
            ->htmlTemplate('emails/dispute_notification.html.twig')
            ->context(['booking' => $booking]);

        $this->mailer->send($email);
    }
}