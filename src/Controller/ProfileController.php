<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserProfileType;
use App\Repository\CarRepository;
use App\Repository\RideRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\RideBookingRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'app_profile', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        CarRepository $carRepository,
        RideRepository $rideRepository,
        RideBookingRepository $bookingRepository,
        SluggerInterface $slugger
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Traitement de l'achat de crédits AVANT le formulaire de profil
        if ($request->isMethod('POST') && $request->request->has('credits')) {
            $this->handleCreditPurchase($request, $user, $em);
            return $this->redirectToRoute('app_profile');
        }
        
        // Mode édition : déterminé par le paramètre GET 'edit'
        $isEditing = $request->query->get('edit') === 'true';
        
        // Créer le formulaire de profil
        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        // Traiter la soumission du formulaire de profil
        if ($form->isSubmitted() && $form->isValid()) {
            return $this->handleProfileUpdate($form, $user, $slugger, $em);
        }

        // Récupérer les données de l'utilisateur
        $cars = $carRepository->findBy(
            ['owner' => $user],
            ['createdAt' => 'DESC']
        );

        $ridesAsDriver = $rideRepository->findBy(
            ['driver' => $user],
            ['departureDate' => 'DESC', 'departureTime' => 'DESC']
        );

        $bookingsAsPassenger = $bookingRepository->findBy(
            ['passenger' => $user],
            ['createdAt' => 'DESC']
        );

        // Récupérer les bookings en attente de feedback
        $bookingsAwaitingFeedback = $bookingRepository->createQueryBuilder('b')
            ->join('b.ride', 'r')
            ->where('b.passenger = :user')
            ->andWhere('b.status = :confirmed')
            ->andWhere('r.status = :completed')
            ->andWhere('b.feedbackAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('confirmed', \App\Enum\BookingStatus::CONFIRMED)
            ->setParameter('completed', \App\Enum\RideStatus::COMPLETED)
            ->orderBy('r.endedAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'cars' => $cars,
            'ridesAsDriver' => $ridesAsDriver,
            'bookingsAsPassenger' => $bookingsAsPassenger,
            'bookingsAwaitingFeedback' => $bookingsAwaitingFeedback,
            'form' => $form->createView(),
            'isEditing' => $isEditing,
        ]);
    }

    /**
     * Redirection vers la page profil en mode édition
     */
    #[Route('/edit', name: 'app_profile_edit')]
    public function edit(): Response
    {
        return $this->redirectToRoute('app_profile', ['edit' => 'true']);
    }

    /**
     * Gestion de l'achat de crédits
     * 
     * Méthode déplacée et simplifiée
     */
    private function handleCreditPurchase(Request $request, User $user, EntityManagerInterface $em): void
    {
        $creditsToAdd = $request->request->get('credits');
        
        if (is_numeric($creditsToAdd) && $creditsToAdd > 0) {
            $currentCredits = $user->getCredits() ?? 0; 
            $user->setCredits($currentCredits + (int)$creditsToAdd);
            $em->flush();
            
            $this->addFlash('success', "Vous avez ajouté {$creditsToAdd} crédits à votre compte !");
        } else {
            $this->addFlash('error', 'Veuillez entrer un nombre valide de crédits (minimum 1).');
        }
    }

    /**
     * Gestion de la mise à jour du profil
     * 
     * Type de retour et gestion de l'upload
     */
    private function handleProfileUpdate($form, User $user, SluggerInterface $slugger, EntityManagerInterface $em): Response
    {
        // Gestion de l'upload de la photo de profil
        $profilePictureFile = $form->get('profilePicture')->getData();
        
        if ($profilePictureFile) {
            $uploadResult = $this->handleProfilePictureUpload($profilePictureFile, $user, $slugger);
            
            // Si l'upload échoue, ne pas enregistrer les modifications
            if ($uploadResult === false) {
                return $this->redirectToRoute('app_profile', ['edit' => 'true']);
            }
        }

        $em->flush();
        $this->addFlash('success', 'Votre profil a été mis à jour avec succès !');
        
        return $this->redirectToRoute('app_profile');
    }

    /**
     * Gestion de l'upload de la photo de profil
     * 
     * Retourne bool pour indiquer le succès/échec
     */
    private function handleProfilePictureUpload($profilePictureFile, User $user, SluggerInterface $slugger): bool
    {
         // Vérifie que le fichier est valide
        if (!$profilePictureFile instanceof UploadedFile) {
            $this->addFlash('error', 'Fichier invalide.');
            return false;
        }

        // Vérifie la taille et le type MIME
        $maxSize = 2 * 1024 * 1024; // 2 Mo
        $allowedMimeTypes = ['image/jpeg', 'image/png'];

        if ($profilePictureFile->getSize() > $maxSize) {
            $this->addFlash('error', 'Le fichier est trop volumineux (max 2 Mo).');
            return false;
        }

        if (!in_array($profilePictureFile->getMimeType(), $allowedMimeTypes)) {
            $this->addFlash('error', 'Seuls les fichiers JPEG et PNG sont autorisés.');
            return false;
        }

        $originalFilename = pathinfo($profilePictureFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $profilePictureFile->guessExtension();

        // Déplace le fichier vers le dossier d'upload
        try {
            $profilePictureFile->move(
                $this->getParameter('profile_pictures_directory'),
                $newFilename
            );
            
            // Supprimer l'ancienne photo si elle existe
            if ($user->getProfilePicture()) {
                $oldPicturePath = $this->getParameter('profile_pictures_directory') . '/' . $user->getProfilePicture();
                if (file_exists($oldPicturePath)) {
                    @unlink($oldPicturePath);
                }
            }
            
            $user->setProfilePicture($newFilename);
            return true;
            
        } catch (FileException $e) {
            $this->addFlash('error', 'Une erreur est survenue lors du chargement de votre image. Veuillez réessayer.');
            return false;
        }
    }
}