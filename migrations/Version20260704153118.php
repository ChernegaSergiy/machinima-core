<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260704153118 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add display_name column to user_data';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_data ADD COLUMN display_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_data DROP COLUMN display_name');
    }
}
