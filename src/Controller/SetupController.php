<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\DBAL\Connection;

class SetupController extends AbstractController
{
    #[Route('/setup', name: 'app_setup')]
    public function setup(Connection $connection): Response
    {
        try {
            // Create tables
            $sql = '
            -- User table
            CREATE TABLE IF NOT EXISTS user (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email VARCHAR(180) NOT NULL UNIQUE,
                roles TEXT NOT NULL DEFAULT "[]",
                password VARCHAR(255) NOT NULL,
                balance REAL NOT NULL DEFAULT 0.0
            );

            -- Game table
            CREATE TABLE IF NOT EXISTS game (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(64) NOT NULL,
                slug VARCHAR(64) NOT NULL,
                type VARCHAR(64) NOT NULL,
                min_bet REAL NOT NULL,
                max_bet REAL NOT NULL,
                step_bet REAL NOT NULL,
                is_active BOOLEAN NOT NULL,
                rtp REAL,
                reels TEXT,
                paylines TEXT,
                paytable TEXT NOT NULL DEFAULT "[]",
                rows INTEGER NOT NULL
            );

            -- Game session table
            CREATE TABLE IF NOT EXISTS game_session (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                player_id INTEGER NOT NULL,
                game_id INTEGER NOT NULL,
                session_token VARCHAR(255) NOT NULL,
                total_spins INTEGER NOT NULL DEFAULT 0,
                total_bet REAL NOT NULL DEFAULT 0.0,
                total_win REAL NOT NULL DEFAULT 0.0,
                started_at DATETIME NOT NULL,
                ended_at DATETIME,
                status VARCHAR(64) NOT NULL,
                FOREIGN KEY (player_id) REFERENCES user(id),
                FOREIGN KEY (game_id) REFERENCES game(id)
            );

            -- Transaction table
            CREATE TABLE IF NOT EXISTS transaction (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                player_id INTEGER NOT NULL,
                game_session_id INTEGER NOT NULL,
                type VARCHAR(64) NOT NULL,
                amount REAL NOT NULL,
                balance_before REAL NOT NULL,
                balance_after REAL NOT NULL,
                spin_result TEXT,
                reference_id VARCHAR(255) NOT NULL,
                description VARCHAR(255),
                metadata TEXT,
                status VARCHAR(64) NOT NULL,
                FOREIGN KEY (player_id) REFERENCES user(id),
                FOREIGN KEY (game_session_id) REFERENCES game_session(id)
            );
            ';

            $connection->executeStatement($sql);

            // Insert sample game
            $connection->executeStatement('
                INSERT OR IGNORE INTO game (id, name, slug, type, min_bet, max_bet, step_bet, is_active, rtp, rows, paytable) 
                VALUES (1, "Classic Slots", "classic-slots", "slot", 0.10, 100.00, 0.10, 1, 96.5, 3, "[]")
            ');

            return new Response('
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #f0f0f0; border-radius: 10px;">
                    <h2 style="color: #28a745;">✅ Database Setup Complete!</h2>
                    <p>The database has been successfully initialized with all required tables.</p>
                    <p><strong>Sample game added:</strong> Classic Slots</p>
                    <p><a href="/" style="color: #007bff; text-decoration: none;">← Go to Home Page</a></p>
                </div>
            ');

        } catch (\Exception $e) {
            return new Response('
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #f8d7da; border-radius: 10px; color: #721c24;">
                    <h2>❌ Setup Failed</h2>
                    <p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>
                    <p><a href="/" style="color: #007bff; text-decoration: none;">← Go to Home Page</a></p>
                </div>
            ');
        }
    }
}