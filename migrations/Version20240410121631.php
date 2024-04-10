<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240410121631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE cards_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE game_settings_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE cards (id INT NOT NULL, theme_id_id INT DEFAULT NULL, image_url VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_4C258FD276615B2 ON cards (theme_id_id)');
        $this->addSql('CREATE TABLE game_settings (id INT NOT NULL, difficulty VARCHAR(20) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE cards ADD CONSTRAINT FK_4C258FD276615B2 FOREIGN KEY (theme_id_id) REFERENCES themes (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE cards_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE game_settings_id_seq CASCADE');
        $this->addSql('ALTER TABLE cards DROP CONSTRAINT FK_4C258FD276615B2');
        $this->addSql('DROP TABLE cards');
        $this->addSql('DROP TABLE game_settings');
    }
}
