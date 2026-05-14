<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\FaqRepository;
use App\Service\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly FaqRepository $faqs,
    ) {}

    #[Route('/produkty', name: 'product_list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('product/list.html.twig', [
            'products' => $this->products->all(),
            'faqs' => $this->faqs->forLocation('list'),
        ]);
    }

    #[Route('/produkty/{slug}', name: 'product_detail', methods: ['GET'])]
    public function detail(string $slug): Response
    {
        $product = $this->products->bySlug($slug);
        if ($product === null) {
            throw $this->createNotFoundException(sprintf('Produkt "%s" neexistuje.', $slug));
        }
        return $this->render('product/detail.html.twig', [
            'product' => $product,
            'faqs' => $this->faqs->forLocation('detail'),
        ]);
    }
}
