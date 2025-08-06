<?php
// Debug password verification in Symfony

require_once '/var/www/html/vendor/autoload.php';

use App\Kernel;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();

$entityManager = $container->get(EntityManagerInterface::class);
$passwordHasher = $container->get(UserPasswordHasherInterface::class);

// Get the superadmin user
$user = $entityManager->getRepository(User::class)->findOneBy(['username' => 'superadmin']);

if (!$user) {
    echo "User not found!\n";
    exit(1);
}

echo "User found: " . $user->getUsername() . "\n";
echo "Stored hash: " . $user->getPassword() . "\n";
echo "Expected hash: \$2y\$13\$M8eHJ8VWWJPCdKKpOzZhKuLqYGSFnqf8Bs5TkGVmH0LWtVmKG3rZy\n";
echo "Hash match: " . ($user->getPassword() === '$2y$13$M8eHJ8VWWJPCdKKpOzZhKuLqYGSFnqf8Bs5TkGVmH0LWtVmKG3rZy' ? 'YES' : 'NO') . "\n";

// Test password verification
$testPassword = 'admin123';
$isValid = $passwordHasher->isPasswordValid($user, $testPassword);

echo "Password 'admin123' valid: " . ($isValid ? 'YES' : 'NO') . "\n";

// Test direct bcrypt verification
echo "Direct bcrypt verify: " . (password_verify($testPassword, $user->getPassword()) ? 'YES' : 'NO') . "\n";

// Check user roles
echo "User roles: " . json_encode($user->getRoles()) . "\n";
echo "User active: " . ($user->isActive() ? 'YES' : 'NO') . "\n";