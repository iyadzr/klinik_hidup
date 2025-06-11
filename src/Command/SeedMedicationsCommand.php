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
    description: 'Seed the database with common medications',
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
            [
                'name' => 'Panadol',
                'unitType' => 'pieces',
                'unitDescription' => '500mg tablets',
                'category' => 'pain reliever',
                'description' => 'Pain relief and fever reducer'
            ],
            [
                'name' => 'Prospan Cough Syrup',
                'unitType' => 'bottles',
                'unitDescription' => '100ml bottle',
                'category' => 'cough syrup',
                'description' => 'Natural cough relief syrup'
            ],
            [
                'name' => 'Amoxicillin',
                'unitType' => 'pieces',
                'unitDescription' => '250mg capsules',
                'category' => 'antibiotic',
                'description' => 'Broad-spectrum antibiotic'
            ],
            [
                'name' => 'Loratadine',
                'unitType' => 'pieces',
                'unitDescription' => '10mg tablets',
                'category' => 'antihistamine',
                'description' => 'Allergy relief medication'
            ],
            [
                'name' => 'Gaviscon',
                'unitType' => 'sachets',
                'unitDescription' => '10ml sachets',
                'category' => 'antacid',
                'description' => 'Relief from heartburn and indigestion'
            ],
            [
                'name' => 'Betadine Solution',
                'unitType' => 'bottles',
                'unitDescription' => '60ml bottle',
                'category' => 'antiseptic',
                'description' => 'Topical antiseptic solution'
            ],
            [
                'name' => 'Ventolin Inhaler',
                'unitType' => 'pieces',
                'unitDescription' => '100mcg inhaler',
                'category' => 'bronchodilator',
                'description' => 'Relief for asthma and breathing difficulties'
            ],
            [
                'name' => 'Omeprazole',
                'unitType' => 'pieces',
                'unitDescription' => '20mg capsules',
                'category' => 'proton pump inhibitor',
                'description' => 'Reduces stomach acid production'
            ],
            [
                'name' => 'Dextromethorphan Syrup',
                'unitType' => 'bottles',
                'unitDescription' => '60ml bottle',
                'category' => 'cough suppressant',
                'description' => 'Dry cough relief syrup'
            ],
            [
                'name' => 'Ibuprofen',
                'unitType' => 'pieces',
                'unitDescription' => '400mg tablets',
                'category' => 'anti-inflammatory',
                'description' => 'Pain relief and anti-inflammatory'
            ],
            [
                'name' => 'Hydrocortisone Cream',
                'unitType' => 'tubes',
                'unitDescription' => '15g tube',
                'category' => 'topical steroid',
                'description' => 'Anti-inflammatory skin cream'
            ],
            [
                'name' => 'Chlorhexidine Mouthwash',
                'unitType' => 'bottles',
                'unitDescription' => '200ml bottle',
                'category' => 'oral antiseptic',
                'description' => 'Antibacterial mouthwash'
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

        $io->success('Medications seeded successfully!');

        return Command::SUCCESS;
    }
} 