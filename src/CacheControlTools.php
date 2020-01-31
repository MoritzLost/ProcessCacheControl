<?php
namespace ProcessWire\ProcessCacheControl;

use ProcessWire\Wire;
use ProcessWire\WireCache;

class CacheControlTools extends Wire
{
    public const ASSET_CACHE_NAMESPACE = 'cache-control-assets';

    public const ASSET_CACHE_DEFAULT_KEY = 'default';

    protected $config;
    protected $cache;
    protected $files;
    protected $log;

    public function __construct()
    {
        $this->config = $this->wire('config');
        $this->cache = $this->wire('cache');
        $this->files = $this->wire('files');
        $this->log = $this->wire('log');
    }

    public function clearCacheDirectoryContent(string $directory): void
    {
        $dirPath = $this->config->paths->cache . $directory;
        if (!is_dir($dirPath)) return;
        $dirIterator = new \DirectoryIterator($dirPath);
        $cacheLimitPath = $this->config->paths->cache;
        foreach ($dirIterator as $fileinfo) {
            if ($fileinfo->isDot()) continue;
            if ($fileinfo->isDir()) {
                $this->files->rmdir(
                    $fileinfo->getPathname(),
                    true,
                    ['limitPath' => $cacheLimitPath]
                );
            }
            if ($fileinfo->isFile() || $fileinfo->isLink()) {
                $this->files->unlink(
                    $fileinfo->getPathname(),
                    $cacheLimitPath
                );
            }
        }
    }

    public function clearWireCacheByNamespaces(array $namespaces): void
    {
        foreach ($namespaces as $namespace) {
            $this->cache->deleteFor($namespace);
        }
    }

    public function getAssetVersion(string $type = self::ASSET_CACHE_DEFAULT_KEY): string
    {
        return $this->cache->getFor(
            self::ASSET_CACHE_NAMESPACE,
            $type,
            WireCache::expireReserved,
            function () use ($type) {
                return $type . '-' . time();
            }
        );
    }

    public function refreshAssetVersion(?string $type = self::ASSET_CACHE_DEFAULT_KEY, ?string $version): void
    {
        $this->cache->setFor(
            self::ASSET_CACHE_NAMESPACE,
            $type . '-' . time(),
            WireCache::expireReserved
        );
    }

    public function clearAllAssetVersions(): void
    {
        $this->cache->deleteFor(self::ASSET_CACHE_NAMESPACE);
    }
}
