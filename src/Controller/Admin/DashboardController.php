<?php 

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\EmployeeType;
use App\Repository\UserRepository;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ResetPasswordRequestRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;

#[Route('/admin')]
#[IsGranted(User::ROLE_ADMIN)]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(UserRepository $userRepository): Response
    {
        $employees = $userRepository->findByRole(User::ROLE_EMPLOYE);
        
        return $this->render('admin/dashboard.html.twig', [
            'employees' => $employees,
        ]);
    }

   #[Route('/employee/create', name: 'admin_employee_create')]
public function createEmployee(
    Request $request,
    UserPasswordHasherInterface $passwordHasher,
    EntityManagerInterface $em,
    MailerInterface $mailer,
    ResetPasswordHelperInterface $resetPasswordHelper
): Response {
    $employee = new User();
    $form = $this->createForm(EmployeeType::class, $employee);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // 1. Crée l'employé avec un mot de passe aléatoire
        $randomPassword = bin2hex(random_bytes(16));
        $employee->setPassword($passwordHasher->hashPassword($employee, $randomPassword));
        $employee->setRoles([User::ROLE_EMPLOYE]);
        $employee->setIsVerified(false);

        $em->persist($employee);
        $em->flush();

        // 2. Génère un token de réinitialisation
        $resetToken = $resetPasswordHelper->generateResetToken($employee);

        // 3. Crée l'email avec le lien de réinitialisation
        $resetUrl = $this->generateUrl(
            'app_reset_password',
            ['token' => $resetToken->getToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@ecoride.fr', 'EcoRide Admin'))
            ->to($employee->getEmail())
            ->subject('Votre compte employé EcoRide')
            ->htmlTemplate('emails/employee_created.html.twig')
            ->context([
                'employee' => $employee,
                'resetUrl' => $resetUrl,
                'expiresAt' => $resetToken->getExpiresAt(),
            ]);

        // 4. Envoie l'email (c'est ici que ça bloque apparemment)
        $mailer->send($email);

        $this->addFlash('success', sprintf(
            'Employé "%s" créé avec succès ! Un email a été envoyé à %s',
            $employee->getPseudo(),
            $employee->getEmail()
        ));

        return $this->redirectToRoute('admin_dashboard');
    }

    return $this->render('admin/employee_form.html.twig', [
        'form' => $form->createView(),
        'employee' => $employee,
    ]);
}


    #[Route('/employee/{id}/edit', name: 'admin_employee_edit')]
    public function editEmployee(User $employee, Request $request, EntityManagerInterface $em): Response
    {
        // Vérification que c'est bien un employé
        if (!in_array(User::ROLE_EMPLOYE, $employee->getRoles())) {
            $this->addFlash('error', 'Cet utilisateur n\'est pas un employé.');
            return $this->redirectToRoute('admin_dashboard');
        }

        $form = $this->createForm(EmployeeType::class, $employee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            
            $this->addFlash('success', 'Employé mis à jour avec succès !');
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/employee_form.html.twig', [
            'form' => $form->createView(),
            'employee' => $employee,
        ]);
    }

    #[Route('/employee/{id}/delete', name: 'admin_employee_delete', methods: ['POST'])]
    public function deleteEmployee(
        User $employee, 
        Request $request,
        EntityManagerInterface $em,
        ResetPasswordRequestRepository $resetPasswordRequestRepository
    ): Response {
        // Vérification CSRF
        if (!$this->isCsrfTokenValid('delete' . $employee->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_dashboard');
        }

        // Vérification que c'est bien un employé
        if (!in_array(User::ROLE_EMPLOYE, $employee->getRoles())) {
            $this->addFlash('error', 'Cet utilisateur n\'est pas un employé.');
            return $this->redirectToRoute('admin_dashboard');
        }

        $resetPasswordRequests = $resetPasswordRequestRepository->findBy(['user' => $employee]);
    foreach ($resetPasswordRequests as $resetRequest) {
        $em->remove($resetRequest);
    }

        $pseudo = $employee->getPseudo();
        $em->remove($employee);
        $em->flush();

        $this->addFlash('success', sprintf('Employé "%s" supprimé avec succès !', $pseudo));
        return $this->redirectToRoute('admin_dashboard');
    }
}