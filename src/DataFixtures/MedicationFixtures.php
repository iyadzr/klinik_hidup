<?php

namespace App\DataFixtures;

use App\Entity\Medication;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MedicationFixtures extends Fixture
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

            // Respiratory Medications
            [
                'name' => 'Salbutamol',
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

            // Cardiovascular
            [
                'name' => 'Amlodipine',
                'unitType' => 'tablets',
                'unitDescription' => '5mg tablets',
                'category' => 'Cardiovascular',
                'description' => 'Calcium channel blocker for hypertension'
            ],
            [
                'name' => 'Atenolol',
                'unitType' => 'tablets',
                'unitDescription' => '50mg tablets',
                'category' => 'Cardiovascular',
                'description' => 'Beta blocker for hypertension and angina'
            ],
            [
                'name' => 'Simvastatin',
                'unitType' => 'tablets',
                'unitDescription' => '40mg tablets',
                'category' => 'Cardiovascular',
                'description' => 'Statin for cholesterol reduction'
            ],
            [
                'name' => 'Lisinopril',
                'unitType' => 'tablets',
                'unitDescription' => '10mg tablets',
                'category' => 'Cardiovascular',
                'description' => 'ACE inhibitor for hypertension'
            ],
            [
                'name' => 'Losartan',
                'unitType' => 'tablets',
                'unitDescription' => '50mg tablets',
                'category' => 'Cardiovascular',
                'description' => 'Angiotensin II receptor blocker for hypertension'
            ],
            [
                'name' => 'Aspirin',
                'unitType' => 'tablets',
                'unitDescription' => '75mg tablets',
                'category' => 'Cardiovascular',
                'description' => 'Anti-platelet medication for cardiovascular protection'
            ],
            [
                'name' => 'Furosemide',
                'unitType' => 'tablets',
                'unitDescription' => '40mg tablets',
                'category' => 'Cardiovascular',
                'description' => 'Loop diuretic for fluid overload'
            ],

            // Diabetes
            [
                'name' => 'Metformin',
                'unitType' => 'tablets',
                'unitDescription' => '500mg tablets',
                'category' => 'Diabetes',
                'description' => 'Biguanide for type 2 diabetes'
            ],
            [
                'name' => 'Glibenclamide',
                'unitType' => 'tablets',
                'unitDescription' => '5mg tablets',
                'category' => 'Diabetes',
                'description' => 'Sulfonylurea for type 2 diabetes'
            ],
            [
                'name' => 'Gliclazide',
                'unitType' => 'tablets',
                'unitDescription' => '80mg tablets',
                'category' => 'Diabetes',
                'description' => 'Sulfonylurea for type 2 diabetes'
            ],
            [
                'name' => 'Sitagliptin',
                'unitType' => 'tablets',
                'unitDescription' => '100mg tablets',
                'category' => 'Diabetes',
                'description' => 'DPP-4 inhibitor for type 2 diabetes'
            ],
            [
                'name' => 'Insulin Glargine',
                'unitType' => 'injection',
                'unitDescription' => '100units/ml injection',
                'category' => 'Diabetes',
                'description' => 'Long-acting insulin for diabetes'
            ],

            // Dermatological
            [
                'name' => 'Betnovate',
                'unitType' => 'cream',
                'unitDescription' => '0.1% cream',
                'category' => 'Dermatological',
                'description' => 'Topical corticosteroid for skin conditions'
            ],
            [
                'name' => 'Fucidin',
                'unitType' => 'cream',
                'unitDescription' => '2% cream',
                'category' => 'Dermatological',
                'description' => 'Antibiotic cream for skin infections'
            ],
            [
                'name' => 'Canesten',
                'unitType' => 'cream',
                'unitDescription' => '1% cream',
                'category' => 'Dermatological',
                'description' => 'Antifungal cream for fungal infections'
            ],
            [
                'name' => 'Dermovate',
                'unitType' => 'cream',
                'unitDescription' => '0.05% cream',
                'category' => 'Dermatological',
                'description' => 'Potent topical corticosteroid for severe skin conditions'
            ],
            [
                'name' => 'Terbinafine',
                'unitType' => 'cream',
                'unitDescription' => '1% cream',
                'category' => 'Dermatological',
                'description' => 'Antifungal cream for fungal infections'
            ],
            [
                'name' => 'Permethrin',
                'unitType' => 'cream',
                'unitDescription' => '5% cream',
                'category' => 'Dermatological',
                'description' => 'Treatment for scabies and head lice'
            ],

            // Mental Health
            [
                'name' => 'Fluoxetine',
                'unitType' => 'capsules',
                'unitDescription' => '20mg capsules',
                'category' => 'Mental Health',
                'description' => 'SSRI antidepressant'
            ],
            [
                'name' => 'Amitriptyline',
                'unitType' => 'tablets',
                'unitDescription' => '25mg tablets',
                'category' => 'Mental Health',
                'description' => 'Tricyclic antidepressant'
            ],
            [
                'name' => 'Diazepam',
                'unitType' => 'tablets',
                'unitDescription' => '5mg tablets',
                'category' => 'Mental Health',
                'description' => 'Benzodiazepine for anxiety'
            ],
            [
                'name' => 'Sertraline',
                'unitType' => 'tablets',
                'unitDescription' => '50mg tablets',
                'category' => 'Mental Health',
                'description' => 'SSRI antidepressant'
            ],
            [
                'name' => 'Venlafaxine',
                'unitType' => 'tablets',
                'unitDescription' => '75mg tablets',
                'category' => 'Mental Health',
                'description' => 'SNRI antidepressant'
            ],

            // Supplements
            [
                'name' => 'Vitamin D',
                'unitType' => 'tablets',
                'unitDescription' => '1000IU tablets',
                'category' => 'Supplements',
                'description' => 'Vitamin D supplement'
            ],
            [
                'name' => 'Iron Tablets',
                'unitType' => 'tablets',
                'unitDescription' => '200mg tablets',
                'category' => 'Supplements',
                'description' => 'Iron supplement for anemia'
            ],
            [
                'name' => 'Folic Acid',
                'unitType' => 'tablets',
                'unitDescription' => '5mg tablets',
                'category' => 'Supplements',
                'description' => 'Folic acid supplement'
            ],
            [
                'name' => 'Calcium',
                'unitType' => 'tablets',
                'unitDescription' => '500mg tablets',
                'category' => 'Supplements',
                'description' => 'Calcium supplement'
            ],
            [
                'name' => 'Vitamin B12',
                'unitType' => 'tablets',
                'unitDescription' => '1000mcg tablets',
                'category' => 'Supplements',
                'description' => 'Vitamin B12 supplement'
            ],

            // Eye/Ear
            [
                'name' => 'Chloramphenicol',
                'unitType' => 'drops',
                'unitDescription' => '0.5% eye drops',
                'category' => 'Eye/Ear',
                'description' => 'Antibiotic eye drops'
            ],
            [
                'name' => 'Betnesol',
                'unitType' => 'drops',
                'unitDescription' => '0.1% eye drops',
                'category' => 'Eye/Ear',
                'description' => 'Steroid eye drops'
            ],
            [
                'name' => 'Otomize',
                'unitType' => 'spray',
                'unitDescription' => 'ear spray',
                'category' => 'Eye/Ear',
                'description' => 'Antibiotic and steroid ear spray'
            ],
            [
                'name' => 'Artificial Tears',
                'unitType' => 'drops',
                'unitDescription' => 'eye drops',
                'category' => 'Eye/Ear',
                'description' => 'Lubricating eye drops for dry eyes'
            ],

            // Women's Health
            [
                'name' => 'Progesterone',
                'unitType' => 'tablets',
                'unitDescription' => '200mg tablets',
                'category' => 'Women\'s Health',
                'description' => 'Hormone replacement therapy'
            ],
            [
                'name' => 'Estradiol',
                'unitType' => 'tablets',
                'unitDescription' => '2mg tablets',
                'category' => 'Women\'s Health',
                'description' => 'Hormone replacement therapy'
            ],
            [
                'name' => 'Tranexamic Acid',
                'unitType' => 'tablets',
                'unitDescription' => '500mg tablets',
                'category' => 'Women\'s Health',
                'description' => 'Treatment for heavy menstrual bleeding'
            ],

            // Emergency Medications
            [
                'name' => 'Adrenaline',
                'unitType' => 'injection',
                'unitDescription' => '1mg/ml injection',
                'category' => 'Emergency',
                'description' => 'Emergency treatment for anaphylaxis'
            ],
            [
                'name' => 'Glucagon',
                'unitType' => 'injection',
                'unitDescription' => '1mg injection',
                'category' => 'Emergency',
                'description' => 'Emergency treatment for severe hypoglycemia'
            ],
            [
                'name' => 'Naloxone',
                'unitType' => 'injection',
                'unitDescription' => '0.4mg/ml injection',
                'category' => 'Emergency',
                'description' => 'Emergency treatment for opioid overdose'
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