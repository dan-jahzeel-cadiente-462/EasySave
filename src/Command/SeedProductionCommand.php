<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:seed-production', description: 'Idempotently seed production data.')]
class SeedProductionCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $usersToSeed = [
            ['username' => 'admin1', 'email' => 'admin@easysave.com', 'roles' => ['ROLE_ADMIN', 'ROLE_STAFF'], 'password' => 'adminpass1'],
            ['username' => 'admin2', 'email' => 'admin2@easysave.com', 'roles' => ['ROLE_ADMIN'], 'password' => 'adminpass2'],
            ['username' => 'staff1', 'email' => 'staff@easysave.com', 'roles' => ['ROLE_STAFF'], 'password' => 'staffpass1'],
            ['username' => 'user1',  'email' => 'user@easysave.com',  'roles' => ['ROLE_USER'], 'password' => 'userpass1'],
        ];

        foreach ($usersToSeed as $userData) {
            $user = $this->userRepository->findOneBy(['username' => $userData['username']]);
            
            if (!$user) {
                $io->note("Creating user: " . $userData['username']);
                $user = new User();
                $user->setUsername($userData['username']);
                $user->setEmail($userData['email']);
                $user->setRoles($userData['roles']);
                $user->setIsVerified(true);
                
                $user->setPassword($this->hasher->hashPassword($user, $userData['password']));
                
                $this->entityManager->persist($user);
            } else {
                $io->note("User already exists, skipping: " . $userData['username']);
            }
        }

        $this->entityManager->flush();
        $io->success('Production seed completed.');
        return Command::SUCCESS;
    }
}
