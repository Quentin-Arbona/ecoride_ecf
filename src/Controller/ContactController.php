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


final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function contact( MailerInterface $mailer, Request $request) : Response
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
        $form-> handleRequest($request);

        if( $form->isSubmitted() && $form->isValid() ) {

            try {
                $data = $form-> getData();
                $email = (new TemplatedEmail())
                    ->from(new Address($data->email, $data->name))
                    ->to('webmaster@formation.studi')
                    ->bcc('bcc@example.com')
                    ->subject('Contact depuis le site Garden Time')
                    ->htmlTemplate('emails/contact.html.twig')
                    ->locale('fr')
                    // pass varables name => value to the template
                    ->context([  
                        'name' => $data->name,
                        'email' => $data->email,
                        'message' => $data->message,
                    ]);
            
                $mailer->send($email);
                $this->addFlash('success', 'Votre message a bien été envoyé.');
                return $this->redirectToRoute('home');
            } catch(\Exception $e) {
                $this->addFlash('alert', 'Un problème technique est survenu lors de l\'envoi du mail');
            }


        }

        return $this->render('contact/contact.html.twig', [
            'contactForm' => $form,
        ]);
    }
}
