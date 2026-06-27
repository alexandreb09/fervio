<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260627160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace uuid with publicId (random int) on user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD public_id INT DEFAULT NULL');

        // Assign unique random IDs to existing users
        $this->addSql('SET @counter = 0');
        $this->addSql('
            UPDATE `user`
            SET public_id = FLOOR(10000 + RAND() * 990000)
            ORDER BY id
        ');
        // Fix any collisions by incrementing
        $this->addSql('
            UPDATE `user` u1
            JOIN (
                SELECT id, ROW_NUMBER() OVER (ORDER BY id) AS rn
                FROM `user`
            ) u2 ON u1.id = u2.id
            SET u1.public_id = u1.public_id + u2.rn
            WHERE EXISTS (
                SELECT 1 FROM (SELECT public_id FROM `user`) tmp
                GROUP BY public_id HAVING COUNT(*) > 1
            )
        ');

        $this->addSql('ALTER TABLE `user` MODIFY public_id INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649_PUBLIC_ID ON `user` (public_id)');
        $this->addSql('ALTER TABLE `user` DROP COLUMN uuid');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8D93D649_PUBLIC_ID ON `user`');
        $this->addSql('ALTER TABLE `user` DROP COLUMN public_id');
        $this->addSql('ALTER TABLE `user` ADD uuid VARCHAR(36) NOT NULL DEFAULT \'\'');
    }
}
