<?php

namespace App\Controller;

use App\Service\CacheCleaner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CacheController extends AbstractController
{
    public function __construct(
        private readonly CacheCleaner $cacheCleaner,
        #[Autowire('%env(CACHE_CLEAR_TOKEN)%')]
        private readonly string $cacheClearToken,
    ) {
    }

    #[Route('/clear-cache', name: 'app_clear_cache', methods: ['GET'])]
    public function clearAll(Request $request): Response
    {
        $token = (string) $request->query->get('token', '');

        if ($this->cacheClearToken === '' || $token === '' || !hash_equals($this->cacheClearToken, $token)) {
            throw $this->createAccessDeniedException();
        }

        $this->cacheCleaner->clearAll();

        return new Response('All cache has been cleared.', Response::HTTP_OK, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
