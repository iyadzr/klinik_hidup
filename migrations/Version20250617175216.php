<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250617175216 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add profile image field to user table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE doctor ADD user_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE doctor ADD CONSTRAINT FK_1FC0F36AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_1FC0F36AA76ED395 ON doctor (user_id)
        SQL);
        // Add profile_image column to user table
        $this->addSql('ALTER TABLE user ADD profile_image VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE doctor DROP FOREIGN KEY FK_1FC0F36AA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_1FC0F36AA76ED395 ON doctor
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE doctor DROP user_id
        SQL);
        // Remove profile_image column from user table
        $this->addSql('ALTER TABLE user DROP profile_image');
    }
}
