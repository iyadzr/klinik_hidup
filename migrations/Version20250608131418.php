<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250608131418 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Only add the medical certificate fields to the consultation table
        $this->addSql(<<<'SQL'
            ALTER TABLE consultation ADD has_medical_certificate TINYINT(1) DEFAULT NULL, ADD mc_start_date DATETIME DEFAULT NULL, ADD mc_end_date DATETIME DEFAULT NULL, ADD mc_number VARCHAR(255) DEFAULT NULL, ADD mc_running_number VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE consultation DROP has_medical_certificate, DROP mc_start_date, DROP mc_end_date, DROP mc_number, DROP mc_running_number
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_1ADAD7EBCD4C031E ON patient
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE patient CHANGE nric nric VARCHAR(50) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE queue CHANGE registration_number registration_number INT DEFAULT NULL
        SQL);
    }
}
