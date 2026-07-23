<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260723120500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add join_mode to game_proposal and create proposal_join_request table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE proposal_join_request (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, proposal_id INT NOT NULL, requester_id INT NOT NULL, INDEX IDX_2F8729ABF4792058 (proposal_id), INDEX IDX_2F8729ABED442CF4 (requester_id), UNIQUE INDEX UNIQ_PROPOSAL_REQUESTER (proposal_id, requester_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE proposal_join_request ADD CONSTRAINT FK_2F8729ABF4792058 FOREIGN KEY (proposal_id) REFERENCES game_proposal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE proposal_join_request ADD CONSTRAINT FK_2F8729ABED442CF4 FOREIGN KEY (requester_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game_proposal ADD join_mode VARCHAR(20) DEFAULT \'auto\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE proposal_join_request DROP FOREIGN KEY FK_2F8729ABF4792058');
        $this->addSql('ALTER TABLE proposal_join_request DROP FOREIGN KEY FK_2F8729ABED442CF4');
        $this->addSql('DROP TABLE proposal_join_request');
        $this->addSql('ALTER TABLE game_proposal DROP join_mode');
    }
}
