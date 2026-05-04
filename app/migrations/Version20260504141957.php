<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260504141957 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add timezone column to activities and store UTC timestamps';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE activities ADD timezone VARCHAR(100) NOT NULL DEFAULT \'UTC\' AFTER start_date');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE activities DROP COLUMN timezone');
    }
}
