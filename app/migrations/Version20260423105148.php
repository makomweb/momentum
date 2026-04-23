<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260423105148 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE rememberme_token (series VARCHAR(88) NOT NULL, value VARCHAR(88) NOT NULL, lastUsed DATETIME NOT NULL, class VARCHAR(100) DEFAULT \'\' NOT NULL, username VARCHAR(200) NOT NULL, PRIMARY KEY (series)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE remember_me_tokens DROP FOREIGN KEY `FK_9D54E365A76ED395`');
        $this->addSql('DROP TABLE remember_me_tokens');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE remember_me_tokens (id INT AUTO_INCREMENT NOT NULL, selector VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, hashed_validator VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_9D54E365A76ED395 (user_id), UNIQUE INDEX idx_selector (selector), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE remember_me_tokens ADD CONSTRAINT `FK_9D54E365A76ED395` FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('DROP TABLE rememberme_token');
    }
}
