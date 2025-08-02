<?php
// Reset all user passwords to 'password'
require_once 'vendor/autoload.php';

use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;

$factory = new PasswordHasherFactory([
    'common' => ['algorithm' => 'bcrypt'],
]);

$hasher = $factory->getPasswordHasher('common');
$hash = $hasher->hash('password');

echo "Password hash for 'password': " . $hash . "\n\n";
echo "SQL to reset ALL user passwords:\n";
echo "UPDATE user SET password = '$hash';\n";