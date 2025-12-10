<?php

// src/Controller/HomeController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {
        // Affiche simplement la page d'accueil SANS traitement de recherche
        return $this->render('home/index.html.twig');
    }

    #[Route('/search', name: 'app_home_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        // Récupère TOUS les paramètres de recherche
        $parameters = $request->query->all();

        // Redirige vers la page des covoiturages avec les paramètres
        return $this->redirectToRoute('app_ride_index', $parameters);
    }
}

