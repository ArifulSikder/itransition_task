<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserStatus;
use App\Repository\UserRepository;
use App\Service\VerificationMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->redirectToRoute('app_users');
    }

    #[Route('/users', name: 'app_users')]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $this->userRepository->findSortedForTable(),
            'currentUser' => $this->getUser(),
        ]);
    }

    #[Route('/users/resend-verification', name: 'app_resend_verification', methods: ['POST'])]
    public function resendVerification(
        Request $request,
        VerificationMailer $verificationMailer,
    ): Response {
        if (!$this->isCsrfTokenValid('resend_verification', (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('danger', 'The form has expired. Please try again.');

            return $this->redirectToRoute('app_users');
        }

        $user = $this->getUser();
        if (!$user instanceof User || $user->getStatus() !== UserStatus::Unverified) {
            $this->addFlash('warning', 'Only unverified accounts can request a confirmation e-mail.');

            return $this->redirectToRoute('app_users');
        }

        if ($user->getVerificationToken() === null) {
            $user->setVerificationToken($user->getUniqIdValue());
            $this->entityManager->flush();
        }

        $verificationMailer->sendConfirmation($user);
        $this->addFlash('success', 'Confirmation e-mail queued. Check your Mailtrap inbox, then click the link.');

        return $this->redirectToRoute('app_users');
    }

    #[Route('/users/actions', name: 'app_users_actions', methods: ['POST'])]
    public function actions(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('users_toolbar', (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('danger', 'The form has expired. Please try again.');

            return $this->redirectToRoute('app_users');
        }

        $action = (string) $request->request->get('action');
        $selected = $this->userRepository->findByIds($request->request->all('ids'));

        return match ($action) {
            'block' => $this->blockUsers($selected, $request),
            'unblock' => $this->unblockUsers($selected),
            'delete' => $this->deleteUsers($selected, $request),
            'delete_unverified' => $this->deleteUnverified($request),
            default => $this->unknownAction(),
        };
    }

    /**
     * Note: every authenticated user may block any account, including their own.
     * Important: there are no per-row buttons; the toolbar performs the action.
     * Nota bene: blocking yourself ends the session on the next request.
     *
     * @param User[] $users
     */
    private function blockUsers(array $users, Request $request): Response
    {
        if ($users === []) {
            $this->addFlash('warning', 'Select at least one user to block.');

            return $this->redirectToRoute('app_users');
        }

        $blockedSelf = false;

        foreach ($users as $user) {
            $user->setStatus(UserStatus::Blocked);
            $blockedSelf = $blockedSelf || $this->isCurrentUser($user);
        }

        $this->entityManager->flush();
        $this->addFlash('success', sprintf('Blocked %d user(s).', \count($users)));

        if ($blockedSelf) {
            return $this->endOwnSession($request, 'Your account has been blocked.');
        }

        return $this->redirectToRoute('app_users');
    }

    /**
     * @param User[] $users
     */
    private function unblockUsers(array $users): Response
    {
        if ($users === []) {
            $this->addFlash('warning', 'Select at least one user to unblock.');

            return $this->redirectToRoute('app_users');
        }

        foreach ($users as $user) {
            if ($user->getStatus() === UserStatus::Blocked) {
                $user->setStatus(UserStatus::Active);
            }
        }

        $this->entityManager->flush();
        $this->addFlash('success', sprintf('Unblocked %d user(s).', \count($users)));

        return $this->redirectToRoute('app_users');
    }

    /**
     * Important: users are hard-deleted, not marked as deleted.
     * Note: a deleted e-mail can be registered again because the unique index row is gone.
     * Nota bene: deleting yourself must send you back to the login page.
     *
     * @param User[] $users
     */
    private function deleteUsers(array $users, Request $request): Response
    {
        if ($users === []) {
            $this->addFlash('warning', 'Select at least one user to delete.');

            return $this->redirectToRoute('app_users');
        }

        $deletedSelf = false;

        foreach ($users as $user) {
            $deletedSelf = $deletedSelf || $this->isCurrentUser($user);
            $this->entityManager->remove($user);
        }

        $this->entityManager->flush();
        $this->addFlash('success', sprintf('Deleted %d user(s).', \count($users)));

        if ($deletedSelf) {
            return $this->endOwnSession($request, 'Your account has been deleted.');
        }

        return $this->redirectToRoute('app_users');
    }

    private function deleteUnverified(Request $request): Response
    {
        $users = $this->userRepository->findUnverified();

        if ($users === []) {
            $this->addFlash('warning', 'There are no unverified users to delete.');

            return $this->redirectToRoute('app_users');
        }

        return $this->deleteUsers($users, $request);
    }

    private function unknownAction(): Response
    {
        $this->addFlash('danger', 'Unknown toolbar action.');

        return $this->redirectToRoute('app_users');
    }

    private function isCurrentUser(User $user): bool
    {
        $current = $this->getUser();

        return $current instanceof User && $current->getId() === $user->getId();
    }

    private function endOwnSession(Request $request, string $message): Response
    {
        $this->tokenStorage->setToken(null);
        $request->getSession()->invalidate();
        $request->getSession()->getFlashBag()->add('warning', $message);

        return $this->redirectToRoute('app_login');
    }
}
