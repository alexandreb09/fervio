<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add postal_code to user and game_proposal';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD postal_code VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE game_proposal ADD postal_code VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP COLUMN postal_code');
        $this->addSql('ALTER TABLE game_proposal DROP COLUMN postal_code');
    }
}
