<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250610173538 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE patient ADD gender VARCHAR(1) DEFAULT NULL, ADD address VARCHAR(500) DEFAULT NULL, CHANGE email email VARCHAR(255) DEFAULT NULL, CHANGE nric nric VARCHAR(20) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_1ADAD7EBCD4C031E ON patient (nric)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_1ADAD7EBCD4C031E ON patient
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE patient DROP gender, DROP address, CHANGE nric nric VARCHAR(50) DEFAULT NULL, CHANGE email email VARCHAR(255) NOT NULL
        SQL);
    }
}
