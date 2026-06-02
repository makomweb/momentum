<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260421082718 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $table = $schema->getTable('users');

        // Only drop columns if they exist
        if ($table->hasColumn('athlete_name')) {
            $this->addSql('ALTER TABLE users DROP athlete_name');
        }
        if ($table->hasColumn('email')) {
            $this->addSql('ALTER TABLE users DROP email');
        }
        if ($table->hasColumn('created_at')) {
            $this->addSql('ALTER TABLE users DROP created_at');
        }
        if ($table->hasColumn('updated_at')) {
            $this->addSql('ALTER TABLE users DROP updated_at');
        }

        // Add new columns if they don't exist
        if (!$table->hasColumn('name')) {
            $this->addSql('ALTER TABLE users ADD name VARCHAR(255) NOT NULL');
        }
        if (!$table->hasColumn('profile_picture')) {
            $this->addSql('ALTER TABLE users ADD profile_picture VARCHAR(255) NOT NULL');
        }
        if (!$table->hasColumn('profile_picture_medium')) {
            $this->addSql('ALTER TABLE users ADD profile_picture_medium VARCHAR(255) NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD athlete_name VARCHAR(255) DEFAULT NULL, ADD email VARCHAR(255) DEFAULT NULL, ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME DEFAULT NULL, DROP name, DROP profile_picture, DROP profile_picture_medium');
    }
}
