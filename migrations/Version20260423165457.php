<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260423165457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activite_programme (idActivite INT AUTO_INCREMENT NOT NULL, jour INT NOT NULL, heureDebut TIME NOT NULL, titre VARCHAR(150) DEFAULT NULL, description LONGTEXT DEFAULT NULL, dureeMinutes INT DEFAULT NULL, typeActivite VARCHAR(100) DEFAULT NULL, idProgramme INT NOT NULL, INDEX IDX_7E28C844C13692A9 (idProgramme), PRIMARY KEY (idActivite)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE appointments (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) DEFAULT \'SCHEDULED\' NOT NULL, created_at DATETIME NOT NULL, patient_id_user INT NOT NULL, plan_id INT NOT NULL, INDEX IDX_6A41727A5BD09FA0 (patient_id_user), INDEX IDX_6A41727AE899029B (plan_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE avis (idAvis INT AUTO_INCREMENT NOT NULL, note INT NOT NULL, commentaire LONGTEXT DEFAULT NULL, dateAvis DATE DEFAULT NULL, idProgramme INT NOT NULL, psychologue_id_user INT NOT NULL, INDEX IDX_8F91ABF0C13692A9 (idProgramme), INDEX IDX_8F91ABF065708D1C (psychologue_id_user), PRIMARY KEY (idAvis)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cabinet (id_cabinet INT AUTO_INCREMENT NOT NULL, adresse VARCHAR(255) NOT NULL, ville VARCHAR(100) NOT NULL, horaires VARCHAR(100) DEFAULT NULL, description LONGTEXT DEFAULT NULL, valide TINYINT DEFAULT 0 NOT NULL, archive TINYINT DEFAULT 0 NOT NULL, PRIMARY KEY (id_cabinet)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commentaire (id_comment INT AUTO_INCREMENT NOT NULL, auteur_role VARCHAR(20) NOT NULL, contenu LONGTEXT NOT NULL, nb_likes INT DEFAULT 0 NOT NULL, date DATETIME NOT NULL, is_hidden TINYINT DEFAULT 0 NOT NULL, hidden_at DATETIME DEFAULT NULL, id_post INT NOT NULL, auteur_id_user INT NOT NULL, hidden_by_id_user INT DEFAULT NULL, parent_comment_id INT DEFAULT NULL, INDEX IDX_67F068BCD1AA708F (id_post), INDEX IDX_67F068BCB891092E (auteur_id_user), INDEX IDX_67F068BC4221E0E3 (hidden_by_id_user), INDEX IDX_67F068BCBF2AF943 (parent_comment_id), PRIMARY KEY (id_comment)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE conversation (id_conversation INT AUTO_INCREMENT NOT NULL, date_creation DATE DEFAULT NULL, statut_conversation VARCHAR(50) DEFAULT NULL, archiver_conversation TINYINT DEFAULT 0 NOT NULL, PRIMARY KEY (id_conversation)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE creneau (id INT AUTO_INCREMENT NOT NULL, date_creneau DATE NOT NULL, heure TIME NOT NULL, statut VARCHAR(20) DEFAULT \'RESERVE\' NOT NULL, disponibilite_id INT NOT NULL, patient_id_user INT NOT NULL, INDEX IDX_F9668B5F2B9D6493 (disponibilite_id), INDEX IDX_F9668B5F5BD09FA0 (patient_id_user), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE disponibilite (id INT AUTO_INCREMENT NOT NULL, jour SMALLINT NOT NULL, heure_debut TIME NOT NULL, heure_fin TIME NOT NULL, duree_consultation INT NOT NULL, cabinet_id INT NOT NULL, INDEX IDX_2CBACE2FD351EC (cabinet_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE forum_notification (id INT AUTO_INCREMENT NOT NULL, is_read TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, recipient_id_user INT NOT NULL, comment_id_comment INT DEFAULT NULL, post_id_post INT DEFAULT NULL, INDEX IDX_878A808D1083E249 (recipient_id_user), INDEX IDX_878A808DC808713C (comment_id_comment), INDEX IDX_878A808DEC42C7BC (post_id_post), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE forum_report (id INT AUTO_INCREMENT NOT NULL, reason VARCHAR(60) NOT NULL, details LONGTEXT DEFAULT NULL, status VARCHAR(20) DEFAULT \'open\' NOT NULL, resolution_action VARCHAR(20) DEFAULT NULL, resolved_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, reporter_id_user INT NOT NULL, post_id_post INT DEFAULT NULL, comment_id_comment INT DEFAULT NULL, resolved_by_id_user INT DEFAULT NULL, INDEX IDX_DC8044557EE0744B (reporter_id_user), INDEX IDX_DC804455EC42C7BC (post_id_post), INDEX IDX_DC804455C808713C (comment_id_comment), INDEX IDX_DC804455DA5EEE5 (resolved_by_id_user), INDEX idx_forum_report_status_created (status, created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE message (id_message INT AUTO_INCREMENT NOT NULL, contenu_message LONGTEXT NOT NULL, date_message DATETIME NOT NULL, est_lu TINYINT DEFAULT 0 NOT NULL, expediteur_role VARCHAR(20) NOT NULL, destinataire_role VARCHAR(20) NOT NULL, expediteur_id_user INT NOT NULL, destinataire_id_user INT NOT NULL, id_conversation INT NOT NULL, INDEX IDX_B6BD307F8B65AEC3 (expediteur_id_user), INDEX IDX_B6BD307F447F7C90 (destinataire_id_user), INDEX IDX_B6BD307FA94F539B (id_conversation), PRIMARY KEY (id_message)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE post (id_post INT AUTO_INCREMENT NOT NULL, auteur_role VARCHAR(20) NOT NULL, titre VARCHAR(255) NOT NULL, contenu LONGTEXT NOT NULL, categorie VARCHAR(100) NOT NULL, nb_likes INT DEFAULT 0 NOT NULL, date DATETIME NOT NULL, image_url VARCHAR(255) DEFAULT NULL, is_hidden TINYINT DEFAULT 0 NOT NULL, is_anonymous TINYINT DEFAULT 0 NOT NULL, hidden_at DATETIME DEFAULT NULL, auteur_id_user INT NOT NULL, hidden_by_id_user INT DEFAULT NULL, INDEX IDX_5A8A6C8DB891092E (auteur_id_user), INDEX IDX_5A8A6C8D4221E0E3 (hidden_by_id_user), PRIMARY KEY (id_post)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE programme_bien_etre (idProgramme INT AUTO_INCREMENT NOT NULL, nom VARCHAR(150) NOT NULL, objectif LONGTEXT DEFAULT NULL, duree INT NOT NULL, statut VARCHAR(50) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, niveauDifficulte VARCHAR(50) DEFAULT NULL, psychologue_id_user INT NOT NULL, INDEX IDX_A5C0BD7E65708D1C (psychologue_id_user), PRIMARY KEY (idProgramme)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE psy_cabinet (id_psy_cabinet INT AUTO_INCREMENT NOT NULL, psychologue_id_user INT NOT NULL, id_cabinet INT NOT NULL, INDEX IDX_895C93AE65708D1C (psychologue_id_user), INDEX IDX_895C93AE9270ACC0 (id_cabinet), UNIQUE INDEX uq_psy_cabinet (psychologue_id_user, id_cabinet), PRIMARY KEY (id_psy_cabinet)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE psychologue_plans (id INT AUTO_INCREMENT NOT NULL, day_of_week VARCHAR(15) NOT NULL, period VARCHAR(10) NOT NULL, max_appointments INT DEFAULT 5 NOT NULL, created_at DATETIME NOT NULL, psychologue_id_user INT NOT NULL, INDEX IDX_A4D23B6965708D1C (psychologue_id_user), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE rating (id INT AUTO_INCREMENT NOT NULL, note INT NOT NULL, patient_id_user INT NOT NULL, cabinet_id INT NOT NULL, INDEX IDX_D88926225BD09FA0 (patient_id_user), INDEX IDX_D8892622D351EC (cabinet_id), UNIQUE INDEX uq_patient_cabinet (patient_id_user, cabinet_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id_user INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, telephone VARCHAR(30) DEFAULT NULL, email VARCHAR(150) NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL, date_inscription DATE DEFAULT NULL, est_actif TINYINT DEFAULT 1 NOT NULL, email_verifie TINYINT DEFAULT 0 NOT NULL, derniere_connexion DATETIME DEFAULT NULL, statut_validation VARCHAR(20) DEFAULT \'approuve\' NOT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY (id_user)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_saved_post (id_user INT NOT NULL, id_post INT NOT NULL, INDEX IDX_F3D258746B3CA4B (id_user), INDEX IDX_F3D25874D1AA708F (id_post), PRIMARY KEY (id_user, id_post)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE activite_programme ADD CONSTRAINT FK_7E28C844C13692A9 FOREIGN KEY (idProgramme) REFERENCES programme_bien_etre (idProgramme) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT FK_6A41727A5BD09FA0 FOREIGN KEY (patient_id_user) REFERENCES users (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT FK_6A41727AE899029B FOREIGN KEY (plan_id) REFERENCES psychologue_plans (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0C13692A9 FOREIGN KEY (idProgramme) REFERENCES programme_bien_etre (idProgramme) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF065708D1C FOREIGN KEY (psychologue_id_user) REFERENCES users (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCD1AA708F FOREIGN KEY (id_post) REFERENCES post (id_post) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCB891092E FOREIGN KEY (auteur_id_user) REFERENCES users (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC4221E0E3 FOREIGN KEY (hidden_by_id_user) REFERENCES users (id_user) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCBF2AF943 FOREIGN KEY (parent_comment_id) REFERENCES commentaire (id_comment) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE creneau ADD CONSTRAINT FK_F9668B5F2B9D6493 FOREIGN KEY (disponibilite_id) REFERENCES disponibilite (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE creneau ADD CONSTRAINT FK_F9668B5F5BD09FA0 FOREIGN KEY (patient_id_user) REFERENCES users (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE disponibilite ADD CONSTRAINT FK_2CBACE2FD351EC FOREIGN KEY (cabinet_id) REFERENCES cabinet (id_cabinet) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forum_notification ADD CONSTRAINT FK_878A808D1083E249 FOREIGN KEY (recipient_id_user) REFERENCES users (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forum_notification ADD CONSTRAINT FK_878A808DC808713C FOREIGN KEY (comment_id_comment) REFERENCES commentaire (id_comment) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forum_notification ADD CONSTRAINT FK_878A808DEC42C7BC FOREIGN KEY (post_id_post) REFERENCES post (id_post) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forum_report ADD CONSTRAINT FK_DC8044557EE0744B FOREIGN KEY (reporter_id_user) REFERENCES users (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forum_report ADD CONSTRAINT FK_DC804455EC42C7BC FOREIGN KEY (post_id_post) REFERENCES post (id_post) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE forum_report ADD CONSTRAINT FK_DC804455C808713C FOREIGN KEY (comment_id_comment) REFERENCES commentaire (id_comment) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE forum_report ADD CONSTRAINT FK_DC804455DA5EEE5 FOREIGN KEY (resolved_by_id_user) REFERENCES users (id_user) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F8B65AEC3 FOREIGN KEY (expediteur_id_user) REFERENCES users (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F447F7C90 FOREIGN KEY (destinataire_id_user) REFERENCES users (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FA94F539B FOREIGN KEY (id_conversation) REFERENCES conversation (id_conversation) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DB891092E FOREIGN KEY (auteur_id_user) REFERENCES users (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8D4221E0E3 FOREIGN KEY (hidden_by_id_user) REFERENCES users (id_user) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE programme_bien_etre ADD CONSTRAINT FK_A5C0BD7E65708D1C FOREIGN KEY (psychologue_id_user) REFERENCES users (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE psy_cabinet ADD CONSTRAINT FK_895C93AE65708D1C FOREIGN KEY (psychologue_id_user) REFERENCES users (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE psy_cabinet ADD CONSTRAINT FK_895C93AE9270ACC0 FOREIGN KEY (id_cabinet) REFERENCES cabinet (id_cabinet) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE psychologue_plans ADD CONSTRAINT FK_A4D23B6965708D1C FOREIGN KEY (psychologue_id_user) REFERENCES users (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rating ADD CONSTRAINT FK_D88926225BD09FA0 FOREIGN KEY (patient_id_user) REFERENCES users (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rating ADD CONSTRAINT FK_D8892622D351EC FOREIGN KEY (cabinet_id) REFERENCES cabinet (id_cabinet) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_saved_post ADD CONSTRAINT FK_F3D258746B3CA4B FOREIGN KEY (id_user) REFERENCES users (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_saved_post ADD CONSTRAINT FK_F3D25874D1AA708F FOREIGN KEY (id_post) REFERENCES post (id_post) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activite_programme DROP FOREIGN KEY FK_7E28C844C13692A9');
        $this->addSql('ALTER TABLE appointments DROP FOREIGN KEY FK_6A41727A5BD09FA0');
        $this->addSql('ALTER TABLE appointments DROP FOREIGN KEY FK_6A41727AE899029B');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0C13692A9');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF065708D1C');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCD1AA708F');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCB891092E');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC4221E0E3');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCBF2AF943');
        $this->addSql('ALTER TABLE creneau DROP FOREIGN KEY FK_F9668B5F2B9D6493');
        $this->addSql('ALTER TABLE creneau DROP FOREIGN KEY FK_F9668B5F5BD09FA0');
        $this->addSql('ALTER TABLE disponibilite DROP FOREIGN KEY FK_2CBACE2FD351EC');
        $this->addSql('ALTER TABLE forum_notification DROP FOREIGN KEY FK_878A808D1083E249');
        $this->addSql('ALTER TABLE forum_notification DROP FOREIGN KEY FK_878A808DC808713C');
        $this->addSql('ALTER TABLE forum_notification DROP FOREIGN KEY FK_878A808DEC42C7BC');
        $this->addSql('ALTER TABLE forum_report DROP FOREIGN KEY FK_DC8044557EE0744B');
        $this->addSql('ALTER TABLE forum_report DROP FOREIGN KEY FK_DC804455EC42C7BC');
        $this->addSql('ALTER TABLE forum_report DROP FOREIGN KEY FK_DC804455C808713C');
        $this->addSql('ALTER TABLE forum_report DROP FOREIGN KEY FK_DC804455DA5EEE5');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F8B65AEC3');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F447F7C90');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FA94F539B');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DB891092E');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8D4221E0E3');
        $this->addSql('ALTER TABLE programme_bien_etre DROP FOREIGN KEY FK_A5C0BD7E65708D1C');
        $this->addSql('ALTER TABLE psy_cabinet DROP FOREIGN KEY FK_895C93AE65708D1C');
        $this->addSql('ALTER TABLE psy_cabinet DROP FOREIGN KEY FK_895C93AE9270ACC0');
        $this->addSql('ALTER TABLE psychologue_plans DROP FOREIGN KEY FK_A4D23B6965708D1C');
        $this->addSql('ALTER TABLE rating DROP FOREIGN KEY FK_D88926225BD09FA0');
        $this->addSql('ALTER TABLE rating DROP FOREIGN KEY FK_D8892622D351EC');
        $this->addSql('ALTER TABLE user_saved_post DROP FOREIGN KEY FK_F3D258746B3CA4B');
        $this->addSql('ALTER TABLE user_saved_post DROP FOREIGN KEY FK_F3D25874D1AA708F');
        $this->addSql('DROP TABLE activite_programme');
        $this->addSql('DROP TABLE appointments');
        $this->addSql('DROP TABLE avis');
        $this->addSql('DROP TABLE cabinet');
        $this->addSql('DROP TABLE commentaire');
        $this->addSql('DROP TABLE conversation');
        $this->addSql('DROP TABLE creneau');
        $this->addSql('DROP TABLE disponibilite');
        $this->addSql('DROP TABLE forum_notification');
        $this->addSql('DROP TABLE forum_report');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE post');
        $this->addSql('DROP TABLE programme_bien_etre');
        $this->addSql('DROP TABLE psy_cabinet');
        $this->addSql('DROP TABLE psychologue_plans');
        $this->addSql('DROP TABLE rating');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE user_saved_post');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
