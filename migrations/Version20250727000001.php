<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Populate consultationId for existing queue records
 */
final class Version20250727000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Populate consultationId for existing queue records by matching patient, doctor, and date';
    }

    public function up(Schema $schema): void
    {
        // Update queue records to link them with their corresponding consultations
        // Match by patient, doctor, and date (same day)
        $this->addSql('
            UPDATE queue q
            JOIN consultation c ON (
                q.patient_id = c.patient_id 
                AND q.doctor_id = c.doctor_id 
                AND DATE(q.queue_date_time) = DATE(c.consultation_date)
            )
            SET q.consultation_id = c.id
            WHERE q.consultation_id IS NULL
        ');
    }

    public function down(Schema $schema): void
    {
        // Clear consultation_id values
        $this->addSql('UPDATE queue SET consultation_id = NULL');
    }
}