<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:seed:test-users', description: 'Create test users with hashed passwords')]
final class SeedTestUsersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rows = [
            ['admin@psy.local', 'Admin', 'Admin', 'User', 'AdminPass123!'],
            ['psy@psy.local', 'Psychologue', 'Psy', 'User', 'PsyPass123!'],
            ['patient@psy.local', 'Patient', 'Patient', 'User', 'PatientPass123!'],
        ];

        foreach ($rows as [$email, $role, $prenom, $nom, $plainPassword]) {
            $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]) ?? new User();
            $user->setEmail($email);
            $user->setRole($role);
            $user->setPrenom($prenom);
            $user->setNom($nom);
            $user->setTelephone('000000000');
            $user->setDateInscription(new \DateTime());
            $user->setEstActif(true);
            $user->setEmailVerifie(true);
            $user->setStatutValidation('approuve');
            $user->setMotDePasse($this->hasher->hashPassword($user, $plainPassword));
            $this->em->persist($user);
        }

        $this->em->flush();
        $output->writeln('Test users ready: admin@psy.local / psy@psy.local / patient@psy.local');

        return Command::SUCCESS;
    }
}
