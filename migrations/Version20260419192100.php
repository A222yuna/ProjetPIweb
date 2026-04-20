<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260419192100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add soft-hide fields to post and commentaire.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE post ADD is_hidden TINYINT(1) NOT NULL DEFAULT 0, ADD hidden_at DATETIME DEFAULT NULL, ADD hidden_by_id_user INT DEFAULT NULL");
        $this->addSql("ALTER TABLE commentaire ADD is_hidden TINYINT(1) NOT NULL DEFAULT 0, ADD hidden_at DATETIME DEFAULT NULL, ADD hidden_by_id_user INT DEFAULT NULL");

        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_post_hidden_by FOREIGN KEY (hidden_by_id_user) REFERENCES users (id_user) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_commentaire_hidden_by FOREIGN KEY (hidden_by_id_user) REFERENCES users (id_user) ON DELETE SET NULL');

        $this->addSql('CREATE INDEX IDX_post_is_hidden ON post (is_hidden)');
        $this->addSql('CREATE INDEX IDX_commentaire_is_hidden ON commentaire (is_hidden)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_post_is_hidden ON post');
        $this->addSql('DROP INDEX IDX_commentaire_is_hidden ON commentaire');

        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_commentaire_hidden_by');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_post_hidden_by');

        $this->addSql('ALTER TABLE commentaire DROP is_hidden, DROP hidden_at, DROP hidden_by_id_user');
        $this->addSql('ALTER TABLE post DROP is_hidden, DROP hidden_at, DROP hidden_by_id_user');
    }
}

