<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241211001_CreateSettingsTable extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create settings table for application configuration';
    }

    public function up(Schema $schema): void
    {
        // Create settings table
        $this->addSql('CREATE TABLE setting (
            id INT AUTO_INCREMENT NOT NULL, 
            setting_key VARCHAR(255) NOT NULL, 
            setting_value LONGTEXT DEFAULT NULL, 
            value_type VARCHAR(50) NOT NULL DEFAULT "string", 
            category VARCHAR(100) NOT NULL DEFAULT "general", 
            description TEXT DEFAULT NULL, 
            is_system TINYINT(1) NOT NULL DEFAULT 0, 
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
            UNIQUE INDEX UNIQ_9F74B898A5E7B6C5 (setting_key), 
            INDEX IDX_9F74B89864C19C1 (category), 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // Drop settings table
        $this->addSql('DROP TABLE setting');
    }
} 