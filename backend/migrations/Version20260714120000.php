<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email verification fields to user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD email_verification_token VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD is_email_verified TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP email_verification_token');
        $this->addSql('ALTER TABLE `user` DROP is_email_verified');
    }
}
