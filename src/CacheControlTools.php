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

    /**
     * Clear all files and directories in the specified folder inside the site's
     * cache directory. Includes a safety check to never delete anything outside
     * the cache directory.
     *
     * @param string $directory The name of the folder to clear, without a leading slash.
     * @return void
     */
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

    /**
     * Clears out all cache entries for the specified namespaces using
     * ProcessWire's cache API ($cache / WireCache).
     *
     * @param array $namespaces An array of cache namespaces to clear.
     * @return void
     */
    public function clearWireCacheByNamespaces(array $namespaces): void
    {
        foreach ($namespaces as $namespace) {
            $this->cache->deleteFor($namespace);
        }
    }

    /**
     * Get the stored asset version string to append to asset source URLs.
     *
     * @param string $type  Optional asset class / category.
     * @return string
     */
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

    /**
     * Refresh the stored asset version string.
     *
     * @param string|null $type     Optional asset class / category to refresh the version for.
     * @param string|null $version  The new version to store. Defaults to the curernt timestamp.
     * @return void
     */
    public function refreshAssetVersion(?string $type = self::ASSET_CACHE_DEFAULT_KEY, ?string $version = null): void
    {
        $this->cache->setFor(
            self::ASSET_CACHE_NAMESPACE,
            $type . '-' . time(),
            WireCache::expireReserved
        );
    }

    /**
     * Clear out all stored asset versions. New version strings will be
     * automatically generated the next time they are requested.
     *
     * @return void
     */
    public function clearAllAssetVersions(): void
    {
        $this->cache->deleteFor(self::ASSET_CACHE_NAMESPACE);
    }

    /**
     * The following are utility
     */

    /**
     * @see ProcessCacheControl::logMessage
     */
    public function logMessage(string $message): void
    {
        $this->modules->get('ProcessCacheControl')->logMessage($message);
    }

    /**
     * @see ProcessCacheControl::getNewLogMessages
     */
    public function getNewLogMessages(): array
    {
        return $this->modules->get('ProcessCacheControl')->getNewLogMessages();
    }
}
