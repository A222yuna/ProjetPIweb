<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create emotion_analysis table for PsyMood AI feature';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE IF NOT EXISTS emotion_analysis (
                id INT AUTO_INCREMENT NOT NULL,
                cabinet_id INT NOT NULL,
                total_reviews INT NOT NULL DEFAULT 0,
                positif_pct NUMERIC(5,2) NOT NULL DEFAULT 0,
                neutre_pct  NUMERIC(5,2) NOT NULL DEFAULT 0,
                negatif_pct NUMERIC(5,2) NOT NULL DEFAULT 0,
                confiance_score    NUMERIC(5,2) NOT NULL DEFAULT 0,
                satisfaction_score NUMERIC(5,2) NOT NULL DEFAULT 0,
                anxiete_score      NUMERIC(5,2) NOT NULL DEFAULT 0,
                deception_score    NUMERIC(5,2) NOT NULL DEFAULT 0,
                stress_score       NUMERIC(5,2) NOT NULL DEFAULT 0,
                gratitude_score    NUMERIC(5,2) NOT NULL DEFAULT 0,
                alerte_active TINYINT(1) NOT NULL DEFAULT 0,
                top_mots      JSON DEFAULT NULL,
                details_analyse JSON DEFAULT NULL,
                analysed_at DATETIME NOT NULL,
                INDEX IDX_EA_CABINET (cabinet_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        $this->addSql('
            ALTER TABLE emotion_analysis
                ADD CONSTRAINT FK_EA_CABINET
                FOREIGN KEY (cabinet_id) REFERENCES cabinet (id_cabinet) ON DELETE CASCADE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE emotion_analysis DROP FOREIGN KEY FK_EA_CABINET');
        $this->addSql('DROP TABLE IF EXISTS emotion_analysis');
    }
}
