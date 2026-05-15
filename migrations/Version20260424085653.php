<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260424085653 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Add missing presentation column to users table
        $this->addSql('ALTER TABLE users ADD COLUMN presentation LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Revert: Remove presentation column
        $this->addSql('ALTER TABLE users DROP COLUMN presentation');
    }
}
