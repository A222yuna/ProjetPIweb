<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260419192000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add forum reporting (forum_report table).';
    }

    public function up(Schema $schema): void
    {
        // This migration targets MySQL/MariaDB (see .env DATABASE_URL).
        $this->addSql(<<<'SQL'
CREATE TABLE forum_report (
  id INT AUTO_INCREMENT NOT NULL,
  reporter_id_user INT NOT NULL,
  post_id_post INT DEFAULT NULL,
  comment_id_comment INT DEFAULT NULL,
  resolved_by_id_user INT DEFAULT NULL,
  reason VARCHAR(60) NOT NULL,
  details LONGTEXT DEFAULT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'open',
  resolution_action VARCHAR(20) DEFAULT NULL,
  resolved_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_forum_report_status_created (status, created_at),
  INDEX IDX_forum_report_reporter (reporter_id_user),
  INDEX IDX_forum_report_post (post_id_post),
  INDEX IDX_forum_report_comment (comment_id_comment),
  INDEX IDX_forum_report_resolved_by (resolved_by_id_user),
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql('ALTER TABLE forum_report ADD CONSTRAINT FK_forum_report_reporter FOREIGN KEY (reporter_id_user) REFERENCES users (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forum_report ADD CONSTRAINT FK_forum_report_post FOREIGN KEY (post_id_post) REFERENCES post (id_post) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE forum_report ADD CONSTRAINT FK_forum_report_comment FOREIGN KEY (comment_id_comment) REFERENCES commentaire (id_comment) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE forum_report ADD CONSTRAINT FK_forum_report_resolved_by FOREIGN KEY (resolved_by_id_user) REFERENCES users (id_user) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE forum_report');
    }
}

