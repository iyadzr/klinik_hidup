<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250327155042 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE clinic_assistant (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(20) NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE medical_certificate (id INT AUTO_INCREMENT NOT NULL, patient_id INT NOT NULL, doctor_id INT NOT NULL, issue_date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', start_date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', end_date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', diagnosis VARCHAR(255) NOT NULL, remarks VARCHAR(1000) DEFAULT NULL, certificate_number VARCHAR(50) NOT NULL, INDEX IDX_B36515F86B899279 (patient_id), INDEX IDX_B36515F887F4FB17 (doctor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE queue (id INT AUTO_INCREMENT NOT NULL, patient_id INT NOT NULL, doctor_id INT NOT NULL, queue_date_time DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', status VARCHAR(20) NOT NULL, queue_number INT NOT NULL, INDEX IDX_7FFD7F636B899279 (patient_id), INDEX IDX_7FFD7F6387F4FB17 (doctor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE medical_certificate ADD CONSTRAINT FK_B36515F86B899279 FOREIGN KEY (patient_id) REFERENCES patient (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE medical_certificate ADD CONSTRAINT FK_B36515F887F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE queue ADD CONSTRAINT FK_7FFD7F636B899279 FOREIGN KEY (patient_id) REFERENCES patient (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE queue ADD CONSTRAINT FK_7FFD7F6387F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE patient ADD registered_by_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE patient ADD CONSTRAINT FK_1ADAD7EB27E92E18 FOREIGN KEY (registered_by_id) REFERENCES clinic_assistant (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_1ADAD7EB27E92E18 ON patient (registered_by_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE patient DROP FOREIGN KEY FK_1ADAD7EB27E92E18
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE medical_certificate DROP FOREIGN KEY FK_B36515F86B899279
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE medical_certificate DROP FOREIGN KEY FK_B36515F887F4FB17
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE queue DROP FOREIGN KEY FK_7FFD7F636B899279
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE queue DROP FOREIGN KEY FK_7FFD7F6387F4FB17
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE clinic_assistant
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE medical_certificate
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE queue
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_1ADAD7EB27E92E18 ON patient
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE patient DROP registered_by_id
        SQL);
    }
}
