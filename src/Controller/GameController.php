<?php

namespace App\Controller;

use App\DTOs\GameResultDto;
use App\Entity\Game;
use App\Engines\SlotGameEngine;
use App\Form\SpinType;
use App\Repository\GameRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/games')]
class GameController extends AbstractController
{
    public function __construct(
        private GameRepository $gameRepository,
        private SlotGameEngine $slotGameEngine
    ) {
    }

    #[Route('/', name: 'app_games')]
    public function index(): Response
    {
        $games = $this->gameRepository->findBy(['isActive' => true], ['name' => 'ASC']);

        return $this->render('games/index.html.twig', [
            'games' => $games,
        ]);
    }

    #[Route('/{slug}', name: 'app_game_details')]
    public function details(#[MapEntity(mapping: ['slug' => 'slug'])] Game $game): Response
    {
        $this->validateGameAccess($game);

        return $this->render('games/details.html.twig', [
            'game' => $game,
        ]);
    }

    #[Route('/{slug}/play', name: 'app_game_play')]
    #[IsGranted('ROLE_USER')]
    public function play(#[MapEntity(mapping: ['slug' => 'slug'])] Game $game, Request $request): Response
    {
        $this->validateGameAccess($game);

        $user = $this->getUser();
        $spinForm = $this->createSpinForm($game);

        $reelsScreen = [];
        foreach ($game->getReels() as $reel) {
            $reelsScreen[] = array_slice($reel, rand(0, count($reel) - $game->getRows()), $game->getRows());
        }

        return $this->render('games/play.html.twig', [
            'game' => $game,
            'reelsScreen' => $reelsScreen,
            'user' => $user,
            'spinForm' => $spinForm->createView(),
        ]);
    }

    #[Route('/{slug}/spin', name: 'app_game_spin')]
    #[IsGranted('ROLE_USER')]
    public function spin(#[MapEntity(mapping: ['slug' => 'slug'])] Game $game, Request $request): JsonResponse
    {
        try {
            $this->validateGameAccess($game);
            $result = $this->processSpinRequest($game, $request);

            return new JsonResponse($result->toArray(), Response::HTTP_OK);
        } catch (\Exception $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    private function createSpinForm(Game $game): FormInterface
    {
        return $this->createForm(
            SpinType::class,
            null,
            [
                'game_object' => $game,
                'active_free_spins' => false,
            ]
        );
    }

    private function validateGameAccess(Game $game): void
    {
        if (!$game->isActive()) {
            throw $this->createNotFoundException('Game not found or not available');
        }
    }

    private function processSpinRequest(Game $game, Request $request): GameResultDto
    {
        $user = $this->getUser();
        $spinForm = $this->createSpinForm($game);

        $spinForm->handleRequest($request);

        if (!$spinForm->isSubmitted() || !$spinForm->isValid()) {
            throw new \InvalidArgumentException('Invalid form submission');
        }

        return $this->slotGameEngine->play($user, $game, $spinForm->getData());
    }
}
