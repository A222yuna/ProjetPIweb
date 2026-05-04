<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: 'Create an admin user')]
final class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = 'azizcheikh@admin.com';
        $password = 'aziz123456789';
        $role = 'Admin';
        $prenom = 'Aziz';
        $nom = 'Cheikh';

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        
        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $output->writeln(sprintf('Creating user %s...', $email));
        } else {
            $output->writeln(sprintf('Updating user %s...', $email));
        }

        $user->setRole($role);
        $user->setPrenom($prenom);
        $user->setNom($nom);
        $user->setTelephone('000000000');
        $user->setDateInscription(new \DateTime());
        $user->setEstActif(true);
        $user->setEmailVerifie(true);
        $user->setStatutValidation('approuve');
        $user->setMotDePasse($this->hasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln(sprintf('Admin user %s created/updated successfully!', $email));

        return Command::SUCCESS;
    }
}
