<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260423100726 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE remember_me_tokens (id INT AUTO_INCREMENT NOT NULL, selector VARCHAR(255) NOT NULL, hashed_validator VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_9D54E365A76ED395 (user_id), UNIQUE INDEX idx_selector (selector), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE remember_me_tokens ADD CONSTRAINT FK_9D54E365A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE remember_me_tokens DROP FOREIGN KEY FK_9D54E365A76ED395');
        $this->addSql('DROP TABLE remember_me_tokens');
    }
}
