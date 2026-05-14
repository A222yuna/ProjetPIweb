<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add forum_notification table for local bell notifications';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE forum_notification (
            id INT AUTO_INCREMENT NOT NULL,
            recipient_id_user INT NOT NULL,
            comment_id_comment INT DEFAULT NULL,
            post_id_post INT DEFAULT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_forum_notif_recipient (recipient_id_user),
            INDEX IDX_forum_notif_comment (comment_id_comment),
            INDEX IDX_forum_notif_post (post_id_post),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE forum_notification
            ADD CONSTRAINT FK_forum_notif_recipient FOREIGN KEY (recipient_id_user) REFERENCES users (id_user) ON DELETE CASCADE,
            ADD CONSTRAINT FK_forum_notif_comment FOREIGN KEY (comment_id_comment) REFERENCES commentaire (id_comment) ON DELETE CASCADE,
            ADD CONSTRAINT FK_forum_notif_post FOREIGN KEY (post_id_post) REFERENCES post (id_post) ON DELETE CASCADE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE forum_notification DROP FOREIGN KEY FK_forum_notif_recipient');
        $this->addSql('ALTER TABLE forum_notification DROP FOREIGN KEY FK_forum_notif_comment');
        $this->addSql('ALTER TABLE forum_notification DROP FOREIGN KEY FK_forum_notif_post');
        $this->addSql('DROP TABLE forum_notification');
    }
}
