<?php

namespace App\Controller;

use App\Entity\Car;
use App\Form\CarType;
use App\Repository\CarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/car')]
#[IsGranted('ROLE_USER')]
class CarController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    #[Route('/new', name: 'app_car_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $car = new Car();
        $car->setOwner($this->getUser());
        
        $form = $this->createForm(CarType::class, $car);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($car);
            $this->em->flush();
            
            $this->addFlash('success', 'Votre véhicule a été ajouté avec succès !');

            if ($request->query->get('from') === 'profile') {
                return $this->redirectToRoute('app_profile');
            }

            return $this->redirectToRoute('app_car_index');
        }

        return $this->render('car/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('', name: 'app_car_index', methods: ['GET'])]
    public function index(CarRepository $carRepository): Response
    {
        $cars = $carRepository->findBy(
            ['owner' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        return $this->render('car/index.html.twig', [
            'cars' => $cars,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_car_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Car $car): Response
    {
        if ($car->getOwner() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier ce véhicule.');
            return $this->redirectToRoute('app_car_index');
        }

        $form = $this->createForm(CarType::class, $car);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Votre véhicule a été modifié avec succès.');

            return $this->redirectToRoute('app_car_index');
        }

        return $this->render('car/edit.html.twig', [
            'car' => $car,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/toggle', name: 'app_car_toggle', methods: ['POST'])]
    public function toggle(Request $request, Car $car): Response
    {
        if ($car->getOwner() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier ce véhicule.');
            return $this->redirectToRoute('app_car_index');
        }
        
        if ($this->isCsrfTokenValid('toggle'.$car->getId(), $request->request->get('_token'))) {
            $car->setIsActive(!$car->getIsActive());
            $this->em->flush();

            $message = $car->getIsActive() 
                ? 'Le véhicule a été activé.' 
                : 'Le véhicule a été désactivé.';
            
            $this->addFlash('success', $message);
        }

        return $this->redirectToRoute('app_car_index');
    }

    #[Route('/{id}', name: 'app_car_delete', methods: ['POST'])]
    public function delete(Request $request, Car $car): Response
    {
        if ($car->getOwner() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer ce véhicule.');
            return $this->redirectToRoute('app_car_index');
        }

        // Vérifier si le véhicule est utilisé dans des trajets actifs
        $activeRides = $this->em->getRepository(\App\Entity\Ride::class)
            ->createQueryBuilder('r')
            ->where('r.car = :car')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('car', $car)
            ->setParameter('statuses', [\App\Enum\RideStatus::PENDING, \App\Enum\RideStatus::ACTIVE])
            ->getQuery()
            ->getResult();

        if (!empty($activeRides)) {
            $this->addFlash('error', 'Impossible de supprimer ce véhicule car il est utilisé dans des trajets actifs.');
            return $this->redirectToRoute('app_car_index');
        }

        if ($this->isCsrfTokenValid('delete'.$car->getId(), $request->request->get('_token'))) {
            $this->em->remove($car);
            $this->em->flush();

            $this->addFlash('success', 'Le véhicule a été supprimé.');
        }

        return $this->redirectToRoute('app_profile');
    }
}