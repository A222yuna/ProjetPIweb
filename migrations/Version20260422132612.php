<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422132612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reputation fields to cabinet (already applied) and multi-criteria fields to rating';
    }

    public function up(Schema $schema): void
    {
        // Cabinet columns were already added by a partial run — skip them
        // Rating: add multi-criteria columns
        $this->addSql('ALTER TABLE rating
            ADD note_ecoute NUMERIC(3, 1) DEFAULT NULL,
            ADD note_competence NUMERIC(3, 1) DEFAULT NULL,
            ADD note_ponctualite NUMERIC(3, 1) DEFAULT NULL,
            ADD note_environnement NUMERIC(3, 1) DEFAULT NULL,
            ADD note_globale NUMERIC(3, 1) DEFAULT NULL,
            ADD commentaire_rating LONGTEXT DEFAULT NULL,
            ADD is_verified TINYINT(1) DEFAULT 0 NOT NULL,
            ADD created_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cabinet
            DROP reputation_score,
            DROP reputation_badge,
            DROP score_updated_at');

        $this->addSql('ALTER TABLE rating
            DROP note_ecoute,
            DROP note_competence,
            DROP note_ponctualite,
            DROP note_environnement,
            DROP note_globale,
            DROP commentaire_rating,
            DROP is_verified,
            DROP created_at');
    }
}
