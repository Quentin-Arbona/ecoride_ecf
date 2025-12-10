<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints AS Assert;

class ContactDTO
{
    #[Assert\length(
        min: 5,
        max: 50,
        minMessage: 'Le nom est obligatoire avec un minimum de {{ limit }}',
        maxMessage: 'Le nom dépasse {{ limit }} caractères',
    )]
    public string $name = '';

    #[Assert\Email(
        message: 'L\'adresse email {{ value }} est invalide',
    )]
    #[Assert\NotBlank(
        message: 'L\'adresse email est obligatoire',
    )]
    public string $email = '';

    #[Assert\length(
        min: '10',
        max: '100',
        minMessage: 'Le message est obligatoire avec un minimum de {{ limit }} caractères',
        maxMessage: 'Le message dépasse {{ limit }} caractères',
    )]
    public string $message = '';
}