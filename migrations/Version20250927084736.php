<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250927084736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game (id SERIAL NOT NULL, name VARCHAR(64) NOT NULL, slug VARCHAR(64) NOT NULL, type VARCHAR(64) NOT NULL, min_bet DOUBLE PRECISION NOT NULL, max_bet DOUBLE PRECISION NOT NULL, step_bet DOUBLE PRECISION NOT NULL, is_active BOOLEAN NOT NULL, rtp DOUBLE PRECISION DEFAULT NULL, reels JSON DEFAULT NULL, paylines JSON DEFAULT NULL, paytable JSON NOT NULL, rows INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE game_session (id SERIAL NOT NULL, player_id INT NOT NULL, game_id INT NOT NULL, session_token VARCHAR(255) NOT NULL, total_spins INT NOT NULL, total_bet DOUBLE PRECISION NOT NULL, total_win DOUBLE PRECISION NOT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, ended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, status VARCHAR(64) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_4586AAFB99E6F5DF ON game_session (player_id)');
        $this->addSql('CREATE INDEX IDX_4586AAFBE48FD905 ON game_session (game_id)');
        $this->addSql('COMMENT ON COLUMN game_session.started_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN game_session.ended_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE transaction (id SERIAL NOT NULL, player_id INT NOT NULL, game_session_id INT DEFAULT NULL, type VARCHAR(64) NOT NULL, amount DOUBLE PRECISION NOT NULL, balance_before DOUBLE PRECISION NOT NULL, balance_after DOUBLE PRECISION NOT NULL, spin_result JSON DEFAULT NULL, reference_id VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, metadata JSON DEFAULT NULL, status VARCHAR(64) NOT NULL, payment_method VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_723705D199E6F5DF ON transaction (player_id)');
        $this->addSql('CREATE INDEX IDX_723705D18FE32B32 ON transaction (game_session_id)');
        $this->addSql('COMMENT ON COLUMN transaction.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, balance DOUBLE PRECISION NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE game_session ADD CONSTRAINT FK_4586AAFB99E6F5DF FOREIGN KEY (player_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game_session ADD CONSTRAINT FK_4586AAFBE48FD905 FOREIGN KEY (game_id) REFERENCES game (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D199E6F5DF FOREIGN KEY (player_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D18FE32B32 FOREIGN KEY (game_session_id) REFERENCES game_session (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SCHEMA slots_audit');
        $this->addSql('CREATE SCHEMA slots_analytics');
        $this->addSql('ALTER TABLE game_session DROP CONSTRAINT FK_4586AAFB99E6F5DF');
        $this->addSql('ALTER TABLE game_session DROP CONSTRAINT FK_4586AAFBE48FD905');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D199E6F5DF');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D18FE32B32');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE game_session');
        $this->addSql('DROP TABLE transaction');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
