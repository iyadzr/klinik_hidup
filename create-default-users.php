<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Entity\User;
use App\Kernel;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

// Boot Symfony kernel
$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();

// Get services
$entityManager = $container->get('doctrine.orm.entity_manager');
$passwordHasher = $container->get(UserPasswordHasherInterface::class);

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

echo "Creating default users...\n";
echo "========================\n";

foreach ($defaultUsers as $userData) {
    try {
        // Check if user already exists
        $existingUser = $entityManager->getRepository(User::class)
            ->findOneBy(['username' => $userData['username']]);
        
        if ($existingUser) {
            echo "❌ User '{$userData['username']}' already exists. Skipping.\n";
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
        $hashedPassword = $passwordHasher->hashPassword($user, $userData['password']);
        $user->setPassword($hashedPassword);
        
        // Save to database
        $entityManager->persist($user);
        $entityManager->flush();
        
        echo "✅ Created user: {$userData['username']} ({$userData['name']})\n";
        echo "   Email: {$userData['email']}\n";
        echo "   Roles: " . implode(', ', $userData['roles']) . "\n";
        echo "   Password: {$userData['password']}\n\n";
        
    } catch (\Exception $e) {
        echo "❌ Error creating user '{$userData['username']}': " . $e->getMessage() . "\n\n";
    }
}

echo "Default user creation completed!\n";
echo "================================\n";
echo "Login credentials:\n";
echo "• Super Admin: superadmin / password\n";
echo "• Doctor: doctor / password\n";
echo "• Assistant: assistant / password\n";
echo "\nLogin URL: /api/login\n";
echo "Use username OR email field for login.\n";