<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add dosage, frequency, and duration fields to prescribed_medication table
 */
final class Version20250622000000_AddDosageFieldsToPrescribedMedication extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add dosage, frequency, and duration fields to prescribed_medication table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE prescribed_medication ADD dosage VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE prescribed_medication ADD frequency VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE prescribed_medication ADD duration VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE prescribed_medication DROP dosage');
        $this->addSql('ALTER TABLE prescribed_medication DROP frequency');
        $this->addSql('ALTER TABLE prescribed_medication DROP duration');
    }
} 