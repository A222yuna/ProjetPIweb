<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260424000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add nb_views to post and post_reaction table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post ADD nb_views INT NOT NULL DEFAULT 0');

        $this->addSql('CREATE TABLE post_reaction (
            id          INT AUTO_INCREMENT NOT NULL,
            post_id     INT NOT NULL,
            user_id     INT NOT NULL,
            emoji       VARCHAR(10) NOT NULL,
            created_at  DATETIME NOT NULL,
            PRIMARY KEY(id),
            UNIQUE KEY uq_post_user_reaction (post_id, user_id),
            INDEX idx_reaction_post (post_id),
            CONSTRAINT fk_reaction_post FOREIGN KEY (post_id) REFERENCES post (id_post) ON DELETE CASCADE,
            CONSTRAINT fk_reaction_user FOREIGN KEY (user_id) REFERENCES users (id_user) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE post_reaction');
        $this->addSql('ALTER TABLE post DROP COLUMN nb_views');
    }
}
