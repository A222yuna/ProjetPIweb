<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260408135933 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initialise les tables cabinet/disponibilite/psy_cabinet pour le module Gestion des Cabinets.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS users (id_user INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, telephone VARCHAR(30) DEFAULT NULL, email VARCHAR(150) NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL, date_inscription DATE DEFAULT NULL, est_actif TINYINT(1) DEFAULT 1 NOT NULL, email_verifie TINYINT(1) DEFAULT 0 NOT NULL, derniere_connexion DATETIME DEFAULT NULL, statut_validation VARCHAR(20) DEFAULT \'approuve\' NOT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY (id_user)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS cabinet (id_cabinet INT AUTO_INCREMENT NOT NULL, adresse VARCHAR(255) NOT NULL, ville VARCHAR(100) NOT NULL, horaires VARCHAR(100) DEFAULT NULL, description LONGTEXT DEFAULT NULL, valide TINYINT(1) DEFAULT 0 NOT NULL, archive TINYINT(1) DEFAULT 0 NOT NULL, PRIMARY KEY (id_cabinet)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS disponibilite (id INT AUTO_INCREMENT NOT NULL, cabinet_id INT NOT NULL, jour SMALLINT NOT NULL, heure_debut TIME NOT NULL, heure_fin TIME NOT NULL, duree_consultation INT NOT NULL, INDEX IDX_2CBACE2FD351EC (cabinet_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS psy_cabinet (id_psy_cabinet INT AUTO_INCREMENT NOT NULL, psychologue_id_user INT NOT NULL, id_cabinet INT NOT NULL, INDEX IDX_895C93AE65708D1C (psychologue_id_user), INDEX IDX_895C93AE9270ACC0 (id_cabinet), UNIQUE INDEX uq_psy_cabinet (psychologue_id_user, id_cabinet), PRIMARY KEY (id_psy_cabinet)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE disponibilite ADD CONSTRAINT FK_MC_DISPO_CABINET FOREIGN KEY (cabinet_id) REFERENCES cabinet (id_cabinet) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE psy_cabinet ADD CONSTRAINT FK_MC_PSYCAB_USER FOREIGN KEY (psychologue_id_user) REFERENCES users (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE psy_cabinet ADD CONSTRAINT FK_MC_PSYCAB_CABINET FOREIGN KEY (id_cabinet) REFERENCES cabinet (id_cabinet) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE psy_cabinet DROP FOREIGN KEY FK_MC_PSYCAB_USER');
        $this->addSql('ALTER TABLE psy_cabinet DROP FOREIGN KEY FK_MC_PSYCAB_CABINET');
        $this->addSql('ALTER TABLE disponibilite DROP FOREIGN KEY FK_MC_DISPO_CABINET');
        $this->addSql('DROP TABLE IF EXISTS psy_cabinet');
        $this->addSql('DROP TABLE IF EXISTS disponibilite');
        $this->addSql('DROP TABLE IF EXISTS cabinet');
    }
}
