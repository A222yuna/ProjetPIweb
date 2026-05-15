<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create notification table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE IF NOT EXISTS notification (
                id INT AUTO_INCREMENT NOT NULL,
                recipient_id_user INT NOT NULL,
                type VARCHAR(50) NOT NULL DEFAULT \'info\',
                title VARCHAR(255) NOT NULL DEFAULT \'\',
                message LONGTEXT NOT NULL,
                link VARCHAR(255) DEFAULT NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                INDEX IDX_NOTIF_RECIPIENT (recipient_id_user),
                INDEX IDX_NOTIF_READ (is_read),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        $this->addSql('
            ALTER TABLE notification
                ADD CONSTRAINT FK_NOTIF_USER
                FOREIGN KEY (recipient_id_user) REFERENCES users (id_user) ON DELETE CASCADE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_NOTIF_USER');
        $this->addSql('DROP TABLE IF EXISTS notification');
    }
}
