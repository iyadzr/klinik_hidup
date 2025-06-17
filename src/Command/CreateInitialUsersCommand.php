<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-initial-users',
    description: 'Create initial users for the clinic management system',
)]
class CreateInitialUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $initialUsers = [
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'email' => 'dhiak@gmail.com',
                'password' => 'admin123',
                'roles' => ['ROLE_USER', 'ROLE_SUPER_ADMIN'],
                'allowedPages' => ['dashboard', 'queue', 'queue-display', 'consultations', 'financial', 'admin', 'settings']
            ],
            [
                'name' => 'Dr. John Doe',
                'username' => 'doctor',
                'email' => 'doctor@gmail.com',
                'password' => 'doctor123',
                'roles' => ['ROLE_USER', 'ROLE_DOCTOR'],
                'allowedPages' => ['dashboard', 'queue', 'queue-display', 'consultations']
            ],
            [
                'name' => 'Dr. Mat Hayat',
                'username' => 'mathayat',
                'email' => 'mat.hayat@ymail.com',
                'password' => 'doctor123',
                'roles' => ['ROLE_USER', 'ROLE_DOCTOR'],
                'allowedPages' => ['dashboard', 'queue', 'queue-display', 'consultations']
            ],
            [
                'name' => 'Clinic Assistant',
                'username' => 'assistant',
                'email' => 'assistant@gmail.com',
                'password' => 'assistant123',
                'roles' => ['ROLE_USER', 'ROLE_ASSISTANT'],
                'allowedPages' => ['dashboard', 'queue', 'queue-display', 'registration']
            ]
        ];

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($initialUsers as $userData) {
            // Check if user already exists
            $existingUser = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => $userData['email']]);

            if ($existingUser) {
                $io->warning("User with email {$userData['email']} already exists. Skipping.");
                $skippedCount++;
                continue;
            }

            $user = new User();
            $user->setName($userData['name']);
            $user->setUsername($userData['username']);
            $user->setEmail($userData['email']);
            $user->setRoles($userData['roles']);
            $user->setAllowedPages($userData['allowedPages']);
            $user->setIsActive(true);

            // Hash the password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $createdCount++;

            $io->success("Created user: {$userData['name']} ({$userData['email']})");
        }

        $this->entityManager->flush();

        $io->success("Initial users setup completed!");
        $io->info("Created: {$createdCount} users");
        $io->info("Skipped: {$skippedCount} users (already exist)");

        return Command::SUCCESS;
    }
} 