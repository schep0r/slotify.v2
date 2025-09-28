<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SetupController extends AbstractController
{
    public function __construct(
        private Connection $connection
    ) {
    }

    #[Route('/setup', name: 'app_setup')]
    public function setup(): Response
    {
        try {
            // Check if database is accessible
            $this->connection->executeQuery('SELECT 1');
            
            // Check if tables exist
            $tables = $this->connection->createSchemaManager()->listTableNames();
            $hasUserTable = in_array('user', $tables);
            $hasGameTable = in_array('game', $tables);
            
            return $this->render('setup/index.html.twig', [
                'database_connected' => true,
                'has_user_table' => $hasUserTable,
                'has_game_table' => $hasGameTable,
                'tables' => $tables,
            ]);
        } catch (\Exception $e) {
            return $this->render('setup/index.html.twig', [
                'database_connected' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }
}