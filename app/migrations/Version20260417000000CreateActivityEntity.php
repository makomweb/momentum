<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260417000000CreateActivityEntity extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create activity table for persisting Strava activities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE activity (
            id INT AUTO_INCREMENT NOT NULL,
            strava_activity_id INT NOT NULL,
            strava_user_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            device_name VARCHAR(255) NOT NULL,
            start_date INT NOT NULL,
            timezone VARCHAR(255) NOT NULL,
            elapsed_time INT NOT NULL,
            moving_time INT NOT NULL,
            distance DOUBLE PRECISION NOT NULL,
            max_speed DOUBLE PRECISION NOT NULL,
            average_speed DOUBLE PRECISION NOT NULL,
            type VARCHAR(50) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id),
            UNIQUE KEY unique_strava_activity_id (strava_activity_id, strava_user_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE activity');
    }
}
