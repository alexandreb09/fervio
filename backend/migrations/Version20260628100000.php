<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260628100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add publicId (random int) to game_proposal';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_proposal ADD public_id INT DEFAULT NULL');
        $this->addSql('UPDATE game_proposal SET public_id = FLOOR(10000 + RAND() * 990000) + id');
        $this->addSql('ALTER TABLE game_proposal MODIFY public_id INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PROPOSAL_PUBLIC_ID ON game_proposal (public_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_PROPOSAL_PUBLIC_ID ON game_proposal');
        $this->addSql('ALTER TABLE game_proposal DROP COLUMN public_id');
    }
}
