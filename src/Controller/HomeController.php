<?php

namespace App\Controller;

use App\Repository\GameStateRepository;
use App\Service\WordRotationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(GameStateRepository $gameStateRepository, WordRotationService $rotationService): Response
    {
        $state = $gameStateRepository->getSingleton();
        $word = $state?->getCurrentWord();

        $slotId = null;
        if ($state) {
            $slotId = $state->getSlotDate()->format('Y-m-d') . '-' . $state->getCurrentSlot();
        }

        $nextRotation = $rotationService->getNextRotationTime();

        return $this->render('home/index.html.twig', [
            'hasWords' => $word !== null,
            'slotId' => $slotId,
            'nextRotationUtc' => $nextRotation->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z')
        ]);
    }
}
