<?php

namespace App\Controller;

use App\Entity\Ride;
use App\Entity\User;
use App\Form\RideType;
use App\Enum\RideStatus;
use App\Repository\RideRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ride')]
class RideController extends AbstractController
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em
    ) {}

    /**
     * Liste et recherche des trajets disponibles
     */
    #[Route('', name: 'app_ride_index', methods: ['GET'])]
    public function index(RideRepository $rideRepository, Request $request): Response
    {
        // Récupération des paramètres de recherche
        $departure = $request->query->get('departure');
        $arrival = $request->query->get('arrival');
        $date = $request->query->get('date');
        $electric = $request->query->get('electric'); 
        $maxPrice = $request->query->get('max_price');
        $maxDuration = $request->query->get('max_duration');

        // Construction de la requête de base
        $queryBuilder = $rideRepository->createQueryBuilder('r')
            ->leftJoin('r.driver', 'd')
            ->leftJoin('r.car', 'c')
            ->addSelect('d', 'c')
            ->where('r.status = :status')
            ->setParameter('status', RideStatus::PENDING)
            ->andWhere('r.departureDate >= :today')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->orderBy('r.departureDate', 'ASC')
            ->addOrderBy('r.departureTime', 'ASC');

        // Filtre : ville de départ
        if ($departure) {
            $queryBuilder->andWhere('r.departurePlace LIKE :departure')
                ->setParameter('departure', '%' . $departure . '%');
        }

        // Filtre : ville d'arrivée
        if ($arrival) {
            $queryBuilder->andWhere('r.arrivalPlace LIKE :arrival')
                ->setParameter('arrival', '%' . $arrival . '%');
        }

        // Filtre : date de départ
        if ($date) {
            try {
                $searchDate = new \DateTimeImmutable($date);
                $queryBuilder->andWhere('r.departureDate = :date')
                    ->setParameter('date', $searchDate);
            } catch (\Exception $e) {
                //Gestion d'erreur si la date est invalide
                $this->addFlash('warning', 'Format de date invalide.');
            }
        }

        // Filtre voiture électrique
        if ($electric) {
            $queryBuilder->andWhere('c.isElectric = true');
        }

        // Filtre prix maximum
        if ($maxPrice && is_numeric($maxPrice)) {
            $queryBuilder->andWhere('r.pricePerSeat <= :maxPrice')
                ->setParameter('maxPrice', (float) $maxPrice);
        }

        if ($maxDuration) {
            $queryBuilder->andWhere('r.estimatedDuration <= :maxDuration')
                ->setParameter('maxDuration', $maxDuration);
}

        // Exécution de la requête
        $allRides = $queryBuilder->getQuery()->getResult();
        
        // Filtrage des trajets avec places disponibles + réindexation du tableau
        $rides = array_values(array_filter($allRides, fn(Ride $ride) => $ride->getRemainingSeats() > 0));

        // Recherche de date alternative si aucun résultat
        $alternativeDate = null;
        if (empty($rides) && $departure && $arrival && $date) {
            // Note : Vérifiez que cette méthode existe dans RideRepository
            if (method_exists($rideRepository, 'findNextAvailableDate')) {
                $alternativeDate = $rideRepository->findNextAvailableDate(
                    $departure,
                    $arrival,
                    new \DateTimeImmutable($date)
                );
            }
        }

        return $this->render('ride/index.html.twig', [
            'rides' => $rides,
            'alternativeDate' => $alternativeDate,
            'searchParams' => [ // Paramètres pour pré-remplir le formulaire
                'departure' => $departure,
                'arrival' => $arrival,
                'date' => $date,
            ],
        ]);
    }

    /**
     * Création d'un nouveau trajet
     */
    #[Route('/new', name: 'app_ride_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (method_exists($user, 'hasCars')) {
            if (!$user->hasCars()) {
                $this->addFlash('error', 'Vous devez d\'abord ajouter un véhicule à votre profil pour proposer un trajet.');
                return $this->redirectToRoute('app_car_new', ['from' => 'ride']);
            }
        } elseif (method_exists($user, 'hasCars')) {
            if (!$user->hasCars()) {
                $this->addFlash('error', 'Vous devez d\'abord ajouter un véhicule à votre profil pour proposer un trajet.');
                return $this->redirectToRoute('app_car_new', ['from' => 'ride']);
            }
        } else {
            // Fallback si aucune méthode n'existe
            if ($user->getCars()->isEmpty()) {
                $this->addFlash('error', 'Vous devez d\'abord ajouter un véhicule à votre profil pour proposer un trajet.');
                return $this->redirectToRoute('app_car_new', ['from' => 'ride']);
            }
        }

        // Vérification de l'email vérifié
        if (!$user->isVerified()) {
            $this->addFlash('error', 'Vous devez vérifier votre adresse email avant de proposer un trajet.');
            return $this->redirectToRoute('app_profile');
        }

        $ride = new Ride();
        $ride->setDriver($user);

        $form = $this->createForm(RideType::class, $ride);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($ride);
            $this->em->flush();

            $this->addFlash('success', 'Votre trajet a été créé avec succès !');

            return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
        }

        return $this->render('ride/new.html.twig', [
            'ride' => $ride,
            'form' => $form,
        ]);
    }

    /**
     * Affichage du détail d'un trajet
     */
    #[Route('/{id}', name: 'app_ride_show', methods: ['GET'])]
    public function show(Ride $ride): Response
    {
        $userBooking = null;

        if ($user = $this->getUser()) {
            $userBooking = $ride->getPassengerBooking($user);
        }

        return $this->render('ride/show.html.twig', [
            'ride' => $ride,
            'userBooking' => $userBooking,
        ]);
    }

    /**
     * Modification d'un trajet
     */
    #[Route('/{id}/edit', name: 'app_ride_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Ride $ride): Response
    {
        // Vérification que l'utilisateur est bien le conducteur
        if ($ride->getDriver() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier ce trajet.');
            return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
        }

        // Empêcher la modification d'un trajet annulé
        if ($ride->getStatus() === RideStatus::CANCELLED) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier un trajet annulé.');
            return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
        }

        // Sauvegarder le nombre de places actuellement réservées
        $bookedSeatsCount = $ride->getBookedSeatsCount();

        $form = $this->createForm(RideType::class, $ride);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier que le nouveau nombre de places est suffisant pour les réservations existantes
            if ($ride->getAvailableSeats() < $bookedSeatsCount) {
                $this->addFlash('error',
                    sprintf('Impossible de réduire le nombre de places à %d. Il y a déjà %d place(s) réservée(s).',
                    $ride->getAvailableSeats(),
                    $bookedSeatsCount)
                );
                return $this->redirectToRoute('app_ride_edit', ['id' => $ride->getId()]);
            }

            $ride->setUpdatedAt(new \DateTimeImmutable());
            $this->em->flush();

            $this->addFlash('success', 'Votre trajet a été modifié avec succès.');

            return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
        }

        return $this->render('ride/edit.html.twig', [
            'ride' => $ride,
            'form' => $form,
        ]);
    }

    /**
     * Annulation d'un trajet
     */
    #[Route('/{id}/cancel', name: 'app_ride_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function cancel(Request $request, Ride $ride): Response
    {
        // Vérification que l'utilisateur est bien le conducteur
        if ($ride->getDriver() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas annuler ce trajet.');
            return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
        }

        // Vérifier que le trajet n'est pas déjà annulé
        if ($ride->getStatus() === RideStatus::CANCELLED) {
            $this->addFlash('warning', 'Ce trajet est déjà annulé.');
            return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
        }

        if ($this->isCsrfTokenValid('cancel'.$ride->getId(), $request->request->get('_token'))) {
            $ride->setStatus(RideStatus::CANCELLED);
            $ride->setUpdatedAt(new \DateTimeImmutable());
            
            // Annuler toutes les réservations actives
            $cancelledCount = 0;
            foreach ($ride->getBookings() as $booking) {
                if ($booking->getStatus() !== \App\Enum\BookingStatus::CANCELLED) {
                    $booking->cancel();
                    $cancelledCount++;
                }
            }

            $this->em->flush();

            $message = 'Votre trajet a été annulé.';
            if ($cancelledCount > 0) {
                $message .= " {$cancelledCount} passager(s) ont été notifié(s).";
            }
            $this->addFlash('success', $message);
        }

        return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
    }

    /**
     * Suppression d'un trajet
     */
    #[Route('/{id}/delete', name: 'app_ride_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Ride $ride): Response
    {
        if ($ride->getDriver() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer ce trajet.');
            return $this->redirectToRoute('app_ride_index');
        }

        if ($ride->getBookings()->count() > 0) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer un trajet avec des réservations. Annulez-le plutôt.');
            return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
        }

        if ($this->isCsrfTokenValid('delete'.$ride->getId(), $request->request->get('_token'))) {
            $this->em->remove($ride);
            $this->em->flush();

            $this->addFlash('success', 'Le trajet a été supprimé.');
        }

        return $this->redirectToRoute('app_ride_index');
    }

    /**
     * Liste des trajets proposés par l'utilisateur connecté
     */
    #[Route('/my/drives', name: 'app_my_rides', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function myRides(): Response
    {
        $user = $this->getUser();

        $rides = $this->em->getRepository(Ride::class)
            ->createQueryBuilder('r')
            ->where('r.driver = :user')
            ->setParameter('user', $user)
            ->orderBy('r.departureDate', 'DESC')
            ->addOrderBy('r.departureTime', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('ride/my_rides.html.twig', [
            'rides' => $rides,
        ]);
    }
}