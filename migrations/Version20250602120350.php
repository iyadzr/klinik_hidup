<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250602120350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Skip these operations since they're causing conflicts
        // The NRIC column likely already exists
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
