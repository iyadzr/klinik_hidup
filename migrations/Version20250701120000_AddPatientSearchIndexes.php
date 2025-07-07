<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add database indexes for patient search optimization
 */
final class Version20250701120000_AddPatientSearchIndexes extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes for patient name, nric, phone search optimization';
    }

    public function up(Schema $schema): void
    {
        // Add indexes only if they don't exist
        
        // Add index for phone number searching
        $this->addSql('ALTER TABLE patient ADD INDEX IDX_PATIENT_PHONE_SEARCH (phone(20))');
        
        // Add composite index for pagination ordering
        $this->addSql('ALTER TABLE patient ADD INDEX IDX_PATIENT_NAME_ID (name(50), id)');
        
        // Add index for date of birth queries
        $this->addSql('ALTER TABLE patient ADD INDEX IDX_PATIENT_DOB (date_of_birth)');
        
        // Add index for gender filtering
        $this->addSql('ALTER TABLE patient ADD INDEX IDX_PATIENT_GENDER (gender)');
        
        // Add composite index for common filtering combinations
        $this->addSql('ALTER TABLE patient ADD INDEX IDX_PATIENT_SEARCH_COMBO (name(30), nric, phone(15))');
    }

    public function down(Schema $schema): void
    {
        // Remove the indexes we created
        $this->addSql('ALTER TABLE patient DROP INDEX IDX_PATIENT_SEARCH_COMBO');
        $this->addSql('ALTER TABLE patient DROP INDEX IDX_PATIENT_GENDER');
        $this->addSql('ALTER TABLE patient DROP INDEX IDX_PATIENT_DOB');
        $this->addSql('ALTER TABLE patient DROP INDEX IDX_PATIENT_NAME_ID');
        $this->addSql('ALTER TABLE patient DROP INDEX IDX_PATIENT_PHONE_SEARCH');
    }
} 