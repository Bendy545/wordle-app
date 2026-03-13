<?php

namespace App\Controller;

use App\Repository\GameStateRepository;
use App\Repository\PlayerGameRepository;
use App\Repository\WordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

class GameApiController extends AbstractController
{
    private const MAX_ATTEMPTS = 6;

    #[Route('/api/game-state', name: 'api_game_state', methods: ['GET'])]
    public function gameState(
        RequestStack $requestStack,
        GameStateRepository $gameStateRepository,
        PlayerGameRepository $playerGameRepository,
    ): JsonResponse {
        $state = $gameStateRepository->getSingleton();
        if (!$state || !$state->getCurrentWord()) {
            return $this->json(['error' => 'No active game'], 500);
        }

        $slotId = $state->getSlotDate()->format('Y-m-d') . '-' . $state->getCurrentSlot();
        $sessionId = $requestStack->getSession()->getId();

        $playerGame = $playerGameRepository->findBySessionAndSlot($sessionId, $slotId);

        if (!$playerGame) {
            return $this->json([
                'slotId' => $slotId,
                'guesses' => [],
                'gameOver' => false,
                'won' => false,
                'answer' => null,
            ]);
        }

        return $this->json([
            'slotId' => $slotId,
            'guesses' => $playerGame->getGuesses(),
            'gameOver' => $playerGame->isGameOver(),
            'won' => $playerGame->hasWon(),
            'answer' => $playerGame->isGameOver() ? $playerGame->getAnswer() : null,
        ]);
    }

    #[Route('/api/guess', name: 'api_guess', methods: ['POST'])]
    public function guess(
        Request $request,
        RequestStack $requestStack,
        GameStateRepository $gameStateRepository,
        WordRepository $wordRepository,
        PlayerGameRepository $playerGameRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $guess = strtoupper(trim($data['guess'] ?? ''));

        if (strlen($guess) !== 5 || !ctype_alpha($guess)) {
            return $this->json(['error' => 'Invalid guess']);
        }

        if (!$wordRepository->wordExists($guess)) {
            return $this->json(['error' => 'Not in word list']);
        }

        $state = $gameStateRepository->getSingleton();
        if (!$state || !$state->getCurrentWord()) {
            return $this->json(['error' => 'No active game'], 500);
        }

        $slotId = $state->getSlotDate()->format('Y-m-d') . '-' . $state->getCurrentSlot();

        $clientSlotId = $data['slotId'] ?? null;
        if ($clientSlotId && $clientSlotId !== $slotId) {
            return $this->json([
                'error' => 'expired',
                'message' => 'The word has changed! Reloading…'
            ], 409);
        }

        $sessionId = $requestStack->getSession()->getId();
        $playerGame = $playerGameRepository->getOrCreate($sessionId, $slotId);

        if ($playerGame->isGameOver()) {
            return $this->json(['error' => 'Game already finished']);
        }

        if ($playerGame->getAttemptCount() >= self::MAX_ATTEMPTS) {
            return $this->json(['error' => 'No attempts remaining']);
        }

        $answer = strtoupper($state->getCurrentWord()->getName());
        $result = $this->evaluate($guess, $answer);
        $won = $guess === $answer;

        $playerGame->addGuess([
            'word' => $guess,
            'result' => $result,
            'won' => $won,
        ]);

        $gameOver = $won || $playerGame->getAttemptCount() >= self::MAX_ATTEMPTS;

        if ($gameOver) {
            $playerGame->setGameOver(true);
            $playerGame->setWon($won);
            $playerGame->setAnswer($answer);
        }

        $em->flush();

        $response = [
            'result' => $result,
            'won' => $won,
            'slotId' => $slotId,
        ];

        if ($gameOver) {
            $response['answer'] = $answer;
        }

        return $this->json($response);
    }

    private function evaluate(string $guess, string $answer): array
    {
        $result = [];
        $answerLetters = str_split($answer);
        $guessLetters = str_split($guess);
        $remaining = [];

        for ($i = 0; $i < 5; $i++) {
            if ($guessLetters[$i] === $answerLetters[$i]) {
                $result[$i] = ['letter' => $guessLetters[$i], 'status' => 'correct'];
            } else {
                $result[$i] = ['letter' => $guessLetters[$i], 'status' => 'absent'];
                $remaining[] = $answerLetters[$i];
            }
        }

        for ($i = 0; $i < 5; $i++) {
            if ($result[$i]['status'] === 'correct') {
                continue;
            }

            $pos = array_search($guessLetters[$i], $remaining);
            if ($pos !== false) {
                $result[$i]['status'] = 'present';
                unset($remaining[$pos]);
            }
        }

        return array_values($result);
    }
}