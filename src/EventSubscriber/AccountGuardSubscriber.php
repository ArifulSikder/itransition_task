<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Fifth requirement: every request except login/registration must prove
 * the current user still exists and is not blocked.
 */
class AccountGuardSubscriber implements EventSubscriberInterface
{
    private const PUBLIC_ROUTES = [
        'app_login',
        'app_register',
        'app_verify_email',
        'app_logout',
    ];

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UserRepository $userRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', -16],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = (string) $request->attributes->get('_route');

        if ($this->isPublicRoute($route)) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        $sessionUser = $token?->getUser();

        if (!$sessionUser instanceof User || $sessionUser->getId() === null) {
            return;
        }

        $freshUser = $this->reloadUser($sessionUser->getId());

        if ($this->mustForceLogin($freshUser)) {
            $this->tokenStorage->setToken(null);
            $request->getSession()->getFlashBag()->add(
                'warning',
                'Your account is blocked, deleted, or not confirmed. Please log in again.',
            );

            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_login')));
        }
    }

    /**
     * Note: login, registration and the confirmation link stay reachable.
     * Important: every other page must pass the existence/blocked check.
     * Nota bene: logout is public so a blocked user can still leave.
     */
    private function isPublicRoute(string $route): bool
    {
        return $route === '' || str_starts_with($route, '_') || \in_array($route, self::PUBLIC_ROUTES, true);
    }

    /**
     * Important: always reload from storage; do not trust the session copy.
     * Note: a missing row means the user was hard-deleted.
     * Nota bene: this is not a uniqueness check, only an existence check.
     */
    private function reloadUser(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    /**
     * Note: deleted users are gone; blocked and unverified users cannot continue.
     * Important: these cases redirect to the login page.
     * Nota bene: only active users may use the management table.
     */
    private function mustForceLogin(?User $user): bool
    {
        return $user === null || $user->isBlocked() || $user->isUnverified();
    }

}
