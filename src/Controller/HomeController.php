<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ProductCatalog;
use App\Service\StrapiContentClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    public function __construct(
        private readonly StrapiContentClient $strapi,
        private readonly ProductCatalog $catalog,
    ) {}

    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(): Response
    {
        $homepage = $this->strapi->singleType('homepage', ['hero']);
        return $this->render('home/index.html.twig', [
            'homepage' => $homepage['data'] ?? [],
            'products' => $this->catalog->all(),
        ]);
    }
}
