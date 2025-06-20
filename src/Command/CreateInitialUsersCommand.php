<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Doctor;
use App\Entity\Patient;
use App\Entity\ClinicAssistant;
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
        $io->title('Creating Initial Users');

        try {
            // Create users
            $this->createUsers($io);
            
            // Create test patients
            $this->createTestPatients($io);

            $io->success('Initial users and test patients created successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createUsers(SymfonyStyle $io): void
    {
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
    }

    private function createTestPatients(SymfonyStyle $io): void
    {
        $io->section('Creating Test Patients');
        
        $progressBar = $io->createProgressBar(1000);
        $progressBar->start();

        $firstNames = [
            'Ahmad', 'Siti', 'Mohammad', 'Nurul', 'Abdul', 'Fatimah', 'Ismail', 'Aminah',
            'Hassan', 'Zainab', 'Omar', 'Mariam', 'Ali', 'Khadijah', 'Yusuf', 'Aisha',
            'Ibrahim', 'Hajar', 'Adam', 'Eve', 'Noah', 'Sarah', 'Abraham', 'Rebecca',
            'Isaac', 'Rachel', 'Jacob', 'Leah', 'Joseph', 'Asenath', 'Moses', 'Zipporah',
            'David', 'Bathsheba', 'Solomon', 'Naamah', 'Elijah', 'Jezebel', 'Elisha', 'Huldah',
            'Isaiah', 'Prophetess', 'Jeremiah', 'Baruch', 'Ezekiel', 'Daniel', 'Hosea', 'Gomer',
            'Joel', 'Amos', 'Obadiah', 'Jonah', 'Micah', 'Nahum', 'Habakkuk', 'Zephaniah',
            'Haggai', 'Zechariah', 'Malachi', 'Matthew', 'Mark', 'Luke', 'John', 'Peter',
            'Paul', 'James', 'Jude', 'Timothy', 'Titus', 'Philemon', 'Hebrews', 'Revelation'
        ];

        $lastNames = [
            'Abdullah', 'Ahmad', 'Ali', 'Hassan', 'Hussein', 'Ibrahim', 'Ismail', 'Mahmud',
            'Mohammad', 'Mustafa', 'Omar', 'Osman', 'Rashid', 'Salim', 'Tariq', 'Umar',
            'Wahid', 'Yusuf', 'Zain', 'Zakaria', 'Zulfikar', 'Amin', 'Aziz', 'Bakar',
            'Fadil', 'Farid', 'Ghani', 'Hadi', 'Hakim', 'Hamid', 'Haris', 'Hasan',
            'Hashim', 'Hisham', 'Idris', 'Imran', 'Irfan', 'Jabir', 'Jalal', 'Jamal',
            'Jamil', 'Kamil', 'Karim', 'Khalid', 'Khalil', 'Latif', 'Mahdi', 'Malik',
            'Mansur', 'Marwan', 'Masud', 'Muhammad', 'Mukhtar', 'Munir', 'Musa', 'Nadir',
            'Naji', 'Nasir', 'Nazim', 'Nazir', 'Nizam', 'Nur', 'Qadir', 'Qasim',
            'Rafi', 'Rahim', 'Rashid', 'Rauf', 'Raza', 'Ridwan', 'Sabri', 'Sadiq',
            'Safwan', 'Sahil', 'Salih', 'Samir', 'Sami', 'Saqib', 'Sari', 'Shahid',
            'Shakir', 'Shams', 'Sharif', 'Shaukat', 'Shaykh', 'Sulaiman', 'Tahir', 'Talha',
            'Tariq', 'Tawfiq', 'Ubaid', 'Umar', 'Uthman', 'Wahid', 'Wajid', 'Wali',
            'Yahya', 'Yasin', 'Yusuf', 'Zafar', 'Zahid', 'Zain', 'Zakariya', 'Zaman'
        ];

        $genders = ['M', 'F'];
        $companies = [
            'Petronas', 'Maybank', 'CIMB Bank', 'Tenaga Nasional', 'Telekom Malaysia',
            'Sime Darby', 'IOI Corporation', 'Genting Group', 'YTL Corporation', 'KLCC',
            'Google Malaysia', 'Microsoft Malaysia', 'Intel Malaysia', 'Dell Malaysia',
            'HP Malaysia', 'IBM Malaysia', 'Oracle Malaysia', 'SAP Malaysia', 'Salesforce',
            'Shopee Malaysia', 'Lazada Malaysia', 'Grab Malaysia', 'Foodpanda Malaysia',
            'AirAsia', 'Malaysia Airlines', 'Firefly', 'Batik Air', 'Malindo Air',
            'McDonald\'s Malaysia', 'KFC Malaysia', 'Pizza Hut Malaysia', 'Domino\'s Malaysia',
            'Starbucks Malaysia', 'Coffee Bean Malaysia', 'Old Town White Coffee',
            'Baskin Robbins Malaysia', 'Haagen-Dazs Malaysia', 'Ben & Jerry\'s Malaysia',
            'Nestle Malaysia', 'Unilever Malaysia', 'Procter & Gamble Malaysia',
            'Johnson & Johnson Malaysia', 'Pfizer Malaysia', 'GlaxoSmithKline Malaysia',
            'AstraZeneca Malaysia', 'Novartis Malaysia', 'Roche Malaysia', 'Merck Malaysia',
            'Bayer Malaysia', 'Sanofi Malaysia', 'Takeda Malaysia', 'Eli Lilly Malaysia'
        ];

        $specializations = [
            'Cardiology', 'Dermatology', 'Endocrinology', 'Gastroenterology', 'Hematology',
            'Infectious Disease', 'Nephrology', 'Neurology', 'Oncology', 'Ophthalmology',
            'Orthopedics', 'Otolaryngology', 'Psychiatry', 'Pulmonology', 'Rheumatology',
            'Urology', 'General Practice', 'Family Medicine', 'Internal Medicine',
            'Emergency Medicine', 'Anesthesiology', 'Radiology', 'Pathology', 'Pediatrics',
            'Obstetrics & Gynecology', 'Surgery', 'Plastic Surgery', 'Neurosurgery',
            'Cardiothoracic Surgery', 'Vascular Surgery', 'Orthopedic Surgery'
        ];

        for ($i = 1; $i <= 1000; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $name = $firstName . ' ' . $lastName;
            $gender = $genders[array_rand($genders)];
            
            // Generate realistic Malaysian NRIC
            $year = rand(1950, 2005);
            $month = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
            $day = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
            $stateCode = str_pad(rand(1, 16), 2, '0', STR_PAD_LEFT);
            $sequence = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $nric = $year . $month . $day . '-' . $stateCode . '-' . $sequence;
            
            // Generate phone number
            $phone = '01' . rand(10000000, 99999999);
            
            // Generate email
            $email = strtolower($firstName . '.' . $lastName . rand(1, 999) . '@example.com');
            
            // Generate date of birth
            $dob = new \DateTime();
            $dob->setDate($year, rand(1, 12), rand(1, 28));
            
            // Generate address
            $addresses = [
                'No. ' . rand(1, 999) . ', Jalan ' . $lastName . ', Taman ' . $firstName . ', ' . rand(50000, 99999) . ' Kuala Lumpur',
                'Lot ' . rand(1, 999) . ', Kampung ' . $lastName . ', ' . rand(50000, 99999) . ' Selangor',
                'Unit ' . rand(1, 999) . ', Blok ' . chr(rand(65, 90)) . ', ' . rand(50000, 99999) . ' Penang',
                'No. ' . rand(1, 999) . ', Taman ' . $firstName . ', ' . rand(50000, 99999) . ' Johor Bahru',
                'Lot ' . rand(1, 999) . ', Bandar ' . $lastName . ', ' . rand(50000, 99999) . ' Melaka'
            ];
            $address = $addresses[array_rand($addresses)];
            
            // Generate company
            $company = $companies[array_rand($companies)];
            
            // Generate medical history
            $medicalConditions = [
                'Hypertension', 'Diabetes', 'Asthma', 'Heart disease', 'Arthritis',
                'Depression', 'Anxiety', 'Migraine', 'Allergies', 'High cholesterol',
                'Obesity', 'Thyroid disorder', 'Kidney disease', 'Liver disease',
                'Cancer', 'Stroke', 'Epilepsy', 'Tuberculosis', 'HIV/AIDS', 'None'
            ];
            $medicalHistory = $medicalConditions[array_rand($medicalConditions)];
            
            // Generate pre-informed illness
            $symptoms = [
                'Fever and cough', 'Headache', 'Stomach pain', 'Back pain', 'Joint pain',
                'Chest pain', 'Shortness of breath', 'Dizziness', 'Nausea', 'Vomiting',
                'Diarrhea', 'Constipation', 'Rash', 'Itching', 'Swelling',
                'Fatigue', 'Insomnia', 'Anxiety', 'Depression', 'Memory problems'
            ];
            $preInformedIllness = $symptoms[array_rand($symptoms)];

            $patient = new Patient();
            $patient->setName($name);
            $patient->setNric($nric);
            $patient->setEmail($email);
            $patient->setPhone($phone);
            $patient->setDateOfBirth($dob);
            $patient->setGender($gender);
            $patient->setAddress($address);
            $patient->setCompany($company);
            $patient->setMedicalHistory($medicalHistory);
            $patient->setPreInformedIllness($preInformedIllness);

            $this->entityManager->persist($patient);
            
            // Flush every 100 patients to avoid memory issues
            if ($i % 100 === 0) {
                $this->entityManager->flush();
                $progressBar->advance(100);
            }
        }

        $this->entityManager->flush();
        $progressBar->finish();
        $io->newLine(2);
        $io->info('Created 1000 test patients successfully!');
    }
} 