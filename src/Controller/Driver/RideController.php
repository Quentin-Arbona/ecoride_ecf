<?php 

namespace App\Controller\Driver;

use App\Entity\Ride;
use App\Enum\RideStatus;
use App\Service\RideCompletionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('driver/ride')]
class RideController extends AbstractController
{
    #[Route('/{id}/start', name: 'driver_ride_start', methods: ['POST'])]
    public function start(Ride $ride, EntityManagerInterface $em): Response
    {
        // Vérifie que l'utilisateur est le chauffeur du trajet (via le Voter)
        $this->denyAccessUnlessGranted('DRIVE', $ride);

        try {
            if ($ride->getStatus() !== RideStatus::PENDING) {
                throw new \LogicException('Le trajet ne peut être démarré que s\'il est en attente.');
            }

            $ride->setStatus(RideStatus::ACTIVE);
            $ride->setStartedAt(new \DateTime());
            $em->flush();

            $this->addFlash('success', 'Trajet démarré avec succès !');
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_profile'); 
    }

    #[Route('/{id}/end', name: 'driver_ride_end', methods: ['POST'])]
    public function end(
        Ride $ride,
        EntityManagerInterface $em,
        \Symfony\Component\Mailer\MailerInterface $mailer
    ): Response {
        // Vérifie que l'utilisateur est le chauffeur du trajet (via le Voter)
        $this->denyAccessUnlessGranted('DRIVE', $ride);

        try {
            if ($ride->getStatus() !== RideStatus::ACTIVE) {
                throw new \LogicException('Le trajet doit être en cours pour être terminé.');
            }

            $ride->setStatus(RideStatus::COMPLETED);
            $ride->setEndedAt(new \DateTime());
            $em->flush();

             // Envoie les emails aux passagers
            foreach ($ride->getConfirmedBookings() as $booking) {
                $email = (new \Symfony\Bridge\Twig\Mime\TemplatedEmail())
                    ->from('noreply@ecoride.fr')
                    ->to($booking->getPassenger()->getEmail())
                    ->subject('Votre trajet est terminé')
                    ->htmlTemplate('emails/ride_completed.html.twig')
                    ->context([
                        'ride' => $ride,
                        'booking' => $booking,
                    ]);

                $mailer->send($email);
            }

            $this->addFlash('success', 'Trajet terminé avec succès !');
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_profile'); 
    }
}