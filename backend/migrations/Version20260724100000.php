<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260724100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add composite index on (latitude, longitude) for user and game_proposal to speed up radius search';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IDX_USER_LAT_LNG ON `user` (latitude, longitude)');
        $this->addSql('CREATE INDEX IDX_GAME_PROPOSAL_LAT_LNG ON game_proposal (latitude, longitude)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_USER_LAT_LNG ON `user`');
        $this->addSql('DROP INDEX IDX_GAME_PROPOSAL_LAT_LNG ON game_proposal');
    }
}
