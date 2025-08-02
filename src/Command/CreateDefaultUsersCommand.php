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
    name: 'app:create-default-users',
    description: 'Create default users for the clinic management system',
)]
class CreateDefaultUsersCommand extends Command
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

        $io->title('Creating Default Users for Clinic Management System');

        // Default users configuration
        $defaultUsers = [
            [
                'username' => 'superadmin',
                'email' => 'superadmin@clinic.com',
                'name' => 'Super Admin',
                'password' => 'password',
                'roles' => ['ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_USER'],
                'allowedPages' => [
                    'dashboard', 'patients', 'doctors', 'queue', 'consultations', 
                    'payments', 'medicines', 'users', 'settings', 'reports'
                ]
            ],
            [
                'username' => 'doctor',
                'email' => 'doctor@clinic.com',
                'name' => 'Dr. Default',
                'password' => 'password',
                'roles' => ['ROLE_DOCTOR', 'ROLE_USER'],
                'allowedPages' => [
                    'dashboard', 'queue', 'consultations', 'patients', 'medicines'
                ]
            ],
            [
                'username' => 'assistant',
                'email' => 'assistant@clinic.com',
                'name' => 'Clinic Assistant',
                'password' => 'password',
                'roles' => ['ROLE_ASSISTANT', 'ROLE_USER'],
                'allowedPages' => [
                    'dashboard', 'queue', 'patients', 'payments'
                ]
            ]
        ];

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($defaultUsers as $userData) {
            try {
                // Check if user already exists
                $existingUser = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['username' => $userData['username']]);
                
                if ($existingUser) {
                    $io->warning("User '{$userData['username']}' already exists. Skipping.");
                    $skippedCount++;
                    continue;
                }

                // Create new user
                $user = new User();
                $user->setUsername($userData['username']);
                $user->setEmail($userData['email']);
                $user->setName($userData['name']);
                $user->setRoles($userData['roles']);
                $user->setAllowedPages($userData['allowedPages']);
                $user->setIsActive(true);
                $user->setCreatedAt(new \DateTimeImmutable());
                $user->setUpdatedAt(new \DateTimeImmutable());
                
                // Hash password
                $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
                $user->setPassword($hashedPassword);
                
                // Save to database
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                
                $io->success("Created user: {$userData['username']} ({$userData['name']})");
                $io->text([
                    "  Email: {$userData['email']}",
                    "  Roles: " . implode(', ', $userData['roles']),
                    "  Password: {$userData['password']}"
                ]);
                
                $createdCount++;
                
            } catch (\Exception $e) {
                $io->error("Error creating user '{$userData['username']}': " . $e->getMessage());
            }
        }

        $io->newLine();
        $io->section('Summary');
        $io->text([
            "✅ Created: $createdCount users",
            "⚠️  Skipped: $skippedCount users (already exist)",
        ]);

        if ($createdCount > 0) {
            $io->newLine();
            $io->section('Login Credentials');
            $io->text([
                '• Super Admin: superadmin / password',
                '• Doctor: doctor / password', 
                '• Assistant: assistant / password'
            ]);
            
            $io->newLine();
            $io->section('Test Login');
            $io->text([
                'curl -X POST http://localhost:8090/api/login \\',
                '  -H "Content-Type: application/json" \\',
                '  -d \'{"username":"superadmin","password":"password"}\''
            ]);
        }

        return Command::SUCCESS;
    }
}