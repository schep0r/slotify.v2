<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250927090205 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add sample slot games';
    }

    public function up(Schema $schema): void
    {
        // Insert sample slot games
        $this->addSql(
            "INSERT INTO game (name, slug, type, min_bet, max_bet, step_bet, is_active, rtp, reels, paylines, paytable, rows) VALUES 
            ('Lucky Sevens', 'lucky-sevens', 'slot', 0.10, 100.00, 0.10, true, 0.96, '[[\"7\",\"BAR\",\"CHERRY\",\"LEMON\",\"ORANGE\"],[\"7\",\"BAR\",\"CHERRY\",\"LEMON\",\"ORANGE\"],[\"7\",\"BAR\",\"CHERRY\",\"LEMON\",\"ORANGE\"]]', '[[0,1,2]]', '{\"7\":[100,50,10],\"BAR\":[50,25,5],\"CHERRY\":[25,10,2],\"LEMON\":[10,5,1],\"ORANGE\":[10,5,1]}', 3),
            ('Diamond Rush', 'diamond-rush', 'slot', 0.25, 250.00, 0.25, true, 0.95, '[[\"DIAMOND\",\"GOLD\",\"SILVER\",\"RUBY\",\"EMERALD\"],[\"DIAMOND\",\"GOLD\",\"SILVER\",\"RUBY\",\"EMERALD\"],[\"DIAMOND\",\"GOLD\",\"SILVER\",\"RUBY\",\"EMERALD\"],[\"DIAMOND\",\"GOLD\",\"SILVER\",\"RUBY\",\"EMERALD\"],[\"DIAMOND\",\"GOLD\",\"SILVER\",\"RUBY\",\"EMERALD\"]]', '[[0,1,2,3,4],[1,2,3,4,0],[2,3,4,0,1]]', '{\"DIAMOND\":[1000,500,100],\"GOLD\":[500,250,50],\"SILVER\":[250,100,25],\"RUBY\":[100,50,10],\"EMERALD\":[100,50,10]}', 5),
            ('Fruit Bonanza', 'fruit-bonanza', 'slot', 0.05, 50.00, 0.05, true, 0.94, '[[\"WATERMELON\",\"GRAPE\",\"APPLE\",\"BANANA\",\"CHERRY\"],[\"WATERMELON\",\"GRAPE\",\"APPLE\",\"BANANA\",\"CHERRY\"],[\"WATERMELON\",\"GRAPE\",\"APPLE\",\"BANANA\",\"CHERRY\"]]', '[[0,1,2],[1,0,2],[2,1,0]]', '{\"WATERMELON\":[200,100,20],\"GRAPE\":[150,75,15],\"APPLE\":[100,50,10],\"BANANA\":[75,25,5],\"CHERRY\":[50,20,3]}', 3),
            ('Egyptian Gold', 'egyptian-gold', 'slot', 0.20, 200.00, 0.20, true, 0.97, '[[\"PHARAOH\",\"PYRAMID\",\"SCARAB\",\"ANKH\",\"EYE\"],[\"PHARAOH\",\"PYRAMID\",\"SCARAB\",\"ANKH\",\"EYE\"],[\"PHARAOH\",\"PYRAMID\",\"SCARAB\",\"ANKH\",\"EYE\"],[\"PHARAOH\",\"PYRAMID\",\"SCARAB\",\"ANKH\",\"EYE\"],[\"PHARAOH\",\"PYRAMID\",\"SCARAB\",\"ANKH\",\"EYE\"]]', '[[0,1,2,3,4],[1,2,3,4,0],[2,3,4,0,1],[0,2,4],[1,3,0]]', '{\"PHARAOH\":[2000,1000,200],\"PYRAMID\":[1000,500,100],\"SCARAB\":[500,250,50],\"ANKH\":[250,100,25],\"EYE\":[200,75,20]}', 5),
            ('Wild West', 'wild-west', 'slot', 0.15, 150.00, 0.15, true, 0.93, '[[\"SHERIFF\",\"BANDIT\",\"HORSE\",\"CACTUS\",\"BOOT\"],[\"SHERIFF\",\"BANDIT\",\"HORSE\",\"CACTUS\",\"BOOT\"],[\"SHERIFF\",\"BANDIT\",\"HORSE\",\"CACTUS\",\"BOOT\"]]', '[[0,1,2],[1,0,2],[2,1,0],[0,2,1],[1,2,0]]', '{\"SHERIFF\":[300,150,30],\"BANDIT\":[250,125,25],\"HORSE\":[200,100,20],\"CACTUS\":[150,75,15],\"BOOT\":[100,50,10]}', 3),
            ('Ocean Treasures', 'ocean-treasures', 'slot', 0.30, 300.00, 0.30, true, 0.96, '[[\"TREASURE\",\"PEARL\",\"SEAHORSE\",\"STARFISH\",\"SHELL\"],[\"TREASURE\",\"PEARL\",\"SEAHORSE\",\"STARFISH\",\"SHELL\"],[\"TREASURE\",\"PEARL\",\"SEAHORSE\",\"STARFISH\",\"SHELL\"],[\"TREASURE\",\"PEARL\",\"SEAHORSE\",\"STARFISH\",\"SHELL\"],[\"TREASURE\",\"PEARL\",\"SEAHORSE\",\"STARFISH\",\"SHELL\"]]', '[[0,1,2,3,4],[1,2,3,4,0],[2,3,4,0,1],[0,2,4],[1,3,0],[2,0,4],[3,1,0],[4,2,1]]', '{\"TREASURE\":[5000,2500,500],\"PEARL\":[2500,1250,250],\"SEAHORSE\":[1250,625,125],\"STARFISH\":[625,300,60],\"SHELL\":[500,250,50]}', 5)"
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SCHEMA slots_audit');
        $this->addSql('CREATE SCHEMA slots_analytics');
    }
}
