<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class VerificationMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Important: the confirmation e-mail is dispatched asynchronously via Messenger.
     * Note: registration itself does not wait for SMTP.
     * Nota bene: configure MAILER_DSN to actually deliver the message.
     */
    public function sendConfirmation(User $user): void
    {
        $verifyUrl = $this->urlGenerator->generate(
            'app_verify_email',
            ['token' => (string) $user->getVerificationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@user.com', 'User Management'))
            ->to($user->getEmail())
            ->subject('Confirm your e-mail address')
            ->htmlTemplate('emails/confirm.html.twig')
            ->context([
                'name' => $user->getName(),
                'verifyUrl' => $verifyUrl,
            ]);

        $this->mailer->send($email);

        $this->logger->info('Queued confirmation e-mail for {email} with link {url}', [
            'email' => $user->getEmail(),
            'url' => $verifyUrl,
        ]);
    }
}
