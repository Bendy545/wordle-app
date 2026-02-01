<?php

namespace App\Controller;

use App\Repository\WordRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(WordRepository $wordRepository): Response
    {
        $word = $wordRepository->getRandomWord();

        return $this->render('home/index.html.twig', [
            'message' => 'Welcome to ITordle! ',
            'randomWord' => $word?->getName(),
            'hasWords' => $word !== null,
        ]);
    }
}
