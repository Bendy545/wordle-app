<?php

namespace App\Controller;

use App\Repository\GameStateRepository;
use App\Repository\WordRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

class GameApiController extends AbstractController
{
    private const MAX_ATTEMPTS = 6;

    #[Route('/api/guess', name: 'api_guess', methods: ['POST'])]
    public function guess(Request $request, RequestStack $requestStack, GameStateRepository $gameStateRepository, WordRepository $wordRepository): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $guess = strtoupper(trim($data['guess'] ?? ''));

        if (strlen($guess) !== 5 || !ctype_alpha($guess)) {
            return $this->json(['error' => 'Invalid guess'], 400);
        }

        if (!$wordRepository->wordExists($guess)) {
            return $this->json(['error' => 'Not in word list'], 400);
        }

        $state = $gameStateRepository->getSingleton();
        if (!$state || !$state->getCurrentWord()) {
            return $this->json(['error' => 'No active game'], 500);
        }

        $slotId = $state->getSlotDate()->format('Y-m-d') . '-' . $state->getCurrentSlot();

        $session = $requestStack->getSession();
        $sessionKey = 'wordle_' . $slotId;
        $attempts = $session->get($sessionKey, 0);

        if ($attempts >= self::MAX_ATTEMPTS) {
            return $this->json(['error' => 'No attempts remaining'], 400);
        }

        $answer = strtoupper($state->getCurrentWord()->getName());
        $result = $this->evaluate($guess, $answer);
        $won = $guess === $answer;

        $attempts++;
        $session->set($sessionKey, $attempts);

        $gameOver = $won || $attempts >= self::MAX_ATTEMPTS;

        $response = [
            'result' => $result,
            'won' => $won,
            'slotId' => $slotId
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
                $result[$i] = ['letter' => $guessLetters[$i],'status' => 'correct',];
            } else {
                $result[$i] = ['letter' => $guessLetters[$i],'status' => 'absent',];
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