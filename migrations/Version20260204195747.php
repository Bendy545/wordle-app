<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204195747 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game_state (id INT AUTO_INCREMENT NOT NULL, current_slot INT NOT NULL, slot_date DATE NOT NULL, updated_at DATETIME NOT NULL, current_word_id INT NOT NULL, INDEX IDX_91A0AB74495AC608 (current_word_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE game_state ADD CONSTRAINT FK_91A0AB74495AC608 FOREIGN KEY (current_word_id) REFERENCES word (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_state DROP FOREIGN KEY FK_91A0AB74495AC608');
        $this->addSql('DROP TABLE game_state');
    }
}
