<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260627095617 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_proposal ADD CONSTRAINT FK_14A16B54F675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE proposal_participant ADD CONSTRAINT FK_6E8B8D2B2A2C625A FOREIGN KEY (game_proposal_id) REFERENCES game_proposal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE proposal_participant ADD CONSTRAINT FK_6E8B8D2BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FCD53EDB6 FOREIGN KEY (receiver_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user ADD handedness VARCHAR(1) DEFAULT NULL, ADD has_court TINYINT DEFAULT NULL, ADD preferred_surface VARCHAR(10) DEFAULT NULL, ADD accept_messages TINYINT DEFAULT 1 NOT NULL, ADD notify_messages TINYINT DEFAULT 1 NOT NULL, ADD notify_proposal_replies TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE user_partners ADD CONSTRAINT FK_F66B564A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user_partners ADD CONSTRAINT FK_F66B5649393F8FE FOREIGN KEY (partner_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_proposal DROP FOREIGN KEY FK_14A16B54F675F31B');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FF624B39D');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FCD53EDB6');
        $this->addSql('ALTER TABLE proposal_participant DROP FOREIGN KEY FK_6E8B8D2B2A2C625A');
        $this->addSql('ALTER TABLE proposal_participant DROP FOREIGN KEY FK_6E8B8D2BA76ED395');
        $this->addSql('ALTER TABLE `user` DROP handedness, DROP has_court, DROP preferred_surface, DROP accept_messages, DROP notify_messages, DROP notify_proposal_replies');
        $this->addSql('ALTER TABLE user_partners DROP FOREIGN KEY FK_F66B564A76ED395');
        $this->addSql('ALTER TABLE user_partners DROP FOREIGN KEY FK_F66B5649393F8FE');
    }
}
