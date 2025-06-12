<?php

namespace App\DataFixtures;

use App\Entity\Medication;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DefaultMedicationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $medications = [
            // Pain Relievers
            [
                'name' => 'Paracetamol',
                'unitType' => 'pieces',
                'unitDescription' => '500mg tablets',
                'category' => 'pain_reliever',
                'description' => 'Analgesic and antipyretic medication'
            ],
            [
                'name' => 'Ibuprofen',
                'unitType' => 'pieces',
                'unitDescription' => '400mg tablets',
                'category' => 'pain_reliever',
                'description' => 'Non-steroidal anti-inflammatory drug (NSAID)'
            ],
            [
                'name' => 'Aspirin',
                'unitType' => 'pieces',
                'unitDescription' => '100mg tablets',
                'category' => 'pain_reliever',
                'description' => 'Low-dose aspirin for cardiovascular protection'
            ],

            // Antibiotics
            [
                'name' => 'Amoxicillin',
                'unitType' => 'pieces',
                'unitDescription' => '250mg capsules',
                'category' => 'antibiotic',
                'description' => 'Broad-spectrum penicillin antibiotic'
            ],
            [
                'name' => 'Azithromycin',
                'unitType' => 'pieces',
                'unitDescription' => '250mg tablets',
                'category' => 'antibiotic',
                'description' => 'Macrolide antibiotic'
            ],
            [
                'name' => 'Cephalexin',
                'unitType' => 'pieces',
                'unitDescription' => '500mg capsules',
                'category' => 'antibiotic',
                'description' => 'First-generation cephalosporin antibiotic'
            ],

            // Cough & Cold
            [
                'name' => 'Dextromethorphan Syrup',
                'unitType' => 'bottles',
                'unitDescription' => '100ml bottle',
                'category' => 'cough_syrup',
                'description' => 'Cough suppressant syrup'
            ],
            [
                'name' => 'Loratadine',
                'unitType' => 'pieces',
                'unitDescription' => '10mg tablets',
                'category' => 'antihistamine',
                'description' => 'Non-drowsy antihistamine for allergies'
            ],
            [
                'name' => 'Cetirizine',
                'unitType' => 'pieces',
                'unitDescription' => '10mg tablets',
                'category' => 'antihistamine',
                'description' => 'Antihistamine for allergic reactions'
            ],

            // Gastrointestinal
            [
                'name' => 'Omeprazole',
                'unitType' => 'pieces',
                'unitDescription' => '20mg capsules',
                'category' => 'antacid',
                'description' => 'Proton pump inhibitor for acid reflux'
            ],
            [
                'name' => 'Simethicone',
                'unitType' => 'pieces',
                'unitDescription' => '40mg tablets',
                'category' => 'antacid',
                'description' => 'Anti-flatulent medication'
            ],
            [
                'name' => 'Loperamide',
                'unitType' => 'pieces',
                'unitDescription' => '2mg tablets',
                'category' => 'other',
                'description' => 'Anti-diarrheal medication'
            ],

            // Topical Medications
            [
                'name' => 'Hydrocortisone Cream',
                'unitType' => 'tubes',
                'unitDescription' => '15g tube',
                'category' => 'topical',
                'description' => '1% hydrocortisone anti-inflammatory cream'
            ],
            [
                'name' => 'Clotrimazole Cream',
                'unitType' => 'tubes',
                'unitDescription' => '20g tube',
                'category' => 'topical',
                'description' => 'Antifungal cream for skin infections'
            ],
            [
                'name' => 'Fusidic Acid Cream',
                'unitType' => 'tubes',
                'unitDescription' => '15g tube',
                'category' => 'topical',
                'description' => 'Topical antibiotic cream'
            ],

            // Eye & Ear Drops
            [
                'name' => 'Chloramphenicol Eye Drops',
                'unitType' => 'bottles',
                'unitDescription' => '10ml bottle',
                'category' => 'eye_drops',
                'description' => 'Antibiotic eye drops for bacterial infections'
            ],
            [
                'name' => 'Artificial Tears',
                'unitType' => 'bottles',
                'unitDescription' => '15ml bottle',
                'category' => 'eye_drops',
                'description' => 'Lubricating eye drops for dry eyes'
            ],

            // Vitamins & Supplements
            [
                'name' => 'Vitamin D3',
                'unitType' => 'pieces',
                'unitDescription' => '1000 IU tablets',
                'category' => 'vitamins',
                'description' => 'Vitamin D supplement'
            ],
            [
                'name' => 'Vitamin B Complex',
                'unitType' => 'pieces',
                'unitDescription' => 'Multi-B vitamin tablets',
                'category' => 'vitamins',
                'description' => 'B-vitamin complex supplement'
            ],
            [
                'name' => 'Folic Acid',
                'unitType' => 'pieces',
                'unitDescription' => '5mg tablets',
                'category' => 'vitamins',
                'description' => 'Folic acid supplement'
            ],

            // Emergency/Common Use
            [
                'name' => 'Adrenaline Auto-Injector',
                'unitType' => 'vials',
                'unitDescription' => '0.3mg EpiPen',
                'category' => 'other',
                'description' => 'Emergency epinephrine auto-injector'
            ],
            [
                'name' => 'Salbutamol Inhaler',
                'unitType' => 'other',
                'unitDescription' => '100mcg MDI',
                'category' => 'other',
                'description' => 'Bronchodilator inhaler for asthma'
            ],
            [
                'name' => 'Glucose Tablets',
                'unitType' => 'pieces',
                'unitDescription' => '4g dextrose tablets',
                'category' => 'other',
                'description' => 'Fast-acting glucose for hypoglycemia'
            ]
        ];

        foreach ($medications as $medicationData) {
            $medication = new Medication();
            $medication->setName($medicationData['name']);
            $medication->setUnitType($medicationData['unitType']);
            $medication->setUnitDescription($medicationData['unitDescription']);
            $medication->setCategory($medicationData['category']);
            $medication->setDescription($medicationData['description']);

            $manager->persist($medication);
        }

        $manager->flush();
    }
} 