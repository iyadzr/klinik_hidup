<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250407235659 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE consultation ADD consultation_fee NUMERIC(10, 2) DEFAULT NULL, ADD medicines_fee NUMERIC(10, 2) DEFAULT NULL, ADD total_amount NUMERIC(10, 2) NOT NULL, ADD is_paid TINYINT(1) NOT NULL, ADD paid_at DATETIME DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE consultation DROP consultation_fee, DROP medicines_fee, DROP total_amount, DROP is_paid, DROP paid_at
        SQL);
    }
}
