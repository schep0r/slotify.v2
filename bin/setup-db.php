#!/usr/bin/env php
<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(dirname(__DIR__).'/.env');

$databaseUrl = $_ENV['DATABASE_URL'];
$dbPath = dirname(__DIR__).'/var/data_dev.db';

// Create var directory if it doesn't exist
if (!is_dir(dirname($dbPath))) {
    mkdir(dirname($dbPath), 0755, true);
}

// Create SQLite database
$pdo = new PDO($databaseUrl);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

-- Insert sample game
INSERT OR IGNORE INTO game (id, name, slug, type, min_bet, max_bet, step_bet, is_active, rtp, rows, paytable) 
VALUES (1, "Classic Slots", "classic-slots", "slot", 0.10, 100.00, 0.10, 1, 96.5, 3, "[]");
';

$pdo->exec($sql);

echo "Database setup completed successfully!\n";
echo "You can now register users and start playing.\n";