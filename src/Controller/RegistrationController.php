<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\VerificationMailer;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        VerificationMailer $verificationMailer,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_users');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $user->setVerificationToken($user->getUniqIdValue());

            $entityManager->persist($user);

            try {
                $entityManager->flush();
            } catch (\Throwable $exception) {
                if (!$this->isDuplicateEmail($exception)) {
                    throw $exception;
                }

                $this->addFlash('danger', 'This e-mail is already registered.');

                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

            $verificationMailer->sendConfirmation($user);
            $this->addFlash('success', 'Registration successful. Confirm your e-mail before you can log in.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    /**
     * Important: uniqueness is enforced by the database unique index, not by a pre-check.
     * Note: concurrent registrations of the same e-mail are rejected by MySQL.
     * Nota bene: catching the constraint error is not the same as SELECT-before-INSERT.
     */
    private function isDuplicateEmail(\Throwable $exception): bool
    {
        while ($exception instanceof \Throwable) {
            if ($exception instanceof UniqueConstraintViolationException) {
                return true;
            }

            $exception = $exception->getPrevious();
        }

        return false;
    }
}
