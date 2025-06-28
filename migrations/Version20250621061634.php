<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250621061634 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix database schema issues and add missing columns';
    }

    public function up(Schema $schema): void
    {
        // Apply schema changes
        $this->addSql('ALTER TABLE consultation CHANGE status status VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE doctor DROP INDEX FK_DOCTOR_USER, ADD UNIQUE INDEX UNIQ_1FC0F36AA76ED395 (user_id)');
        $this->addSql('ALTER TABLE doctor DROP FOREIGN KEY FK_DOCTOR_USER');
        $this->addSql('ALTER TABLE doctor ADD CONSTRAINT FK_1FC0F36AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user CHANGE is_active is_active TINYINT(1) NOT NULL, CHANGE profile_image profile_image VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE queue ADD metadata LONGTEXT DEFAULT NULL, ADD is_paid TINYINT(1) DEFAULT 0 NOT NULL, ADD paid_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD payment_method VARCHAR(20) DEFAULT NULL, ADD amount NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE receipt_counter ADD counter_name VARCHAR(50) NOT NULL DEFAULT \'receipt\', ADD last_updated DATETIME NOT NULL DEFAULT NOW(), DROP year, DROP month, CHANGE current_number current_number INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D268318611AEA761 ON receipt_counter (counter_name)');
        $this->addSql('ALTER TABLE setting ADD category VARCHAR(100) DEFAULT NULL, ADD description VARCHAR(500) DEFAULT NULL, ADD value_type VARCHAR(50) NOT NULL DEFAULT \'string\', ADD is_system TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE setting RENAME INDEX uniq_setting_key TO UNIQ_9F74B8985FA1E697');
    }

    public function down(Schema $schema): void
    {
        // Reverse the schema changes
        $this->addSql('ALTER TABLE consultation CHANGE status status VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE doctor DROP INDEX UNIQ_1FC0F36AA76ED395, ADD INDEX FK_DOCTOR_USER (user_id)');
        $this->addSql('ALTER TABLE doctor DROP FOREIGN KEY FK_1FC0F36AA76ED395');
        $this->addSql('ALTER TABLE doctor ADD CONSTRAINT FK_DOCTOR_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL, CHANGE profile_image profile_image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE queue DROP metadata, DROP is_paid, DROP paid_at, DROP payment_method, DROP amount');
        $this->addSql('DROP INDEX UNIQ_D268318611AEA761 ON receipt_counter');
        $this->addSql('ALTER TABLE receipt_counter DROP counter_name, DROP last_updated, ADD year INT NOT NULL, ADD month INT NOT NULL, CHANGE current_number current_number INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE setting DROP category, DROP description, DROP value_type, DROP is_system');
        $this->addSql('ALTER TABLE setting RENAME INDEX UNIQ_9F74B8985FA1E697 TO uniq_setting_key');
    }
}
