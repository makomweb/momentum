<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260420000000CreateActivitySyncState extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create activity_sync_state table for tracking sync progress';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE activity_sync_state (
            id INT AUTO_INCREMENT NOT NULL,
            strava_user_id INT NOT NULL,
            last_sync_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            last_activity_timestamp INT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id),
            UNIQUE KEY unique_strava_user_id (strava_user_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE activity_sync_state');
    }
}
