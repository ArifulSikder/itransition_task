<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

class CacheCleaner
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    /**
     * Deletes every environment cache directory under var/cache.
     */
    public function clearAll(): void
    {
        $filesystem = new Filesystem();
        $cacheDir = $this->projectDir.'/var/cache';

        if ($filesystem->exists($cacheDir)) {
            $filesystem->remove($cacheDir);
        }

        $filesystem->mkdir($cacheDir);
    }
}
