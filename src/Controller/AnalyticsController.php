<?php

namespace App\Controller;

use App\Repository\VisitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AnalyticsController extends AbstractController
{
    #[Route('/analytics', name: 'app_analytics')]
    public function dashboard(VisitRepository $visitRepository): Response
    {
        return $this->render('analytics/dashboard.html.twig', $visitRepository->getStats());
    }
}