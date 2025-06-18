<?php

namespace App\DataFixtures;

use App\Entity\Medication;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ConsolidatedMedicationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
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
            [
                'name' => 'Diclofenac',
                'unitType' => 'tablets',
                'unitDescription' => '50mg tablets',
                'category' => 'Pain Relief',
                'description' => 'NSAID for pain and inflammation'
            ],
            [
                'name' => 'Naproxen',
                'unitType' => 'tablets',
                'unitDescription' => '500mg tablets',
                'category' => 'Pain Relief',
                'description' => 'NSAID for pain and inflammation'
            ],
            [
                'name' => 'Mefenamic Acid',
                'unitType' => 'capsules',
                'unitDescription' => '500mg capsules',
                'category' => 'Pain Relief',
                'description' => 'NSAID particularly effective for menstrual pain'
            ],
            [
                'name' => 'Tramadol',
                'unitType' => 'tablets',
                'unitDescription' => '50mg tablets',
                'category' => 'Pain Relief',
                'description' => 'Opioid analgesic for moderate to severe pain'
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
            [
                'name' => 'Ciprofloxacin',
                'unitType' => 'tablets',
                'unitDescription' => '500mg tablets',
                'category' => 'Antibiotics',
                'description' => 'Fluoroquinolone antibiotic for various infections'
            ],
            [
                'name' => 'Doxycycline',
                'unitType' => 'capsules',
                'unitDescription' => '100mg capsules',
                'category' => 'Antibiotics',
                'description' => 'Tetracycline antibiotic for various infections'
            ],
            [
                'name' => 'Clarithromycin',
                'unitType' => 'tablets',
                'unitDescription' => '500mg tablets',
                'category' => 'Antibiotics',
                'description' => 'Macrolide antibiotic for respiratory and skin infections'
            ],
            [
                'name' => 'Co-amoxiclav',
                'unitType' => 'tablets',
                'unitDescription' => '625mg tablets',
                'category' => 'Antibiotics',
                'description' => 'Combination antibiotic for resistant bacterial infections'
            ],
            [
                'name' => 'Metronidazole',
                'unitType' => 'tablets',
                'unitDescription' => '400mg tablets',
                'category' => 'Antibiotics',
                'description' => 'Antibiotic for anaerobic and protozoal infections'
            ],
            [
                'name' => 'Trimethoprim',
                'unitType' => 'tablets',
                'unitDescription' => '200mg tablets',
                'category' => 'Antibiotics',
                'description' => 'Antibiotic for urinary tract infections'
            ],
            [
                'name' => 'Cephalexin',
                'unitType' => 'capsules',
                'unitDescription' => '500mg capsules',
                'category' => 'Antibiotics',
                'description' => 'First-generation cephalosporin antibiotic'
            ],

            // Respiratory Medications
            [
                'name' => 'Salbutamol Inhaler',
                'unitType' => 'inhaler',
                'unitDescription' => '100mcg/dose inhaler',
                'category' => 'Respiratory',
                'description' => 'Short-acting beta agonist for asthma and COPD'
            ],
            [
                'name' => 'Beclomethasone',
                'unitType' => 'inhaler',
                'unitDescription' => '250mcg/dose inhaler',
                'category' => 'Respiratory',
                'description' => 'Inhaled corticosteroid for asthma'
            ],
            [
                'name' => 'Montelukast',
                'unitType' => 'tablets',
                'unitDescription' => '10mg tablets',
                'category' => 'Respiratory',
                'description' => 'Leukotriene receptor antagonist for asthma'
            ],
            [
                'name' => 'Formoterol',
                'unitType' => 'inhaler',
                'unitDescription' => '12mcg/dose inhaler',
                'category' => 'Respiratory',
                'description' => 'Long-acting beta agonist for asthma and COPD'
            ],
            [
                'name' => 'Tiotropium',
                'unitType' => 'inhaler',
                'unitDescription' => '18mcg/dose inhaler',
                'category' => 'Respiratory',
                'description' => 'Long-acting muscarinic antagonist for COPD'
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
                'name' => 'Cetirizine',
                'unitType' => 'tablets',
                'unitDescription' => '10mg tablets',
                'category' => 'Antihistamine',
                'description' => 'Antihistamine for allergic reactions'
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
                'name' => 'Ranitidine',
                'unitType' => 'tablets',
                'unitDescription' => '150mg tablets',
                'category' => 'Gastrointestinal',
                'description' => 'H2 receptor antagonist for acid reduction'
            ],
            [
                'name' => 'Metoclopramide',
                'unitType' => 'tablets',
                'unitDescription' => '10mg tablets',
                'category' => 'Gastrointestinal',
                'description' => 'Anti-emetic and prokinetic agent'
            ],
            [
                'name' => 'Lansoprazole',
                'unitType' => 'capsules',
                'unitDescription' => '30mg capsules',
                'category' => 'Gastrointestinal',
                'description' => 'Proton pump inhibitor for acid-related disorders'
            ],
            [
                'name' => 'Loperamide',
                'unitType' => 'capsules',
                'unitDescription' => '2mg capsules',
                'category' => 'Gastrointestinal',
                'description' => 'Anti-diarrheal medication'
            ],
            [
                'name' => 'Senna',
                'unitType' => 'tablets',
                'unitDescription' => '7.5mg tablets',
                'category' => 'Gastrointestinal',
                'description' => 'Stimulant laxative'
            ],
            [
                'name' => 'Gaviscon',
                'unitType' => 'sachets',
                'unitDescription' => '10ml sachets',
                'category' => 'Gastrointestinal',
                'description' => 'Relief from heartburn and indigestion'
            ],
            [
                'name' => 'Simethicone',
                'unitType' => 'tablets',
                'unitDescription' => '40mg tablets',
                'category' => 'Gastrointestinal',
                'description' => 'Anti-flatulent medication'
            ],

            // Cardiovascular
            [
                'name' => 'Amlodipine',
                'unitType' => 'tablets',
                'unitDescription' => '5mg tablets',
                'category' => 'Cardiovascular',
                'description' => 'Calcium channel blocker for hypertension'
            ],
            [
                'name' => 'Enalapril',
                'unitType' => 'tablets',
                'unitDescription' => '10mg tablets',
                'category' => 'Cardiovascular',
                'description' => 'ACE inhibitor for hypertension and heart failure'
            ],
            [
                'name' => 'Atenolol',
                'unitType' => 'tablets',
                'unitDescription' => '50mg tablets',
                'category' => 'Cardiovascular',
                'description' => 'Beta blocker for hypertension and heart conditions'
            ],
            [
                'name' => 'Simvastatin',
                'unitType' => 'tablets',
                'unitDescription' => '20mg tablets',
                'category' => 'Cardiovascular',
                'description' => 'Statin for cholesterol management'
            ],

            // Diabetes
            [
                'name' => 'Metformin',
                'unitType' => 'tablets',
                'unitDescription' => '500mg tablets',
                'category' => 'Diabetes',
                'description' => 'First-line treatment for type 2 diabetes'
            ],
            [
                'name' => 'Gliclazide',
                'unitType' => 'tablets',
                'unitDescription' => '80mg tablets',
                'category' => 'Diabetes',
                'description' => 'Sulfonylurea for type 2 diabetes'
            ],
            [
                'name' => 'Glucose Tablets',
                'unitType' => 'tablets',
                'unitDescription' => '4g dextrose tablets',
                'category' => 'Diabetes',
                'description' => 'Fast-acting glucose for hypoglycemia'
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
                'name' => 'Clotrimazole Cream',
                'unitType' => 'tubes',
                'unitDescription' => '20g tube',
                'category' => 'Topical',
                'description' => 'Antifungal cream for skin infections'
            ],
            [
                'name' => 'Fusidic Acid Cream',
                'unitType' => 'tubes',
                'unitDescription' => '15g tube',
                'category' => 'Topical',
                'description' => 'Topical antibiotic cream'
            ],
            [
                'name' => 'Betadine Solution',
                'unitType' => 'bottles',
                'unitDescription' => '60ml bottle',
                'category' => 'Topical',
                'description' => 'Povidone-iodine antiseptic solution'
            ],

            // Eye & Ear Drops
            [
                'name' => 'Chloramphenicol Eye Drops',
                'unitType' => 'bottles',
                'unitDescription' => '10ml bottle',
                'category' => 'Eye & Ear',
                'description' => 'Antibiotic eye drops for bacterial infections'
            ],
            [
                'name' => 'Artificial Tears',
                'unitType' => 'bottles',
                'unitDescription' => '15ml bottle',
                'category' => 'Eye & Ear',
                'description' => 'Lubricating eye drops for dry eyes'
            ],

            // Oral Care
            [
                'name' => 'Chlorhexidine Mouthwash',
                'unitType' => 'bottles',
                'unitDescription' => '200ml bottle',
                'category' => 'Oral Care',
                'description' => 'Antibacterial mouthwash'
            ],

            // Vitamins & Supplements
            [
                'name' => 'Vitamin D3',
                'unitType' => 'tablets',
                'unitDescription' => '1000 IU tablets',
                'category' => 'Vitamins',
                'description' => 'Vitamin D supplement'
            ],
            [
                'name' => 'Vitamin B Complex',
                'unitType' => 'tablets',
                'unitDescription' => 'Multi-B vitamin tablets',
                'category' => 'Vitamins',
                'description' => 'B-vitamin complex supplement'
            ],
            [
                'name' => 'Folic Acid',
                'unitType' => 'tablets',
                'unitDescription' => '5mg tablets',
                'category' => 'Vitamins',
                'description' => 'Folic acid supplement'
            ],
            [
                'name' => 'Iron Tablets',
                'unitType' => 'tablets',
                'unitDescription' => '200mg tablets',
                'category' => 'Vitamins',
                'description' => 'Iron supplement for anemia'
            ],
            [
                'name' => 'Calcium Carbonate',
                'unitType' => 'tablets',
                'unitDescription' => '500mg tablets',
                'category' => 'Vitamins',
                'description' => 'Calcium supplement'
            ],

            // Emergency/Controlled
            [
                'name' => 'Adrenaline Auto-Injector',
                'unitType' => 'pieces',
                'unitDescription' => '0.3mg EpiPen',
                'category' => 'Emergency',
                'description' => 'Emergency epinephrine auto-injector for anaphylaxis'
            ],
            [
                'name' => 'Prednisolone',
                'unitType' => 'tablets',
                'unitDescription' => '5mg tablets',
                'category' => 'Anti-inflammatory',
                'description' => 'Oral corticosteroid for inflammation'
            ],

            // Additional Common Medications
            [
                'name' => 'Domperidone',
                'unitType' => 'tablets',
                'unitDescription' => '10mg tablets',
                'category' => 'Gastrointestinal',
                'description' => 'Prokinetic agent for nausea and vomiting'
            ],
            [
                'name' => 'Diazepam',
                'unitType' => 'tablets',
                'unitDescription' => '5mg tablets',
                'category' => 'Anxiolytic',
                'description' => 'Benzodiazepine for anxiety and muscle spasms'
            ],
            [
                'name' => 'Warfarin',
                'unitType' => 'tablets',
                'unitDescription' => '5mg tablets',
                'category' => 'Anticoagulant',
                'description' => 'Oral anticoagulant'
            ]
        ];

        foreach ($medications as $medicationData) {
            // Check if medication already exists to prevent duplicates
            $existingMedication = $manager->getRepository(Medication::class)
                ->findOneBy(['name' => $medicationData['name']]);

            if (!$existingMedication) {
                $medication = new Medication();
                $medication->setName($medicationData['name']);
                $medication->setUnitType($medicationData['unitType']);
                $medication->setUnitDescription($medicationData['unitDescription']);
                $medication->setCategory($medicationData['category']);
                $medication->setDescription($medicationData['description']);

                $manager->persist($medication);
            }
        }

        $manager->flush();
    }
} 