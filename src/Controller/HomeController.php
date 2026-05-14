<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\BenefitRepository;
use App\Service\HomepageRepository;
use App\Service\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    public function __construct(
        private readonly HomepageRepository $homepageRepo,
        private readonly ProductRepository $productRepo,
        private readonly BenefitRepository $benefitRepo,
    ) {}

    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'homepage' => $this->homepageRepo->get(),
            'products' => $this->productRepo->all(),
            'benefits' => $this->benefitRepo->all(),
        ]);
    }
}
