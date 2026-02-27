<?php

namespace App\Controller;

use App\Entity\Visit;
use App\Repository\GameStateRepository;
use App\Service\WordRotationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, GameStateRepository $gameStateRepository, WordRotationService $rotationService, EntityManagerInterface $em): Response
    {

        if ($request->cookies->get('analytics_consent') === 'accepted') {
            $session = $request->getSession();
            $hasUtm = $request->query->has('utm_source');

            if ($hasUtm || !$session->get('utm_tracked')) {
                $visit = new Visit();
                $visit->setUtmSource($request->query->get('utm_source'));
                $visit->setUtmMedium($request->query->get('utm_medium'));
                $visit->setUtmCampaign($request->query->get('utm_campaign'));
                $visit->setIpHash(hash('sha256', ($request->getClientIp() ?? '0') . date('Y-m-d')));

                $em->persist($visit);
                $em->flush();

                $session->set('utm_tracked', true);
            }
        }


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
