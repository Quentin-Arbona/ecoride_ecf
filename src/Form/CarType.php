<?php

namespace App\Form;

use App\Entity\Car;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('brand', TextType::class, [
                'required' => true,
                'label' => 'Marque *',
                'attr' => ['placeholder' => 'Ex: Peugeot'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La marque est obligatoire']),
                    new Assert\Length(['min' => 2, 'max' => 20])
                ]
            ])
            ->add('model', TextType::class, [
                'required' => true,
                'label' => 'Modèle *',
                'attr' => ['placeholder' => 'Ex: 308'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le modèle est obligatoire']),
                    new Assert\Length(['min' => 1, 'max' => 50])
                ]
            ])
            ->add('color', TextType::class, [
                'required' => true,
                'label' => 'Couleur *',
                'attr' => ['placeholder' => 'Ex: Bleu'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La couleur est obligatoire']),
                    new Assert\Length(['min' => 2, 'max' => 20])
                ]
            ])
            ->add('licensePlate', TextType::class, [
                'required' => true,
                'label' => 'Plaque d\'immatriculation *',
                'attr' => ['placeholder' => 'AA-123-BB'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'immatriculation est obligatoire']),
                    new Assert\Regex([
                        'pattern' => '/^[A-Z]{2}-[0-9]{3}-[A-Z]{2}$/',
                        'message' => 'Format invalide (ex: AA-123-BB)'
                    ])
                ]
            ])
            ->add('seats', ChoiceType::class, [
                'required' => true,
                'label' => 'Nombre de places disponibles *',
                'choices' => [
                    '1 place' => 1,
                    '2 places' => 2,
                    '3 places' => 3,
                    '4 places' => 4,
                    '5 places' => 5,
                    '6 places' => 6,
                    '7 places' => 7,
                    '8 places' => 8,
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range(['min' => 1, 'max' => 8])
                ]
            ])
            ->add('year', IntegerType::class, [
                'required' => false,
                'label' => 'Année',
                'attr' => [
                    'min' => 1950,
                    'max' => date('Y') + 1,
                    'placeholder' => 'Ex: 2020'
                ],
                'constraints' => [
                    new Assert\Range([
                        'min' => 1950,
                        'max' => date('Y') + 1
                    ])
                ]
            ])
            ->add('isElectric', CheckboxType::class, [
                'required' => false,
                'label' => 'Véhicule électrique',
                'help' => 'Les trajets en véhicule électrique sont marqués comme écologiques'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Car::class,
        ]);
    }
}