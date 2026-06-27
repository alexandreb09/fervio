<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260627092301 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_partners (user_id INT NOT NULL, partner_id INT NOT NULL, INDEX IDX_F66B564A76ED395 (user_id), INDEX IDX_F66B5649393F8FE (partner_id), PRIMARY KEY (user_id, partner_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_partners ADD CONSTRAINT FK_F66B564A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user_partners ADD CONSTRAINT FK_F66B5649393F8FE FOREIGN KEY (partner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE game_proposal ADD CONSTRAINT FK_14A16B54F675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE proposal_participant ADD CONSTRAINT FK_6E8B8D2B2A2C625A FOREIGN KEY (game_proposal_id) REFERENCES game_proposal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE proposal_participant ADD CONSTRAINT FK_6E8B8D2BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FCD53EDB6 FOREIGN KEY (receiver_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_partners DROP FOREIGN KEY FK_F66B564A76ED395');
        $this->addSql('ALTER TABLE user_partners DROP FOREIGN KEY FK_F66B5649393F8FE');
        $this->addSql('DROP TABLE user_partners');
        $this->addSql('ALTER TABLE game_proposal DROP FOREIGN KEY FK_14A16B54F675F31B');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FF624B39D');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FCD53EDB6');
        $this->addSql('ALTER TABLE proposal_participant DROP FOREIGN KEY FK_6E8B8D2B2A2C625A');
        $this->addSql('ALTER TABLE proposal_participant DROP FOREIGN KEY FK_6E8B8D2BA76ED395');
    }
}
