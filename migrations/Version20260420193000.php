<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260420193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add saved posts (user_saved_post join table).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE user_saved_post (
  id_user INT NOT NULL,
  id_post INT NOT NULL,
  INDEX IDX_user_saved_post_user (id_user),
  INDEX IDX_user_saved_post_post (id_post),
  PRIMARY KEY(id_user, id_post)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql('ALTER TABLE user_saved_post ADD CONSTRAINT FK_user_saved_post_user FOREIGN KEY (id_user) REFERENCES users (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_saved_post ADD CONSTRAINT FK_user_saved_post_post FOREIGN KEY (id_post) REFERENCES post (id_post) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_saved_post');
    }
}

