<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260627090734 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add uuid to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_proposal ADD CONSTRAINT FK_14A16B54F675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE proposal_participant ADD CONSTRAINT FK_6E8B8D2B2A2C625A FOREIGN KEY (game_proposal_id) REFERENCES game_proposal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE proposal_participant ADD CONSTRAINT FK_6E8B8D2BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FCD53EDB6 FOREIGN KEY (receiver_id) REFERENCES `user` (id)');

        // Add as nullable first so existing rows don't fail
        $this->addSql('ALTER TABLE `user` ADD uuid VARCHAR(36) DEFAULT NULL');

        // Populate existing users with generated UUIDs
        $this->addSql("
            UPDATE `user` SET uuid = LOWER(CONCAT(
                LPAD(HEX(FLOOR(RAND() * 0xFFFFFFFF)), 8, '0'), '-',
                LPAD(HEX(FLOOR(RAND() * 0xFFFF)), 4, '0'), '-',
                '4', LPAD(HEX(FLOOR(RAND() * 0xFFF)), 3, '0'), '-',
                HEX(FLOOR(RAND() * 4 + 8)), LPAD(HEX(FLOOR(RAND() * 0xFFF)), 3, '0'), '-',
                LPAD(HEX(FLOOR(RAND() * 0xFFFFFFFFFFFF)), 12, '0')
            )) WHERE uuid IS NULL
        ");

        // Now enforce NOT NULL and unique
        $this->addSql('ALTER TABLE `user` MODIFY uuid VARCHAR(36) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649D17F50A6 ON `user` (uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_proposal DROP FOREIGN KEY FK_14A16B54F675F31B');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FF624B39D');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FCD53EDB6');
        $this->addSql('ALTER TABLE proposal_participant DROP FOREIGN KEY FK_6E8B8D2B2A2C625A');
        $this->addSql('ALTER TABLE proposal_participant DROP FOREIGN KEY FK_6E8B8D2BA76ED395');
        $this->addSql('DROP INDEX UNIQ_8D93D649D17F50A6 ON `user`');
        $this->addSql('ALTER TABLE `user` DROP uuid');
    }
}
