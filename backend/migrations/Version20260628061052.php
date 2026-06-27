<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260628061052 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE report (id INT AUTO_INCREMENT NOT NULL, target_type VARCHAR(20) NOT NULL, target_id INT NOT NULL, category VARCHAR(30) NOT NULL, reason LONGTEXT DEFAULT NULL, status VARCHAR(15) DEFAULT \'pending\' NOT NULL, created_at DATETIME NOT NULL, reporter_id INT NOT NULL, reported_user_id INT NOT NULL, INDEX IDX_C42F7784E1CFE6F5 (reporter_id), INDEX IDX_C42F7784E7566E (reported_user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F7784E1CFE6F5 FOREIGN KEY (reporter_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F7784E7566E FOREIGN KEY (reported_user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game_proposal ADD CONSTRAINT FK_14A16B54F675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE game_proposal ADD CONSTRAINT FK_14A16B546C066AFE FOREIGN KEY (target_user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE game_proposal RENAME INDEX idx_14a16b5466ccb0ec TO IDX_14A16B546C066AFE');
        $this->addSql('ALTER TABLE proposal_participant ADD CONSTRAINT FK_6E8B8D2B2A2C625A FOREIGN KEY (game_proposal_id) REFERENCES game_proposal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE proposal_participant ADD CONSTRAINT FK_6E8B8D2BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FCD53EDB6 FOREIGN KEY (receiver_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE password_reset_token CHANGE expires_at expires_at DATETIME NOT NULL, CHANGE used_at used_at DATETIME DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE password_reset_token ADD CONSTRAINT FK_6B7BA4B6A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE password_reset_token RENAME INDEX uniq_6b7bcef65f37a13b TO UNIQ_6B7BA4B65F37A13B');
        $this->addSql('ALTER TABLE password_reset_token RENAME INDEX idx_6b7bcef6a76ed395 TO IDX_6B7BA4B6A76ED395');
        $this->addSql('ALTER TABLE user ADD is_suspended TINYINT DEFAULT 0 NOT NULL, CHANGE preferred_surface preferred_surface JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE user RENAME INDEX uniq_8d93d649_public_id TO UNIQ_8D93D649B5B48B91');
        $this->addSql('ALTER TABLE user_partners ADD CONSTRAINT FK_F66B564A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user_partners ADD CONSTRAINT FK_F66B5649393F8FE FOREIGN KEY (partner_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE report DROP FOREIGN KEY FK_C42F7784E1CFE6F5');
        $this->addSql('ALTER TABLE report DROP FOREIGN KEY FK_C42F7784E7566E');
        $this->addSql('DROP TABLE report');
        $this->addSql('ALTER TABLE game_proposal DROP FOREIGN KEY FK_14A16B54F675F31B');
        $this->addSql('ALTER TABLE game_proposal DROP FOREIGN KEY FK_14A16B546C066AFE');
        $this->addSql('ALTER TABLE game_proposal RENAME INDEX idx_14a16b546c066afe TO IDX_14A16B5466CCB0EC');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FF624B39D');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FCD53EDB6');
        $this->addSql('ALTER TABLE password_reset_token DROP FOREIGN KEY FK_6B7BA4B6A76ED395');
        $this->addSql('ALTER TABLE password_reset_token CHANGE expires_at expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE used_at used_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE password_reset_token RENAME INDEX idx_6b7ba4b6a76ed395 TO IDX_6B7BCEF6A76ED395');
        $this->addSql('ALTER TABLE password_reset_token RENAME INDEX uniq_6b7ba4b65f37a13b TO UNIQ_6B7BCEF65F37A13B');
        $this->addSql('ALTER TABLE proposal_participant DROP FOREIGN KEY FK_6E8B8D2B2A2C625A');
        $this->addSql('ALTER TABLE proposal_participant DROP FOREIGN KEY FK_6E8B8D2BA76ED395');
        $this->addSql('ALTER TABLE `user` DROP is_suspended, CHANGE preferred_surface preferred_surface LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE `user` RENAME INDEX uniq_8d93d649b5b48b91 TO UNIQ_8D93D649_PUBLIC_ID');
        $this->addSql('ALTER TABLE user_partners DROP FOREIGN KEY FK_F66B564A76ED395');
        $this->addSql('ALTER TABLE user_partners DROP FOREIGN KEY FK_F66B5649393F8FE');
    }
}
