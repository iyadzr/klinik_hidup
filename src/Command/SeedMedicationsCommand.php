<?php

namespace App\Command;

use App\Entity\Medication;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-medications',
    description: 'Seed the database with consolidated medication list',
)]
class SeedMedicationsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $medications = [
            // Pain Relief & Anti-inflammatory
            [
                'name' => 'Paracetamol',
                'unitType' => 'tablets',
                'unitDescription' => '500mg tablets',
                'category' => 'Pain Relief',
                'description' => 'For pain relief and fever reduction'
            ],
            [
                'name' => 'Ibuprofen',
                'unitType' => 'tablets',
                'unitDescription' => '400mg tablets',
                'category' => 'Pain Relief',
                'description' => 'Non-steroidal anti-inflammatory drug (NSAID) for pain and inflammation'
            ],
            [
                'name' => 'Aspirin',
                'unitType' => 'tablets',
                'unitDescription' => '100mg tablets',
                'category' => 'Pain Relief',
                'description' => 'Low-dose aspirin for cardiovascular protection and pain relief'
            ],

            // Antibiotics
            [
                'name' => 'Amoxicillin',
                'unitType' => 'capsules',
                'unitDescription' => '500mg capsules',
                'category' => 'Antibiotics',
                'description' => 'Penicillin antibiotic for bacterial infections'
            ],
            [
                'name' => 'Azithromycin',
                'unitType' => 'tablets',
                'unitDescription' => '500mg tablets',
                'category' => 'Antibiotics',
                'description' => 'Macrolide antibiotic for respiratory and skin infections'
            ],

            // Respiratory Medications
            [
                'name' => 'Salbutamol Inhaler',
                'unitType' => 'inhaler',
                'unitDescription' => '100mcg/dose inhaler',
                'category' => 'Respiratory',
                'description' => 'Short-acting beta agonist for asthma and COPD'
            ],

            // Cough & Cold
            [
                'name' => 'Dextromethorphan Syrup',
                'unitType' => 'bottles',
                'unitDescription' => '100ml bottle',
                'category' => 'Cough & Cold',
                'description' => 'Cough suppressant syrup for dry cough'
            ],
            [
                'name' => 'Loratadine',
                'unitType' => 'tablets',
                'unitDescription' => '10mg tablets',
                'category' => 'Antihistamine',
                'description' => 'Non-drowsy antihistamine for allergies'
            ],
            [
                'name' => 'Prospan Cough Syrup',
                'unitType' => 'bottles',
                'unitDescription' => '100ml bottle',
                'category' => 'Cough & Cold',
                'description' => 'Natural ivy leaf extract cough syrup'
            ],

            // Gastrointestinal
            [
                'name' => 'Omeprazole',
                'unitType' => 'capsules',
                'unitDescription' => '20mg capsules',
                'category' => 'Gastrointestinal',
                'description' => 'Proton pump inhibitor for acid-related disorders'
            ],
            [
                'name' => 'Gaviscon',
                'unitType' => 'sachets',
                'unitDescription' => '10ml sachets',
                'category' => 'Gastrointestinal',
                'description' => 'Relief from heartburn and indigestion'
            ],

            // Topical Medications
            [
                'name' => 'Hydrocortisone Cream',
                'unitType' => 'tubes',
                'unitDescription' => '15g tube',
                'category' => 'Topical',
                'description' => '1% hydrocortisone anti-inflammatory cream'
            ],
            [
                'name' => 'Betadine Solution',
                'unitType' => 'bottles',
                'unitDescription' => '60ml bottle',
                'category' => 'Topical',
                'description' => 'Povidone-iodine antiseptic solution'
            ],

            // Oral Care
            [
                'name' => 'Chlorhexidine Mouthwash',
                'unitType' => 'bottles',
                'unitDescription' => '200ml bottle',
                'category' => 'Oral Care',
                'description' => 'Antibacterial mouthwash'
            ],

            // Emergency/Common Use
            [
                'name' => 'Glucose Tablets',
                'unitType' => 'tablets',
                'unitDescription' => '4g dextrose tablets',
                'category' => 'Diabetes',
                'description' => 'Fast-acting glucose for hypoglycemia'
            ]
        ];

        $io->progressStart(count($medications));

        foreach ($medications as $medData) {
            // Check if medication already exists
            $existingMed = $this->entityManager->getRepository(Medication::class)
                ->findOneBy(['name' => $medData['name']]);

            if (!$existingMed) {
                $medication = new Medication();
                $medication->setName($medData['name']);
                $medication->setUnitType($medData['unitType']);
                $medication->setUnitDescription($medData['unitDescription']);
                $medication->setCategory($medData['category']);
                $medication->setDescription($medData['description']);

                $this->entityManager->persist($medication);
            }

            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();

        $io->success('Consolidated medications seeded successfully!');
        $io->note([
            'Total medications: ' . count($medications),
            'Categories included: Pain Relief, Antibiotics, Respiratory, Cough & Cold, Gastrointestinal, Topical, Oral Care, Diabetes',
            'All duplicates have been removed'
        ]);

        return Command::SUCCESS;
    }
} 