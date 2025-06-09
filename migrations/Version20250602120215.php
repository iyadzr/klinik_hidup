<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250602120215 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Only update queue registration_number null values and skip the other operations
        $this->addSql(<<<'SQL'
            UPDATE queue SET registration_number = 0 WHERE registration_number IS NULL
        SQL);
        
        // Skip the other operations that are causing conflicts
        // We'll manually add the MC fields in the next migration
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_1ADAD7EBCD4C031E ON patient
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE patient DROP nric
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE queue CHANGE registration_number registration_number INT DEFAULT NULL
        SQL);
    }
}
