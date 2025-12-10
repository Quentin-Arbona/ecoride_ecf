<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un nouvel utilisateur admin.',
)]
class CreateAdminCommand extends Command
{
    protected static $defaultName = 'app:create-admin';

    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Demande l'email
        $email = $io->ask('Entrez l\'email de l\'admin', 'admin@example.com', fn(string $value) => trim($value));

        // Demande le pseudo
        $pseudo = $io->ask('Entrez le pseudo de l\'admin', 'admin', fn(string $value) => trim($value));

        // Demande le mot de passe (masqué)
        $password = $io->askHidden('Entrez le mot de passe de l\'admin', fn(string $value) => trim($value));

        // Crée l'utilisateur admin
        $admin = new User();
        $admin->setEmail($email);
        $admin->setPseudo($pseudo);
        $admin->setRoles([User::ROLE_ADMIN]);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, $password));

        $this->em->persist($admin);
        $this->em->flush();

        $io->success('Admin créé avec succès !');

        return Command::SUCCESS;
    }
}
