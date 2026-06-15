<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

/**
 * /kontakt lead form. Same approach as dosmart_web/mikamiho_web: the page is a
 * plain HTML form that POSTs via fetch() to a JSON endpoint, with an inline
 * success/error message (no full-page reload). Sends via dstest11/mail-http-sdk
 * → mail-http-api (prod); dev uses null:// transport.
 */
final class ContactController extends AbstractController
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly RateLimiterFactory $contactFormLimiter,
        private readonly string $leadRecipient,
    ) {
    }

    #[Route('/kontakt', name: 'contact', methods: ['GET'])]
    public function page(Request $request): Response
    {
        return $this->render('contact/contact.html.twig', [
            'product' => (string) $request->query->get('product', ''),
        ]);
    }

    #[Route('/kontakt-odeslat', name: 'contact_submit', methods: ['POST'])]
    public function submit(Request $request): JsonResponse
    {
        // Honeypot: real users never fill `website`; bots fill everything.
        if (trim((string) $request->request->get('website', '')) !== '') {
            return new JsonResponse(['ok' => true, 'message' => 'Děkujeme!']);
        }

        $limiter = $this->contactFormLimiter->create($request->getClientIp() ?? 'anon');
        if (!$limiter->consume(1)->isAccepted()) {
            return new JsonResponse([
                'ok' => false,
                'error' => 'rate_limited',
                'message' => 'Příliš mnoho pokusů — zkuste to prosím za chvíli.',
            ], 429);
        }

        $name    = trim((string) $request->request->get('name', ''));
        $email   = trim((string) $request->request->get('email', ''));
        $phone   = trim((string) $request->request->get('phone', ''));
        $product = trim((string) $request->request->get('product', ''));
        $message = trim((string) $request->request->get('message', ''));

        $errors = [];
        if (mb_strlen($name) < 2) {
            $errors['name'] = 'Vyplňte prosím jméno.';
        }
        if ($email === '' || false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Zadejte prosím platný e-mail.';
        }
        if (mb_strlen($name) > 200 || mb_strlen($email) > 200 || mb_strlen($phone) > 64
            || mb_strlen($product) > 120 || mb_strlen($message) > 5000) {
            $errors['form'] = 'Některé pole je příliš dlouhé.';
        }
        if ($errors !== []) {
            return new JsonResponse(['ok' => false, 'errors' => $errors], 422);
        }

        try {
            $mail = (new Email())
                ->from('noreply@new-factory.cz')
                ->to($this->leadRecipient)
                ->replyTo($email)
                ->subject(sprintf('Poptávka %s — %s', '' !== $product ? $product : 'obecná', $name))
                ->text(sprintf(
                    "Nová poptávka z https://new-factory.cz/kontakt\n\n"
                    . "Jméno: %s\nE-mail: %s\nTelefon: %s\nZájem: %s\n\nZpráva:\n%s\n",
                    $name,
                    $email,
                    '' !== $phone ? $phone : '(neuvedeno)',
                    '' !== $product ? $product : '(neuvedeno)',
                    '' !== $message ? $message : '(bez zprávy)'
                ));
            $this->mailer->send($mail);
        } catch (\Throwable $e) {
            $this->logger->error('Contact form: mail send failed', [
                'err' => $e->getMessage(),
                'recipient' => $this->leadRecipient,
            ]);

            return new JsonResponse([
                'ok' => false,
                'error' => 'send_failed',
                'message' => 'Odeslání se nezdařilo. Napište nám prosím přímo na mining@new-factory.cz.',
            ], 502);
        }

        return new JsonResponse([
            'ok' => true,
            'message' => 'Děkujeme! Ozveme se vám do 24 hodin.',
        ]);
    }
}
