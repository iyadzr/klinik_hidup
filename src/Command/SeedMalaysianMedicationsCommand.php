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
    name: 'app:seed-malaysian-medications',
    description: 'Seed additional medications commonly used in Malaysian medical practice',
)]
class SeedMalaysianMedicationsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // BATCH 1: Popular Malaysian Pain Relief Brands
        $batch1 = [
            ['name' => 'Panadol Extra', 'unitType' => 'tablets', 'unitDescription' => 'Paracetamol 500mg + Caffeine 65mg', 'category' => 'Pain Relief', 'description' => 'Enhanced paracetamol with caffeine', 'costPrice' => '0.25', 'sellingPrice' => '0.80'],
            ['name' => 'Panadol Actifast', 'unitType' => 'tablets', 'unitDescription' => '500mg soluble tablets', 'category' => 'Pain Relief', 'description' => 'Fast-acting soluble paracetamol', 'costPrice' => '0.35', 'sellingPrice' => '1.00'],
            ['name' => 'Voltaren Gel', 'unitType' => 'tubes', 'unitDescription' => '20g tube (1% diclofenac)', 'category' => 'Topical', 'description' => 'Topical NSAID gel for muscle pain', 'costPrice' => '8.50', 'sellingPrice' => '18.00'],
            ['name' => 'Diclofenac Sodium', 'unitType' => 'tablets', 'unitDescription' => '50mg tablets', 'category' => 'Pain Relief', 'description' => 'NSAID for moderate to severe pain', 'costPrice' => '0.25', 'sellingPrice' => '0.80'],
            ['name' => 'Mefenamic Acid', 'unitType' => 'tablets', 'unitDescription' => '500mg tablets', 'category' => 'Pain Relief', 'description' => 'NSAID for menstrual pain', 'costPrice' => '0.20', 'sellingPrice' => '0.60']
        ];

        // BATCH 2: Malaysian Cough & Cold Favorites  
        $batch2 = [
            ['name' => 'Woods Peppermint Cure', 'unitType' => 'bottles', 'unitDescription' => '50ml bottle', 'category' => 'Cough & Cold', 'description' => 'Popular Malaysian cough syrup', 'costPrice' => '4.50', 'sellingPrice' => '12.00'],
            ['name' => 'Strepsils Lozenges', 'unitType' => 'lozenges', 'unitDescription' => '24 lozenges', 'category' => 'Cough & Cold', 'description' => 'Antiseptic throat lozenges', 'costPrice' => '6.00', 'sellingPrice' => '15.00'],
            ['name' => 'Benadryl Cough Syrup', 'unitType' => 'bottles', 'unitDescription' => '100ml bottle', 'category' => 'Cough & Cold', 'description' => 'Diphenhydramine cough suppressant', 'costPrice' => '5.50', 'sellingPrice' => '14.00'],
            ['name' => 'Difflam Lozenges', 'unitType' => 'lozenges', 'unitDescription' => '20 lozenges', 'category' => 'Cough & Cold', 'description' => 'Anti-inflammatory throat lozenges', 'costPrice' => '8.00', 'sellingPrice' => '18.00']
        ];

        // BATCH 3: Tropical Climate Topicals
        $batch3 = [
            ['name' => 'Betadine Solution', 'unitType' => 'bottles', 'unitDescription' => '60ml bottle', 'category' => 'Topical', 'description' => 'Povidone-iodine antiseptic', 'costPrice' => '4.00', 'sellingPrice' => '9.00'],
            ['name' => 'Calamine Lotion', 'unitType' => 'bottles', 'unitDescription' => '100ml bottle', 'category' => 'Topical', 'description' => 'Soothing lotion for itchy skin', 'costPrice' => '2.50', 'sellingPrice' => '6.00'],
            ['name' => 'Fusidic Acid Cream', 'unitType' => 'tubes', 'unitDescription' => '15g tube (2%)', 'category' => 'Topical', 'description' => 'Topical antibiotic for skin infections', 'costPrice' => '12.00', 'sellingPrice' => '25.00'],
            ['name' => 'Zinc Oxide Cream', 'unitType' => 'tubes', 'unitDescription' => '50g tube', 'category' => 'Topical', 'description' => 'Protective barrier cream', 'costPrice' => '3.00', 'sellingPrice' => '7.00']
        ];

        // BATCH 4: Antifungals (Important for humidity)
        $batch4 = [
            ['name' => 'Clotrimazole Cream', 'unitType' => 'tubes', 'unitDescription' => '20g tube (1%)', 'category' => 'Antifungal', 'description' => 'Topical antifungal for athlete\'s foot', 'costPrice' => '4.50', 'sellingPrice' => '12.00'],
            ['name' => 'Ketoconazole Shampoo', 'unitType' => 'bottles', 'unitDescription' => '60ml bottle (2%)', 'category' => 'Antifungal', 'description' => 'Medicated shampoo for dandruff', 'costPrice' => '12.00', 'sellingPrice' => '25.00'],
            ['name' => 'Terbinafine Cream', 'unitType' => 'tubes', 'unitDescription' => '15g tube (1%)', 'category' => 'Antifungal', 'description' => 'Antifungal for dermatophyte infections', 'costPrice' => '8.50', 'sellingPrice' => '20.00']
        ];

        // BATCH 5: Enhanced Antihistamines
        $batch5 = [
            ['name' => 'Cetirizine', 'unitType' => 'tablets', 'unitDescription' => '10mg tablets', 'category' => 'Antihistamine', 'description' => 'Non-drowsy antihistamine', 'costPrice' => '0.25', 'sellingPrice' => '0.80'],
            ['name' => 'Fexofenadine', 'unitType' => 'tablets', 'unitDescription' => '120mg tablets', 'category' => 'Antihistamine', 'description' => 'Long-acting antihistamine', 'costPrice' => '1.20', 'sellingPrice' => '3.50'],
            ['name' => 'Cetirizine Syrup', 'unitType' => 'bottles', 'unitDescription' => '60ml bottle (5mg/5ml)', 'category' => 'Antihistamine', 'description' => 'Paediatric antihistamine syrup', 'costPrice' => '4.50', 'sellingPrice' => '12.00']
        ];

        // BATCH 6: Traditional Malaysian Medicine
        $batch6 = [
            ['name' => 'Minyak Angin Cap Kapak', 'unitType' => 'bottles', 'unitDescription' => '15ml bottle', 'category' => 'Traditional', 'description' => 'Traditional medicated oil', 'costPrice' => '3.50', 'sellingPrice' => '8.00'],
            ['name' => 'Minyak Gamat', 'unitType' => 'bottles', 'unitDescription' => '30ml bottle', 'category' => 'Traditional', 'description' => 'Sea cucumber oil for wounds', 'costPrice' => '15.00', 'sellingPrice' => '35.00'],
            ['name' => 'Kapsul Tongkat Ali', 'unitType' => 'capsules', 'unitDescription' => '500mg capsules', 'category' => 'Traditional', 'description' => 'Traditional herbal supplement', 'costPrice' => '2.00', 'sellingPrice' => '6.00']
        ];

        // BATCH 7: Vitamins (Very popular in Malaysia)
        $batch7 = [
            ['name' => 'Vitamin C 1000mg', 'unitType' => 'tablets', 'unitDescription' => '1000mg tablets', 'category' => 'Vitamins', 'description' => 'High-dose vitamin C', 'costPrice' => '0.15', 'sellingPrice' => '0.50'],
            ['name' => 'Calcium + Vitamin D', 'unitType' => 'tablets', 'unitDescription' => '600mg + 400IU', 'category' => 'Vitamins', 'description' => 'Combined calcium and vitamin D', 'costPrice' => '0.25', 'sellingPrice' => '0.80'],
            ['name' => 'Multivitamin Tablets', 'unitType' => 'tablets', 'unitDescription' => 'Complete multivitamin', 'category' => 'Vitamins', 'description' => 'Daily multivitamin supplement', 'costPrice' => '0.30', 'sellingPrice' => '1.00']
        ];

        // BATCH 8: Popular OTC Brands
        $batch8 = [
            ['name' => 'Gaviscon Advance', 'unitType' => 'sachets', 'unitDescription' => '10ml sachets', 'category' => 'Gastrointestinal', 'description' => 'Advanced heartburn relief', 'costPrice' => '1.20', 'sellingPrice' => '3.00'],
            ['name' => 'ENO Fruit Salt', 'unitType' => 'sachets', 'unitDescription' => '5g sachets', 'category' => 'Gastrointestinal', 'description' => 'Effervescent antacid', 'costPrice' => '0.40', 'sellingPrice' => '1.20'],
            ['name' => 'Dettol Antiseptic', 'unitType' => 'bottles', 'unitDescription' => '125ml bottle', 'category' => 'Antiseptic', 'description' => 'Multi-purpose antiseptic', 'costPrice' => '4.50', 'sellingPrice' => '12.00']
        ];

        // Combine all batches
        $allMedications = array_merge($batch1, $batch2, $batch3, $batch4, $batch5, $batch6, $batch7, $batch8);
        
        $io->progressStart(count($allMedications));
        $addedCount = 0;
        $skippedCount = 0;

        foreach ($allMedications as $medData) {
            $existingMed = $this->entityManager->getRepository(Medication::class)
                ->findOneBy(['name' => $medData['name']]);

            if (!$existingMed) {
                $medication = new Medication();
                $medication->setName($medData['name']);
                $medication->setUnitType($medData['unitType']);
                $medication->setUnitDescription($medData['unitDescription']);
                $medication->setCategory($medData['category']);
                $medication->setDescription($medData['description']);
                $medication->setCostPrice($medData['costPrice']);
                $medication->setSellingPrice($medData['sellingPrice']);

                $this->entityManager->persist($medication);
                $addedCount++;
            } else {
                $skippedCount++;
            }

            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();

        $io->success('Malaysian medications seeded successfully!');
        $io->note([
            'Total processed: ' . count($allMedications),
            'New medications added: ' . $addedCount,
            'Duplicates skipped: ' . $skippedCount,
            'Categories: Pain Relief, Cough & Cold, Topical, Antifungal, Antihistamine, Traditional, Vitamins, OTC',
            'Includes popular Malaysian brands with pricing'
        ]);

        return Command::SUCCESS;
    }
} 