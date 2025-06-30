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
            // BATCH 1: Pain Relief & Anti-inflammatory (Common in Malaysian clinics)
            [
                'name' => 'Paracetamol',
                'unitType' => 'tablets',
                'unitDescription' => '500mg tablets',
                'category' => 'Pain Relief',
                'description' => 'For pain relief and fever reduction',
                'costPrice' => '0.10',
                'sellingPrice' => '0.30'
            ],
            [
                'name' => 'Paracetamol Syrup',
                'unitType' => 'bottles',
                'unitDescription' => '60ml bottle (120mg/5ml)',
                'category' => 'Pain Relief',
                'description' => 'Paediatric paracetamol suspension',
                'costPrice' => '3.50',
                'sellingPrice' => '8.00'
            ],
            [
                'name' => 'Ibuprofen',
                'unitType' => 'tablets',
                'unitDescription' => '400mg tablets',
                'category' => 'Pain Relief',
                'description' => 'NSAID for pain and inflammation',
                'costPrice' => '0.15',
                'sellingPrice' => '0.50'
            ],
            [
                'name' => 'Diclofenac',
                'unitType' => 'tablets',
                'unitDescription' => '50mg tablets',
                'category' => 'Pain Relief',
                'description' => 'NSAID for moderate to severe pain',
                'costPrice' => '0.25',
                'sellingPrice' => '0.80'
            ],
            [
                'name' => 'Mefenamic Acid',
                'unitType' => 'tablets',
                'unitDescription' => '500mg tablets',
                'category' => 'Pain Relief',
                'description' => 'NSAID commonly used for menstrual pain',
                'costPrice' => '0.20',
                'sellingPrice' => '0.60'
            ],
            [
                'name' => 'Aspirin',
                'unitType' => 'tablets',
                'unitDescription' => '100mg tablets',
                'category' => 'Cardiology',
                'description' => 'Low-dose aspirin for cardiovascular protection',
                'costPrice' => '0.08',
                'sellingPrice' => '0.25'
            ],
            [
                'name' => 'Voltaren Gel',
                'unitType' => 'tubes',
                'unitDescription' => '20g tube (1% diclofenac)',
                'category' => 'Topical',
                'description' => 'Topical NSAID gel for muscle and joint pain',
                'costPrice' => '8.50',
                'sellingPrice' => '18.00'
            ],

            // BATCH 2: Antibiotics (Common in Malaysian practice)
            [
                'name' => 'Amoxicillin',
                'unitType' => 'capsules',
                'unitDescription' => '500mg capsules',
                'category' => 'Antibiotics',
                'description' => 'Penicillin antibiotic for bacterial infections',
                'costPrice' => '0.30',
                'sellingPrice' => '1.00'
            ],
            [
                'name' => 'Amoxicillin Syrup',
                'unitType' => 'bottles',
                'unitDescription' => '60ml bottle (125mg/5ml)',
                'category' => 'Antibiotics',
                'description' => 'Paediatric amoxicillin suspension',
                'costPrice' => '4.00',
                'sellingPrice' => '12.00'
            ],
            [
                'name' => 'Augmentin',
                'unitType' => 'tablets',
                'unitDescription' => '625mg tablets (Amoxicillin + Clavulanic acid)',
                'category' => 'Antibiotics',
                'description' => 'Enhanced penicillin for resistant infections',
                'costPrice' => '2.50',
                'sellingPrice' => '6.00'
            ],
            [
                'name' => 'Azithromycin',
                'unitType' => 'tablets',
                'unitDescription' => '500mg tablets',
                'category' => 'Antibiotics',
                'description' => 'Macrolide antibiotic for respiratory infections',
                'costPrice' => '3.00',
                'sellingPrice' => '8.00'
            ],
            [
                'name' => 'Clarithromycin',
                'unitType' => 'tablets',
                'unitDescription' => '500mg tablets',
                'category' => 'Antibiotics',
                'description' => 'Macrolide antibiotic for H. pylori and respiratory infections',
                'costPrice' => '4.50',
                'sellingPrice' => '12.00'
            ],
            [
                'name' => 'Doxycycline',
                'unitType' => 'capsules',
                'unitDescription' => '100mg capsules',
                'category' => 'Antibiotics',
                'description' => 'Tetracycline antibiotic for various infections',
                'costPrice' => '0.80',
                'sellingPrice' => '2.50'
            ],
            [
                'name' => 'Ciprofloxacin',
                'unitType' => 'tablets',
                'unitDescription' => '500mg tablets',
                'category' => 'Antibiotics',
                'description' => 'Fluoroquinolone for UTI and other infections',
                'costPrice' => '1.20',
                'sellingPrice' => '3.50'
            ],
            [
                'name' => 'Cloxacillin',
                'unitType' => 'capsules',
                'unitDescription' => '500mg capsules',
                'category' => 'Antibiotics',
                'description' => 'Penicillin for staphylococcal infections',
                'costPrice' => '0.50',
                'sellingPrice' => '1.50'
            ],

            // BATCH 3: Respiratory Medications
            [
                'name' => 'Salbutamol Inhaler',
                'unitType' => 'inhaler',
                'unitDescription' => '100mcg/dose inhaler (200 doses)',
                'category' => 'Respiratory',
                'description' => 'Short-acting bronchodilator for asthma',
                'costPrice' => '12.00',
                'sellingPrice' => '25.00'
            ],
            [
                'name' => 'Salbutamol Syrup',
                'unitType' => 'bottles',
                'unitDescription' => '60ml bottle (2mg/5ml)',
                'category' => 'Respiratory',
                'description' => 'Oral bronchodilator syrup',
                'costPrice' => '5.00',
                'sellingPrice' => '12.00'
            ],
            [
                'name' => 'Prednisolone',
                'unitType' => 'tablets',
                'unitDescription' => '5mg tablets',
                'category' => 'Respiratory',
                'description' => 'Corticosteroid for asthma and inflammation',
                'costPrice' => '0.15',
                'sellingPrice' => '0.50'
            ],
            [
                'name' => 'Beclomethasone Inhaler',
                'unitType' => 'inhaler',
                'unitDescription' => '250mcg/dose inhaler',
                'category' => 'Respiratory',
                'description' => 'Inhaled corticosteroid for asthma prevention',
                'costPrice' => '18.00',
                'sellingPrice' => '35.00'
            ],

            // BATCH 4: Cough & Cold (Very common in Malaysian climate)
            [
                'name' => 'Dextromethorphan Syrup',
                'unitType' => 'bottles',
                'unitDescription' => '100ml bottle (15mg/5ml)',
                'category' => 'Cough & Cold',
                'description' => 'Cough suppressant for dry cough',
                'costPrice' => '4.50',
                'sellingPrice' => '10.00'
            ],
            [
                'name' => 'Guaifenesin Syrup',
                'unitType' => 'bottles',
                'unitDescription' => '100ml bottle (100mg/5ml)',
                'category' => 'Cough & Cold',
                'description' => 'Expectorant for productive cough',
                'costPrice' => '5.00',
                'sellingPrice' => '12.00'
            ],
            [
                'name' => 'Prospan Cough Syrup',
                'unitType' => 'bottles',
                'unitDescription' => '100ml bottle',
                'category' => 'Cough & Cold',
                'description' => 'Natural ivy leaf extract cough syrup',
                'costPrice' => '8.00',
                'sellingPrice' => '18.00'
            ],
            [
                'name' => 'Bromhexine',
                'unitType' => 'tablets',
                'unitDescription' => '8mg tablets',
                'category' => 'Cough & Cold',
                'description' => 'Mucolytic agent for thick phlegm',
                'costPrice' => '0.20',
                'sellingPrice' => '0.60'
            ],
            [
                'name' => 'Carbocisteine',
                'unitType' => 'capsules',
                'unitDescription' => '375mg capsules',
                'category' => 'Cough & Cold',
                'description' => 'Mucolytic for respiratory secretions',
                'costPrice' => '0.80',
                'sellingPrice' => '2.00'
            ],
            [
                'name' => 'Pseudoephedrine',
                'unitType' => 'tablets',
                'unitDescription' => '60mg tablets',
                'category' => 'Cough & Cold',
                'description' => 'Nasal decongestant',
                'costPrice' => '0.25',
                'sellingPrice' => '0.80'
            ],

            // BATCH 5: Antihistamines (Important in tropical climate)
            [
                'name' => 'Loratadine',
                'unitType' => 'tablets',
                'unitDescription' => '10mg tablets',
                'category' => 'Antihistamine',
                'description' => 'Non-drowsy antihistamine for allergies',
                'costPrice' => '0.30',
                'sellingPrice' => '1.00'
            ],
            [
                'name' => 'Cetirizine',
                'unitType' => 'tablets',
                'unitDescription' => '10mg tablets',
                'category' => 'Antihistamine',
                'description' => 'Antihistamine for allergic reactions',
                'costPrice' => '0.25',
                'sellingPrice' => '0.80'
            ],
            [
                'name' => 'Chlorpheniramine',
                'unitType' => 'tablets',
                'unitDescription' => '4mg tablets',
                'category' => 'Antihistamine',
                'description' => 'First-generation antihistamine',
                'costPrice' => '0.10',
                'sellingPrice' => '0.40'
            ],
            [
                'name' => 'Diphenhydramine',
                'unitType' => 'tablets',
                'unitDescription' => '25mg tablets',
                'category' => 'Antihistamine',
                'description' => 'Antihistamine with sedative effect',
                'costPrice' => '0.15',
                'sellingPrice' => '0.50'
            ],

            // BATCH 6: Gastrointestinal (Common complaints in Malaysia)
            [
                'name' => 'Omeprazole',
                'unitType' => 'capsules',
                'unitDescription' => '20mg capsules',
                'category' => 'Gastrointestinal',
                'description' => 'Proton pump inhibitor for acid disorders',
                'costPrice' => '0.80',
                'sellingPrice' => '2.50'
            ],
            [
                'name' => 'Ranitidine',
                'unitType' => 'tablets',
                'unitDescription' => '150mg tablets',
                'category' => 'Gastrointestinal',
                'description' => 'H2 receptor antagonist for acid reduction',
                'costPrice' => '0.40',
                'sellingPrice' => '1.20'
            ],
            [
                'name' => 'Domperidone',
                'unitType' => 'tablets',
                'unitDescription' => '10mg tablets',
                'category' => 'Gastrointestinal',
                'description' => 'Prokinetic agent for nausea and gastroparesis',
                'costPrice' => '0.50',
                'sellingPrice' => '1.50'
            ],
            [
                'name' => 'Loperamide',
                'unitType' => 'capsules',
                'unitDescription' => '2mg capsules',
                'category' => 'Gastrointestinal',
                'description' => 'Anti-diarrheal medication',
                'costPrice' => '0.60',
                'sellingPrice' => '1.80'
            ],
            [
                'name' => 'Gaviscon',
                'unitType' => 'sachets',
                'unitDescription' => '10ml sachets',
                'category' => 'Gastrointestinal',
                'description' => 'Alginate for heartburn and reflux',
                'costPrice' => '0.80',
                'sellingPrice' => '2.00'
            ],
            [
                'name' => 'Simethicone',
                'unitType' => 'tablets',
                'unitDescription' => '40mg tablets',
                'category' => 'Gastrointestinal',
                'description' => 'Anti-foaming agent for gas and bloating',
                'costPrice' => '0.25',
                'sellingPrice' => '0.70'
            ],
            [
                'name' => 'ORS Sachets',
                'unitType' => 'sachets',
                'unitDescription' => '21g sachets',
                'category' => 'Gastrointestinal',
                'description' => 'Oral rehydration salts for dehydration',
                'costPrice' => '0.50',
                'sellingPrice' => '1.20'
            ],

            // BATCH 7: Diabetes Management (Growing concern in Malaysia)
            [
                'name' => 'Metformin',
                'unitType' => 'tablets',
                'unitDescription' => '500mg tablets',
                'category' => 'Diabetes',
                'description' => 'First-line treatment for type 2 diabetes',
                'costPrice' => '0.15',
                'sellingPrice' => '0.50'
            ],
            [
                'name' => 'Glibenclamide',
                'unitType' => 'tablets',
                'unitDescription' => '5mg tablets',
                'category' => 'Diabetes',
                'description' => 'Sulfonylurea for type 2 diabetes',
                'costPrice' => '0.10',
                'sellingPrice' => '0.40'
            ],
            [
                'name' => 'Glucose Tablets',
                'unitType' => 'tablets',
                'unitDescription' => '4g dextrose tablets',
                'category' => 'Diabetes',
                'description' => 'Fast-acting glucose for hypoglycemia',
                'costPrice' => '0.20',
                'sellingPrice' => '0.60'
            ],

            // BATCH 8: Hypertension (Very common in Malaysia)
            [
                'name' => 'Amlodipine',
                'unitType' => 'tablets',
                'unitDescription' => '5mg tablets',
                'category' => 'Hypertension',
                'description' => 'Calcium channel blocker for high blood pressure',
                'costPrice' => '0.20',
                'sellingPrice' => '0.70'
            ],
            [
                'name' => 'Perindopril',
                'unitType' => 'tablets',
                'unitDescription' => '4mg tablets',
                'category' => 'Hypertension',
                'description' => 'ACE inhibitor for hypertension and heart failure',
                'costPrice' => '0.80',
                'sellingPrice' => '2.50'
            ],
            [
                'name' => 'Atenolol',
                'unitType' => 'tablets',
                'unitDescription' => '50mg tablets',
                'category' => 'Hypertension',
                'description' => 'Beta-blocker for hypertension and angina',
                'costPrice' => '0.15',
                'sellingPrice' => '0.60'
            ],

            // BATCH 9: Topical Medications (Essential for tropical climate)
            [
                'name' => 'Hydrocortisone Cream',
                'unitType' => 'tubes',
                'unitDescription' => '15g tube (1%)',
                'category' => 'Topical',
                'description' => 'Mild topical corticosteroid for skin inflammation',
                'costPrice' => '3.50',
                'sellingPrice' => '8.00'
            ],
            [
                'name' => 'Betadine Solution',
                'unitType' => 'bottles',
                'unitDescription' => '60ml bottle',
                'category' => 'Topical',
                'description' => 'Povidone-iodine antiseptic solution',
                'costPrice' => '4.00',
                'sellingPrice' => '9.00'
            ],
            [
                'name' => 'Calamine Lotion',
                'unitType' => 'bottles',
                'unitDescription' => '100ml bottle',
                'category' => 'Topical',
                'description' => 'Soothing lotion for itchy skin conditions',
                'costPrice' => '2.50',
                'sellingPrice' => '6.00'
            ],
            [
                'name' => 'Acyclovir Cream',
                'unitType' => 'tubes',
                'unitDescription' => '5g tube (5%)',
                'category' => 'Topical',
                'description' => 'Antiviral cream for cold sores',
                'costPrice' => '8.00',
                'sellingPrice' => '18.00'
            ],
            [
                'name' => 'Mupirocin Ointment',
                'unitType' => 'tubes',
                'unitDescription' => '15g tube (2%)',
                'category' => 'Topical',
                'description' => 'Topical antibiotic for skin infections',
                'costPrice' => '12.00',
                'sellingPrice' => '25.00'
            ],

            // BATCH 10: Antifungal Medications (Common in humid climate)
            [
                'name' => 'Fluconazole',
                'unitType' => 'capsules',
                'unitDescription' => '150mg capsules',
                'category' => 'Antifungal',
                'description' => 'Oral antifungal for thrush and systemic infections',
                'costPrice' => '3.50',
                'sellingPrice' => '10.00'
            ],
            [
                'name' => 'Clotrimazole Cream',
                'unitType' => 'tubes',
                'unitDescription' => '20g tube (1%)',
                'category' => 'Antifungal',
                'description' => 'Topical antifungal for skin fungal infections',
                'costPrice' => '4.50',
                'sellingPrice' => '12.00'
            ],
            [
                'name' => 'Ketoconazole Shampoo',
                'unitType' => 'bottles',
                'unitDescription' => '60ml bottle (2%)',
                'category' => 'Antifungal',
                'description' => 'Medicated shampoo for dandruff and scalp infections',
                'costPrice' => '12.00',
                'sellingPrice' => '25.00'
            ],
            [
                'name' => 'Nystatin Oral Drops',
                'unitType' => 'bottles',
                'unitDescription' => '24ml bottle',
                'category' => 'Antifungal',
                'description' => 'Oral antifungal drops for thrush in infants',
                'costPrice' => '8.00',
                'sellingPrice' => '18.00'
            ],

            // BATCH 11: Eye & Ear Medications
            [
                'name' => 'Chloramphenicol Eye Drops',
                'unitType' => 'bottles',
                'unitDescription' => '10ml bottle (0.5%)',
                'category' => 'Ophthalmology',
                'description' => 'Antibiotic eye drops for bacterial conjunctivitis',
                'costPrice' => '6.00',
                'sellingPrice' => '15.00'
            ],
            [
                'name' => 'Artificial Tears',
                'unitType' => 'bottles',
                'unitDescription' => '10ml bottle',
                'category' => 'Ophthalmology',
                'description' => 'Lubricating eye drops for dry eyes',
                'costPrice' => '8.00',
                'sellingPrice' => '18.00'
            ],
            [
                'name' => 'Otrivin Nasal Drops',
                'unitType' => 'bottles',
                'unitDescription' => '10ml bottle (0.1%)',
                'category' => 'ENT',
                'description' => 'Nasal decongestant drops',
                'costPrice' => '5.50',
                'sellingPrice' => '12.00'
            ],
            [
                'name' => 'Waxsol Ear Drops',
                'unitType' => 'bottles',
                'unitDescription' => '10ml bottle',
                'category' => 'ENT',
                'description' => 'Ear drops for wax removal',
                'costPrice' => '8.50',
                'sellingPrice' => '18.00'
            ],

            // BATCH 12: Vitamins & Supplements (Popular in Malaysia)
            [
                'name' => 'Vitamin C',
                'unitType' => 'tablets',
                'unitDescription' => '500mg tablets',
                'category' => 'Vitamins',
                'description' => 'Ascorbic acid supplement for immunity',
                'costPrice' => '0.08',
                'sellingPrice' => '0.25'
            ],
            [
                'name' => 'Vitamin D3',
                'unitType' => 'tablets',
                'unitDescription' => '1000IU tablets',
                'category' => 'Vitamins',
                'description' => 'Cholecalciferol for bone health',
                'costPrice' => '0.20',
                'sellingPrice' => '0.60'
            ],
            [
                'name' => 'Folic Acid',
                'unitType' => 'tablets',
                'unitDescription' => '5mg tablets',
                'category' => 'Vitamins',
                'description' => 'Essential for pregnancy and anemia prevention',
                'costPrice' => '0.05',
                'sellingPrice' => '0.20'
            ],
            [
                'name' => 'Iron Tablets',
                'unitType' => 'tablets',
                'unitDescription' => '200mg tablets (Ferrous sulfate)',
                'category' => 'Vitamins',
                'description' => 'Iron supplement for anemia',
                'costPrice' => '0.10',
                'sellingPrice' => '0.35'
            ],
            [
                'name' => 'Calcium Carbonate',
                'unitType' => 'tablets',
                'unitDescription' => '600mg tablets',
                'category' => 'Vitamins',
                'description' => 'Calcium supplement for bone health',
                'costPrice' => '0.15',
                'sellingPrice' => '0.50'
            ],
            [
                'name' => 'Vitamin B Complex',
                'unitType' => 'tablets',
                'unitDescription' => 'B1, B2, B6, B12 complex',
                'category' => 'Vitamins',
                'description' => 'B-vitamin complex for energy and nerve health',
                'costPrice' => '0.18',
                'sellingPrice' => '0.60'
            ],

            // BATCH 13: Emergency & First Aid Medications
            [
                'name' => 'Hydrogen Peroxide',
                'unitType' => 'bottles',
                'unitDescription' => '100ml bottle (3%)',
                'category' => 'First Aid',
                'description' => 'Antiseptic for wound cleaning',
                'costPrice' => '2.00',
                'sellingPrice' => '5.00'
            ],
            [
                'name' => 'Normal Saline',
                'unitType' => 'bottles',
                'unitDescription' => '500ml bottle',
                'category' => 'First Aid',
                'description' => 'Sterile saline solution for wound irrigation',
                'costPrice' => '3.00',
                'sellingPrice' => '8.00'
            ],

            // BATCH 14: Popular Malaysian Brands & OTC
            [
                'name' => 'Panadol Extra',
                'unitType' => 'tablets',
                'unitDescription' => 'Paracetamol 500mg + Caffeine 65mg',
                'category' => 'Pain Relief',
                'description' => 'Enhanced paracetamol with caffeine',
                'costPrice' => '0.25',
                'sellingPrice' => '0.80'
            ],
            [
                'name' => 'Panadol Actifast',
                'unitType' => 'tablets',
                'unitDescription' => '500mg soluble tablets',
                'category' => 'Pain Relief',
                'description' => 'Fast-acting paracetamol',
                'costPrice' => '0.35',
                'sellingPrice' => '1.00'
            ],
            [
                'name' => 'Woods Peppermint Cure',
                'unitType' => 'bottles',
                'unitDescription' => '50ml bottle',
                'category' => 'Cough & Cold',
                'description' => 'Popular cough syrup in Malaysia',
                'costPrice' => '4.50',
                'sellingPrice' => '12.00'
            ],
            [
                'name' => 'Strepsils Lozenges',
                'unitType' => 'lozenges',
                'unitDescription' => '24 lozenges',
                'category' => 'Cough & Cold',
                'description' => 'Antiseptic throat lozenges',
                'costPrice' => '6.00',
                'sellingPrice' => '15.00'
            ],
            [
                'name' => 'Gaviscon Advance',
                'unitType' => 'sachets',
                'unitDescription' => '10ml sachets',
                'category' => 'Gastrointestinal',
                'description' => 'Advanced formulation for severe heartburn',
                'costPrice' => '1.20',
                'sellingPrice' => '3.00'
            ],
            [
                'name' => 'Dettol Antiseptic',
                'unitType' => 'bottles',
                'unitDescription' => '125ml bottle',
                'category' => 'Antiseptic',
                'description' => 'Multi-purpose antiseptic liquid',
                'costPrice' => '4.50',
                'sellingPrice' => '12.00'
            ],

            // BATCH 15: Additional Common Prescriptions
            [
                'name' => 'Lansoprazole',
                'unitType' => 'capsules',
                'unitDescription' => '30mg capsules',
                'category' => 'Gastrointestinal',
                'description' => 'Proton pump inhibitor for GERD',
                'costPrice' => '1.50',
                'sellingPrice' => '4.00'
            ],
            [
                'name' => 'Simvastatin',
                'unitType' => 'tablets',
                'unitDescription' => '20mg tablets',
                'category' => 'Cardiology',
                'description' => 'Statin for cholesterol management',
                'costPrice' => '0.25',
                'sellingPrice' => '0.80'
            ],
            [
                'name' => 'Levothyroxine',
                'unitType' => 'tablets',
                'unitDescription' => '100mcg tablets',
                'category' => 'Endocrinology',
                'description' => 'Thyroid hormone replacement',
                'costPrice' => '0.30',
                'sellingPrice' => '1.00'
            ],
            [
                'name' => 'Allopurinol',
                'unitType' => 'tablets',
                'unitDescription' => '300mg tablets',
                'category' => 'Rheumatology',
                'description' => 'Uric acid reducer for gout prevention',
                'costPrice' => '0.40',
                'sellingPrice' => '1.20'
            ],

            // BATCH 16: Oral Care & Traditional
            [
                'name' => 'Chlorhexidine Mouthwash',
                'unitType' => 'bottles',
                'unitDescription' => '200ml bottle (0.2%)',
                'category' => 'Oral Care',
                'description' => 'Antibacterial mouthwash for gum disease',
                'costPrice' => '8.00',
                'sellingPrice' => '18.00'
            ],
            [
                'name' => 'Sodium Fluoride Toothpaste',
                'unitType' => 'tubes',
                'unitDescription' => '100g tube',
                'category' => 'Oral Care',
                'description' => 'High fluoride toothpaste for cavity prevention',
                'costPrice' => '5.00',
                'sellingPrice' => '12.00'
            ],
            [
                'name' => 'Minyak Angin Cap Kapak',
                'unitType' => 'bottles',
                'unitDescription' => '15ml bottle',
                'category' => 'Traditional',
                'description' => 'Traditional medicated oil for aches and pains',
                'costPrice' => '3.50',
                'sellingPrice' => '8.00'
            ],
            [
                'name' => 'Kapsul Tongkat Ali',
                'unitType' => 'capsules',
                'unitDescription' => '500mg capsules',
                'category' => 'Traditional',
                'description' => 'Traditional Malaysian herbal supplement',
                'costPrice' => '2.00',
                'sellingPrice' => '6.00'
            ],

            // BATCH 17: Contraceptives & Women's Health
            [
                'name' => 'Ethinylestradiol/Levonorgestrel',
                'unitType' => 'tablets',
                'unitDescription' => '21 tablet pack',
                'category' => 'Contraceptive',
                'description' => 'Combined oral contraceptive pill',
                'costPrice' => '8.00',
                'sellingPrice' => '20.00'
            ],
            [
                'name' => 'Clotrimazole Pessary',
                'unitType' => 'pessaries',
                'unitDescription' => '500mg pessary',
                'category' => 'Gynecology',
                'description' => 'Vaginal antifungal for thrush',
                'costPrice' => '4.00',
                'sellingPrice' => '12.00'
            ],
            [
                'name' => 'Norethisterone',
                'unitType' => 'tablets',
                'unitDescription' => '5mg tablets',
                'category' => 'Gynecology',
                'description' => 'Progestogen for menstrual disorders',
                'costPrice' => '0.80',
                'sellingPrice' => '2.50'
            ],

            // BATCH 18: Pediatric Specific Medications
            [
                'name' => 'Zinc Sulfate Syrup',
                'unitType' => 'bottles',
                'unitDescription' => '60ml bottle (20mg/5ml)',
                'category' => 'Pediatrics',
                'description' => 'Zinc supplement for children with diarrhea',
                'costPrice' => '4.50',
                'sellingPrice' => '12.00'
            ],
            [
                'name' => 'Gripe Water',
                'unitType' => 'bottles',
                'unitDescription' => '100ml bottle',
                'category' => 'Pediatrics',
                'description' => 'Herbal remedy for infant colic',
                'costPrice' => '6.00',
                'sellingPrice' => '15.00'
            ],
            [
                'name' => 'Infant Paracetamol Drops',
                'unitType' => 'bottles',
                'unitDescription' => '15ml bottle (100mg/ml)',
                'category' => 'Pediatrics',
                'description' => 'Concentrated paracetamol for infants',
                'costPrice' => '8.00',
                'sellingPrice' => '18.00'
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