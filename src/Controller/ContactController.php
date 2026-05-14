<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

final class ContactController extends AbstractController
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly RateLimiterFactory $contactFormLimiter,
        private readonly string $leadRecipient,
    ) {}

    #[Route('/kontakt', name: 'contact', methods: ['GET', 'POST'])]
    public function contact(Request $request): Response
    {
        $form = $this->createForm(ContactType::class, [
            'product' => $request->query->get('product', ''),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $limiter = $this->contactFormLimiter->create($request->getClientIp() ?? 'anon');
            $limit = $limiter->consume(1);
            if (!$limit->isAccepted()) {
                $this->addFlash('error', 'Příliš mnoho pokusů — zkuste to později.');
                return $this->render('contact/contact.html.twig', [
                    'form' => $form->createView(),
                ], new Response('', Response::HTTP_TOO_MANY_REQUESTS));
            }

            if ($form->isValid()) {
                $data = $form->getData();
                $this->sendLeadEmail($data);
                $this->addFlash('success', 'Děkujeme! Ozveme se vám během 24 hodin.');
                return $this->redirectToRoute('contact');
            }
        }

        return $this->render('contact/contact.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /** @param array<string, mixed> $data */
    private function sendLeadEmail(array $data): void
    {
        try {
            $message = (new Email())
                ->from('noreply@new-factory.cz')
                ->to($this->leadRecipient)
                ->replyTo($data['email'])
                ->subject(sprintf('Poptávka %s — %s', $data['product'] ?: 'obecná', $data['name']))
                ->text($this->buildBody($data));
            $this->mailer->send($message);
        } catch (\Throwable $e) {
            // Don't 500 on mail failure — user already sees success flash.
            // Log + let Sentry capture; operator triages later from the dashboard.
            $this->logger->error('Failed to send lead email', [
                'err' => $e->getMessage(),
                'recipient' => $this->leadRecipient,
            ]);
        }
    }

    /** @param array<string, mixed> $data */
    private function buildBody(array $data): string
    {
        return sprintf(
            "Nová poptávka z https://new-factory.cz/kontakt\n\n" .
            "Jméno: %s\nE-mail: %s\nTelefon: %s\nZájem: %s\n\nZpráva:\n%s\n",
            $data['name'],
            $data['email'],
            $data['phone'] ?: '(neuvedeno)',
            $data['product'] ?: '(neuvedeno)',
            $data['message'],
        );
    }
}
