<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add availability_exception and slot_history tables for intelligent slot management';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE IF NOT EXISTS availability_exception (
                id INT AUTO_INCREMENT NOT NULL,
                cabinet_id INT NOT NULL,
                psychologue_id_user INT NOT NULL,
                date_debut DATETIME NOT NULL,
                date_fin DATETIME NOT NULL,
                type VARCHAR(20) NOT NULL DEFAULT \'BLOCAGE\',
                motif LONGTEXT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                INDEX IDX_AE_CABINET (cabinet_id),
                INDEX IDX_AE_PSY (psychologue_id_user),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        $this->addSql('
            ALTER TABLE availability_exception
                ADD CONSTRAINT FK_AE_CABINET FOREIGN KEY (cabinet_id) REFERENCES cabinet (id_cabinet) ON DELETE CASCADE,
                ADD CONSTRAINT FK_AE_PSY FOREIGN KEY (psychologue_id_user) REFERENCES users (id_user) ON DELETE CASCADE
        ');

        $this->addSql('
            CREATE TABLE IF NOT EXISTS slot_history (
                id INT AUTO_INCREMENT NOT NULL,
                user_id_user INT DEFAULT NULL,
                action VARCHAR(20) NOT NULL,
                entity_type VARCHAR(50) NOT NULL,
                entity_id INT DEFAULT NULL,
                old_state JSON DEFAULT NULL,
                new_state JSON DEFAULT NULL,
                created_at DATETIME NOT NULL,
                INDEX IDX_SH_USER (user_id_user),
                INDEX IDX_SH_ENTITY (entity_type, entity_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        $this->addSql('
            ALTER TABLE slot_history
                ADD CONSTRAINT FK_SH_USER FOREIGN KEY (user_id_user) REFERENCES users (id_user) ON DELETE SET NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE availability_exception DROP FOREIGN KEY FK_AE_CABINET');
        $this->addSql('ALTER TABLE availability_exception DROP FOREIGN KEY FK_AE_PSY');
        $this->addSql('DROP TABLE IF EXISTS availability_exception');
        $this->addSql('ALTER TABLE slot_history DROP FOREIGN KEY FK_SH_USER');
        $this->addSql('DROP TABLE IF EXISTS slot_history');
    }
}
