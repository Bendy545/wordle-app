<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260305120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create player_game table for server-side game state';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE player_game (
            id INT AUTO_INCREMENT NOT NULL,
            session_id VARCHAR(128) NOT NULL,
            slot_id VARCHAR(20) NOT NULL,
            guesses JSON NOT NULL,
            game_over TINYINT(1) NOT NULL DEFAULT 0,
            won TINYINT(1) NOT NULL DEFAULT 0,
            answer VARCHAR(5) DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX uniq_session_slot (session_id, slot_id),
            INDEX idx_session (session_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE player_game');
    }
}