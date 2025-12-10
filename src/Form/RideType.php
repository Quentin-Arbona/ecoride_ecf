<?php

namespace App\Form;

use App\Entity\Car;
use App\Entity\Ride;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security; 
use Symfony\Component\Validator\Constraints as Assert;

class RideType extends AbstractType
{
    public function __construct(private Security $security) 
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->security->getUser();

        $builder
            ->add('departurePlace', TextType::class, [
                'required' => true,
                'label' => 'Lieu de départ *',
                'attr' => ['placeholder' => 'Ex: Paris, Gare de Lyon'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le lieu de départ est obligatoire']),
                    new Assert\Length(['min' => 3, 'max' => 255])
                ]
            ])
            ->add('departureDate', DateType::class, [
                'required' => true,
                'label' => 'Date de départ *',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\GreaterThanOrEqual('today')
                ]
            ])
            ->add('departureTime', TimeType::class, [
                'required' => true,
                'label' => 'Heure de départ *',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
            ->add('arrivalPlace', TextType::class, [
                'required' => true,
                'label' => 'Lieu d\'arrivée *',
                'attr' => ['placeholder' => 'Ex: Lyon, Part-Dieu'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le lieu d\'arrivée est obligatoire']),
                    new Assert\Length(['min' => 3, 'max' => 255])
                ]
            ])
            ->add('arrivalDate', DateType::class, [
                'required' => true,
                'label' => 'Date d\'arrivée *',
                'widget' => 'single_text',
            ])
            ->add('arrivalTime', TimeType::class, [
                'required' => true,
                'label' => 'Heure d\'arrivée estimée *',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
            ->add('car', EntityType::class, [
                'required' => true,
                'label' => 'Votre véhicule *',
                'class' => Car::class,
                'choice_label' => function (Car $car) {
                    return sprintf('%s %s (%s)', $car->getBrand(), $car->getModel(), $car->getLicensePlate());
                },
                'query_builder' => function ($repository) use ($user) {
                    return $repository->createQueryBuilder('c')
                        ->where('c.owner = :user')
                        ->andWhere('c.isActive = true')
                        ->setParameter('user', $user)
                        ->orderBy('c.brand', 'ASC');
                },
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner un véhicule'])
                ]
            ])
            ->add('availableSeats', IntegerType::class, [
                'required' => true,
                'label' => 'Nombre de places disponibles *',
                'attr' => ['min' => 1, 'max' => 8],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range(['min' => 1, 'max' => 8])
                ]
            ])
            ->add('pricePerSeat', IntegerType::class, [
                'required' => true,
                'label' => 'Prix par place (crédits) *',
                'attr' => [
                    'min' => 5, // Minimum 5 crédits (3 pour passager + 2 pour plateforme)
                    'placeholder' => 'Ex: 20'
                ],
                'help' => '2 crédits seront prélevés par la plateforme',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prix est obligatoire']),
                    new Assert\Range([
                        'min' => 5,
                        'max' => 200,
                        'notInRangeMessage' => 'Le prix minimum est de 5 crédits (dont 2 pour la plateforme)'
                    ])
                ]
            ])
            ->add('estimatedDuration', IntegerType::class, [
                'label' => 'Durée estimée (minutes) *',
                'required' => true,
                'attr' => [
                    'min' => 1,
                    'placeholder' => 'Ex: 120',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez indiquer la durée estimée']),
                ],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Informations complémentaires',
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Ex: Je pars de Paris 15e, possibilité de déposer en chemin...'
                ]
            ]);
            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ride::class,
        ]);
    }
}