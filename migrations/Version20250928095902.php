<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250928095902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game_round (id SERIAL NOT NULL, game_session_id INT NOT NULL, player_id INT NOT NULL, game_id INT NOT NULL, bet_amount DOUBLE PRECISION NOT NULL, win_amount DOUBLE PRECISION NOT NULL, net_result DOUBLE PRECISION NOT NULL, balance_before DOUBLE PRECISION NOT NULL, balance_after DOUBLE PRECISION NOT NULL, reels_result JSON NOT NULL, paylines_won JSON NOT NULL, multipliers JSON NOT NULL, bonus_features JSON NOT NULL, lines_played INT NOT NULL, bet_per_line DOUBLE PRECISION NOT NULL, rtp_contribution DOUBLE PRECISION NOT NULL, is_bonus_round BOOLEAN NOT NULL, bonus_type VARCHAR(64) DEFAULT NULL, free_spins_remaining INT DEFAULT NULL, transection_ref VARCHAR(255) NOT NULL, ip_address VARCHAR(64) NOT NULL, user_agent VARCHAR(255) NOT NULL, status VARCHAR(64) NOT NULL, complited_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completion_hash VARCHAR(255) NOT NULL, extra_data JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F7DD93BB8FE32B32 ON game_round (game_session_id)');
        $this->addSql('CREATE INDEX IDX_F7DD93BB99E6F5DF ON game_round (player_id)');
        $this->addSql('CREATE INDEX IDX_F7DD93BBE48FD905 ON game_round (game_id)');
        $this->addSql('COMMENT ON COLUMN game_round.complited_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE game_round ADD CONSTRAINT FK_F7DD93BB8FE32B32 FOREIGN KEY (game_session_id) REFERENCES game_session (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game_round ADD CONSTRAINT FK_F7DD93BB99E6F5DF FOREIGN KEY (player_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game_round ADD CONSTRAINT FK_F7DD93BBE48FD905 FOREIGN KEY (game_id) REFERENCES game (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SCHEMA slots_audit');
        $this->addSql('CREATE SCHEMA slots_analytics');
        $this->addSql('ALTER TABLE game_round DROP CONSTRAINT FK_F7DD93BB8FE32B32');
        $this->addSql('ALTER TABLE game_round DROP CONSTRAINT FK_F7DD93BB99E6F5DF');
        $this->addSql('ALTER TABLE game_round DROP CONSTRAINT FK_F7DD93BBE48FD905');
        $this->addSql('DROP TABLE game_round');
    }
}
