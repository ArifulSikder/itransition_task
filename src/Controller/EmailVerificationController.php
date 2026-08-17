<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EmailVerificationController extends AbstractController
{
    #[Route('/verify/email/{token}', name: 'app_verify_email')]
    public function verify(
        string $token,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $userRepository->findOneBy(['verificationToken' => $token]);

        if (!$user instanceof User) {
            $this->addFlash('danger', 'This confirmation link is invalid or has already been used.');

            return $this->redirectToRoute('app_login');
        }

        $wasBlocked = $user->isBlocked();
        $user->activateFromEmail();
        $entityManager->flush();

        if ($wasBlocked) {
            $this->addFlash('warning', 'Your e-mail was confirmed, but the account stays blocked.');
        } else {
            $this->addFlash('success', 'Your e-mail has been confirmed. The account is now active.');
        }

        return $this->redirectToRoute($this->getUser() ? 'app_users' : 'app_login');
    }
}
