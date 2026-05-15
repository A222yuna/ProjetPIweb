<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260422151328 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE availability_exception (id INT AUTO_INCREMENT NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, type VARCHAR(20) NOT NULL, motif LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, cabinet_id INT NOT NULL, psychologue_id_user INT NOT NULL, INDEX IDX_5E25FABD351EC (cabinet_id), INDEX IDX_5E25FAB65708D1C (psychologue_id_user), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE slot_history (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(20) NOT NULL, entity_type VARCHAR(50) NOT NULL, entity_id INT DEFAULT NULL, old_state JSON DEFAULT NULL, new_state JSON DEFAULT NULL, created_at DATETIME NOT NULL, user_id_user INT DEFAULT NULL, INDEX IDX_C9BC12EF5EBED441 (user_id_user), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE availability_exception ADD CONSTRAINT FK_5E25FABD351EC FOREIGN KEY (cabinet_id) REFERENCES cabinet (id_cabinet) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE availability_exception ADD CONSTRAINT FK_5E25FAB65708D1C FOREIGN KEY (psychologue_id_user) REFERENCES users (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE slot_history ADD CONSTRAINT FK_C9BC12EF5EBED441 FOREIGN KEY (user_id_user) REFERENCES users (id_user) ON DELETE SET NULL');
        $this->addSql('DROP INDEX idx_auteur ON post');
        $this->addSql('ALTER TABLE post CHANGE auteur_role auteur_role VARCHAR(20) NOT NULL, CHANGE contenu contenu LONGTEXT NOT NULL, CHANGE nb_likes nb_likes INT DEFAULT 0 NOT NULL, CHANGE date date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE programme_bien_etre DROP FOREIGN KEY `programme_bien_etre_ibfk_1`');
        $this->addSql('ALTER TABLE programme_bien_etre CHANGE objectif objectif LONGTEXT DEFAULT NULL');
        $this->addSql('DROP INDEX psychologue_id_user ON programme_bien_etre');
        $this->addSql('CREATE INDEX IDX_A5C0BD7E65708D1C ON programme_bien_etre (psychologue_id_user)');
        $this->addSql('ALTER TABLE programme_bien_etre ADD CONSTRAINT `programme_bien_etre_ibfk_1` FOREIGN KEY (psychologue_id_user) REFERENCES users (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE psy_cabinet DROP FOREIGN KEY `psy_cabinet_ibfk_1`');
        $this->addSql('ALTER TABLE psy_cabinet DROP FOREIGN KEY `psy_cabinet_ibfk_2`');
        $this->addSql('ALTER TABLE psy_cabinet DROP FOREIGN KEY `FK_MC_PSYCAB_CABINET`');
        $this->addSql('ALTER TABLE psy_cabinet DROP FOREIGN KEY `psy_cabinet_ibfk_2`');
        $this->addSql('ALTER TABLE psy_cabinet DROP date_debut, DROP date_fin');
        $this->addSql('DROP INDEX fk_mc_psycab_cabinet ON psy_cabinet');
        $this->addSql('CREATE INDEX IDX_895C93AE9270ACC0 ON psy_cabinet (id_cabinet)');
        $this->addSql('ALTER TABLE psy_cabinet ADD CONSTRAINT `FK_MC_PSYCAB_CABINET` FOREIGN KEY (id_cabinet) REFERENCES cabinet (id_cabinet) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE psy_cabinet ADD CONSTRAINT `psy_cabinet_ibfk_2` FOREIGN KEY (id_cabinet) REFERENCES cabinet (id_cabinet) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE psychologue_plans DROP FOREIGN KEY `psychologue_plans_ibfk_1`');
        $this->addSql('ALTER TABLE psychologue_plans CHANGE day_of_week day_of_week VARCHAR(15) NOT NULL, CHANGE period period VARCHAR(10) NOT NULL, CHANGE max_appointments max_appointments INT DEFAULT 5 NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX psychologue_id_user ON psychologue_plans');
        $this->addSql('CREATE INDEX IDX_A4D23B6965708D1C ON psychologue_plans (psychologue_id_user)');
        $this->addSql('ALTER TABLE psychologue_plans ADD CONSTRAINT `psychologue_plans_ibfk_1` FOREIGN KEY (psychologue_id_user) REFERENCES users (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rating DROP FOREIGN KEY `rating_ibfk_2`');
        $this->addSql('DROP INDEX cabinet_id ON rating');
        $this->addSql('CREATE INDEX IDX_D8892622D351EC ON rating (cabinet_id)');
        $this->addSql('ALTER TABLE rating ADD CONSTRAINT `rating_ibfk_2` FOREIGN KEY (cabinet_id) REFERENCES cabinet (id_cabinet) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_role ON users');
        $this->addSql('DROP INDEX idx_email ON users');
        $this->addSql('ALTER TABLE users CHANGE role role VARCHAR(20) NOT NULL, CHANGE date_inscription date_inscription DATE DEFAULT NULL, CHANGE est_actif est_actif TINYINT DEFAULT 1 NOT NULL, CHANGE email_verifie email_verifie TINYINT DEFAULT 0 NOT NULL, CHANGE statut_validation statut_validation VARCHAR(20) DEFAULT \'approuve\' NOT NULL');
        $this->addSql('DROP INDEX email ON users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE availability_exception DROP FOREIGN KEY FK_5E25FABD351EC');
        $this->addSql('ALTER TABLE availability_exception DROP FOREIGN KEY FK_5E25FAB65708D1C');
        $this->addSql('ALTER TABLE slot_history DROP FOREIGN KEY FK_C9BC12EF5EBED441');
        $this->addSql('DROP TABLE availability_exception');
        $this->addSql('DROP TABLE slot_history');
        $this->addSql('ALTER TABLE post CHANGE auteur_role auteur_role ENUM(\'Patient\', \'Psychologue\') NOT NULL, CHANGE contenu contenu TEXT NOT NULL, CHANGE nb_likes nb_likes INT DEFAULT 0, CHANGE date date DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX idx_auteur ON post (auteur_id_user, auteur_role)');
        $this->addSql('ALTER TABLE programme_bien_etre DROP FOREIGN KEY FK_A5C0BD7E65708D1C');
        $this->addSql('ALTER TABLE programme_bien_etre CHANGE objectif objectif TEXT DEFAULT NULL');
        $this->addSql('DROP INDEX idx_a5c0bd7e65708d1c ON programme_bien_etre');
        $this->addSql('CREATE INDEX psychologue_id_user ON programme_bien_etre (psychologue_id_user)');
        $this->addSql('ALTER TABLE programme_bien_etre ADD CONSTRAINT FK_A5C0BD7E65708D1C FOREIGN KEY (psychologue_id_user) REFERENCES users (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE psychologue_plans DROP FOREIGN KEY FK_A4D23B6965708D1C');
        $this->addSql('ALTER TABLE psychologue_plans CHANGE day_of_week day_of_week ENUM(\'MONDAY\', \'TUESDAY\', \'WEDNESDAY\', \'THURSDAY\', \'FRIDAY\', \'SATURDAY\', \'SUNDAY\') NOT NULL, CHANGE period period ENUM(\'DAY\', \'NIGHT\') NOT NULL, CHANGE max_appointments max_appointments INT DEFAULT 5, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('DROP INDEX idx_a4d23b6965708d1c ON psychologue_plans');
        $this->addSql('CREATE INDEX psychologue_id_user ON psychologue_plans (psychologue_id_user)');
        $this->addSql('ALTER TABLE psychologue_plans ADD CONSTRAINT FK_A4D23B6965708D1C FOREIGN KEY (psychologue_id_user) REFERENCES users (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE psy_cabinet DROP FOREIGN KEY FK_895C93AE9270ACC0');
        $this->addSql('ALTER TABLE psy_cabinet ADD date_debut DATE DEFAULT CURRENT_DATE NOT NULL, ADD date_fin DATE DEFAULT NULL');
        $this->addSql('DROP INDEX idx_895c93ae9270acc0 ON psy_cabinet');
        $this->addSql('CREATE INDEX FK_MC_PSYCAB_CABINET ON psy_cabinet (id_cabinet)');
        $this->addSql('ALTER TABLE psy_cabinet ADD CONSTRAINT FK_895C93AE9270ACC0 FOREIGN KEY (id_cabinet) REFERENCES cabinet (id_cabinet) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rating DROP FOREIGN KEY FK_D8892622D351EC');
        $this->addSql('DROP INDEX idx_d8892622d351ec ON rating');
        $this->addSql('CREATE INDEX cabinet_id ON rating (cabinet_id)');
        $this->addSql('ALTER TABLE rating ADD CONSTRAINT FK_D8892622D351EC FOREIGN KEY (cabinet_id) REFERENCES cabinet (id_cabinet) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE users CHANGE role role ENUM(\'Admin\', \'Psychologue\', \'Patient\') DEFAULT \'Patient\' NOT NULL, CHANGE date_inscription date_inscription DATE DEFAULT CURRENT_DATE, CHANGE est_actif est_actif TINYINT DEFAULT 1, CHANGE email_verifie email_verifie TINYINT DEFAULT 0, CHANGE statut_validation statut_validation ENUM(\'en_attente\', \'approuve\', \'rejete\') DEFAULT \'en_attente\'');
        $this->addSql('CREATE INDEX idx_role ON users (role)');
        $this->addSql('CREATE INDEX idx_email ON users (email)');
        $this->addSql('DROP INDEX uniq_1483a5e9e7927c74 ON users');
        $this->addSql('CREATE UNIQUE INDEX email ON users (email)');
    }
}
