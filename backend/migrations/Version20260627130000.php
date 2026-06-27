<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260627130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add private proposal support: isPrivate + targetUser on game_proposal, acceptPrivateProposals on user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_proposal ADD is_private TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE game_proposal ADD target_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE game_proposal ADD CONSTRAINT FK_14A16B5466CCB0EC FOREIGN KEY (target_user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_14A16B5466CCB0EC ON game_proposal (target_user_id)');
        $this->addSql('ALTER TABLE `user` ADD accept_private_proposals TINYINT(1) NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_proposal DROP FOREIGN KEY FK_14A16B5466CCB0EC');
        $this->addSql('DROP INDEX IDX_14A16B5466CCB0EC ON game_proposal');
        $this->addSql('ALTER TABLE game_proposal DROP is_private, DROP target_user_id');
        $this->addSql('ALTER TABLE `user` DROP accept_private_proposals');
    }
}
