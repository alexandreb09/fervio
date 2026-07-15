<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make user.last_name nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user MODIFY last_name VARCHAR(50) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE user SET last_name = '' WHERE last_name IS NULL");
        $this->addSql("ALTER TABLE user MODIFY last_name VARCHAR(50) NOT NULL");
    }
}
