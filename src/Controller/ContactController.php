<?php

namespace App\Controller;

use App\DTO\ContactDTO;
use App\Form\ContactType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Psr\Log\LoggerInterface;


final class ContactController extends AbstractController
{
    #[Route('/test-email', name: 'test_email')]
    public function testEmail(MailerInterface $mailer, LoggerInterface $logger): Response
    {
        try {
            $logger->info('Début du test d\'envoi d\'email');

            $email = (new TemplatedEmail())
                ->from(new Address('test@example.com', 'Test User'))
                ->to('contact@ecoride.local')
                ->subject('Test Email EcoRide')
                ->htmlTemplate('emails/contact.html.twig')
                ->context([
                    'name' => 'Test User',
                    'userEmail' => 'test@example.com',
                    'message' => 'Ceci est un email de test',
                ]);

            $mailer->send($email);
            $logger->info('Email envoyé avec succès');

            return new Response('Email envoyé avec succès !');
        } catch(\Exception $e) {
            $logger->error('Erreur lors de l\'envoi : ' . $e->getMessage());
            return new Response('Erreur : ' . $e->getMessage(), 500);
        }
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(MailerInterface $mailer, Request $request, LoggerInterface $logger): Response
    {

        $contact = new ContactDTO();

        // Pré-remplir le formulaire si l'utilisateur est connecté
        /**
         * @var User $user
         */
        if( $user = $this->getUser() ) {
            $contact->name = $user->getPseudo();
            $contact->email = $user->getEmail();
        }

        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if($form->isSubmitted()) {
            $logger->info('Formulaire de contact soumis');

            if($form->isValid()) {
                $logger->info('Formulaire valide');

                try {
                    $data = $form->getData();
                    $logger->info('Données récupérées', [
                        'name' => $data->name,
                        'email' => $data->email,
                    ]);

                    $email = (new TemplatedEmail())
                        ->from(new Address($data->email, $data->name))
                        ->to('contact@ecoride.local')
                        ->subject('Contact depuis le site EcoRide')
                        ->htmlTemplate('emails/contact.html.twig')
                        ->locale('fr')
                        ->context([
                            'name' => $data->name,
                            'userEmail' => $data->email,
                            'message' => $data->message,
                        ]);

                    $logger->info('Email créé, envoi en cours...');
                    $mailer->send($email);
                    $logger->info('Email envoyé avec succès');

                    $this->addFlash('success', 'Votre message a bien été envoyé.');
                    return $this->redirectToRoute('app_home');
                } catch(\Exception $e) {
                    $logger->error('Erreur lors de l\'envoi', ['exception' => $e->getMessage()]);
                    $this->addFlash('danger', 'Un problème technique est survenu lors de l\'envoi du mail : ' . $e->getMessage());
                }
            } else {
                $logger->warning('Formulaire invalide', [
                    'errors' => (string) $form->getErrors(true)
                ]);
                $this->addFlash('warning', 'Le formulaire contient des erreurs.');
            }
        }

        return $this->render('contact/contact.html.twig', [
            'contactForm' => $form,
        ]);
    }
}
