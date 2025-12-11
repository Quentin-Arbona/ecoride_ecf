<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints AS Assert;

class ContactDTO
{
    #[Assert\Length(
        min: 5,
        max: 50,
        minMessage: 'Le nom est obligatoire avec un minimum de {{ limit }} caractères',
        maxMessage: 'Le nom dépasse {{ limit }} caractères',
    )]
    #[Assert\NotBlank(
        message: 'Le nom est obligatoire',
    )]
    public string $name = '';

    #[Assert\Email(
        message: 'L\'adresse email {{ value }} est invalide',
    )]
    #[Assert\NotBlank(
        message: 'L\'adresse email est obligatoire',
    )]
    public string $email = '';

    #[Assert\Length(
        min: 10,
        max: 500,
        minMessage: 'Le message est obligatoire avec un minimum de {{ limit }} caractères',
        maxMessage: 'Le message dépasse {{ limit }} caractères',
    )]
    #[Assert\NotBlank(
        message: 'Le message est obligatoire',
    )]
    public string $message = '';
}