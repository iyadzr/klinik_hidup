<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250611092531 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE appointment (id INT AUTO_INCREMENT NOT NULL, patient_id INT NOT NULL, doctor_id INT NOT NULL, appointment_date_time DATETIME NOT NULL, reason VARCHAR(1000) DEFAULT NULL, status VARCHAR(20) NOT NULL, notes VARCHAR(1000) DEFAULT NULL, INDEX IDX_FE38F8446B899279 (patient_id), INDEX IDX_FE38F84487F4FB17 (doctor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE clinic_assistant (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(20) NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE consultation (id INT AUTO_INCREMENT NOT NULL, patient_id INT NOT NULL, doctor_id INT NOT NULL, consultation_date DATETIME NOT NULL, created_at DATETIME NOT NULL, diagnosis LONGTEXT NOT NULL, medications LONGTEXT NOT NULL, symptoms LONGTEXT DEFAULT NULL, treatment LONGTEXT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, follow_up_plan LONGTEXT DEFAULT NULL, consultation_fee NUMERIC(10, 2) DEFAULT NULL, medicines_fee NUMERIC(10, 2) DEFAULT NULL, total_amount NUMERIC(10, 2) NOT NULL, is_paid TINYINT(1) NOT NULL, paid_at DATETIME DEFAULT NULL, has_medical_certificate TINYINT(1) DEFAULT NULL, mc_start_date DATETIME DEFAULT NULL, mc_end_date DATETIME DEFAULT NULL, mc_number VARCHAR(255) DEFAULT NULL, mc_running_number VARCHAR(255) DEFAULT NULL, INDEX IDX_964685A66B899279 (patient_id), INDEX IDX_964685A687F4FB17 (doctor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE doctor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(20) NOT NULL, specialization VARCHAR(255) NOT NULL, license_number VARCHAR(255) DEFAULT NULL, working_hours JSON NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE medical_certificate (id INT AUTO_INCREMENT NOT NULL, patient_id INT NOT NULL, doctor_id INT NOT NULL, issue_date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', start_date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', end_date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', diagnosis VARCHAR(255) NOT NULL, remarks VARCHAR(1000) DEFAULT NULL, certificate_number VARCHAR(50) NOT NULL, INDEX IDX_B36515F86B899279 (patient_id), INDEX IDX_B36515F887F4FB17 (doctor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE medication (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, unit_type VARCHAR(50) NOT NULL, unit_description VARCHAR(100) DEFAULT NULL, description VARCHAR(500) DEFAULT NULL, category VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE patient (id INT AUTO_INCREMENT NOT NULL, registered_by_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, nric VARCHAR(20) NOT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(20) NOT NULL, date_of_birth DATE NOT NULL, medical_history VARCHAR(1000) DEFAULT NULL, company VARCHAR(255) DEFAULT NULL, pre_informed_illness VARCHAR(1000) DEFAULT NULL, gender VARCHAR(1) DEFAULT NULL, address VARCHAR(500) DEFAULT NULL, UNIQUE INDEX UNIQ_1ADAD7EBCD4C031E (nric), INDEX IDX_1ADAD7EB27E92E18 (registered_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, consultation_id INT NOT NULL, amount DOUBLE PRECISION NOT NULL, payment_method VARCHAR(20) NOT NULL, payment_date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', reference VARCHAR(255) DEFAULT NULL, notes VARCHAR(1000) DEFAULT NULL, INDEX IDX_6D28840D62FF6CDF (consultation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE prescribed_medication (id INT AUTO_INCREMENT NOT NULL, consultation_id INT NOT NULL, medication_id INT NOT NULL, quantity INT NOT NULL, instructions VARCHAR(500) DEFAULT NULL, prescribed_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_51C16B7062FF6CDF (consultation_id), INDEX IDX_51C16B702C4DE6DA (medication_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE queue (id INT AUTO_INCREMENT NOT NULL, patient_id INT NOT NULL, doctor_id INT NOT NULL, queue_date_time DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', status VARCHAR(20) NOT NULL, queue_number VARCHAR(20) DEFAULT NULL, registration_number INT NOT NULL, INDEX IDX_7FFD7F636B899279 (patient_id), INDEX IDX_7FFD7F6387F4FB17 (doctor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, allowed_pages JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', is_active TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment ADD CONSTRAINT FK_FE38F8446B899279 FOREIGN KEY (patient_id) REFERENCES patient (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment ADD CONSTRAINT FK_FE38F84487F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE consultation ADD CONSTRAINT FK_964685A66B899279 FOREIGN KEY (patient_id) REFERENCES patient (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE consultation ADD CONSTRAINT FK_964685A687F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE medical_certificate ADD CONSTRAINT FK_B36515F86B899279 FOREIGN KEY (patient_id) REFERENCES patient (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE medical_certificate ADD CONSTRAINT FK_B36515F887F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE patient ADD CONSTRAINT FK_1ADAD7EB27E92E18 FOREIGN KEY (registered_by_id) REFERENCES clinic_assistant (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE payment ADD CONSTRAINT FK_6D28840D62FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE prescribed_medication ADD CONSTRAINT FK_51C16B7062FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE prescribed_medication ADD CONSTRAINT FK_51C16B702C4DE6DA FOREIGN KEY (medication_id) REFERENCES medication (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE queue ADD CONSTRAINT FK_7FFD7F636B899279 FOREIGN KEY (patient_id) REFERENCES patient (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE queue ADD CONSTRAINT FK_7FFD7F6387F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F8446B899279
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F84487F4FB17
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE consultation DROP FOREIGN KEY FK_964685A66B899279
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE consultation DROP FOREIGN KEY FK_964685A687F4FB17
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE medical_certificate DROP FOREIGN KEY FK_B36515F86B899279
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE medical_certificate DROP FOREIGN KEY FK_B36515F887F4FB17
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE patient DROP FOREIGN KEY FK_1ADAD7EB27E92E18
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D62FF6CDF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE prescribed_medication DROP FOREIGN KEY FK_51C16B7062FF6CDF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE prescribed_medication DROP FOREIGN KEY FK_51C16B702C4DE6DA
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE queue DROP FOREIGN KEY FK_7FFD7F636B899279
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE queue DROP FOREIGN KEY FK_7FFD7F6387F4FB17
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE appointment
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE clinic_assistant
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE consultation
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE doctor
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE medical_certificate
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE medication
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE patient
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE payment
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE prescribed_medication
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE queue
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user
        SQL);
    }
}
