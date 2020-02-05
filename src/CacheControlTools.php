<?php
namespace ProcessWire\ProcessCacheControl;

use ProcessWire\Wire;
use ProcessWire\WireCache;
use ProcessWire\PageRender;

class CacheControlTools extends Wire
{
    /** @var string The cache namespace for storing asset versions in the database */
    public const ASSET_CACHE_NAMESPACE = 'cache-control-assets';

    /** @var string The default asset type / category if none is specified */
    public const ASSET_CACHE_DEFAULT_KEY = 'default';

    /** @var string The name of the system log for this module */
    public const LOG_NAME = 'cache-control';

    /** @var bool If this is true, all helper methods will not write any log messages */
    protected $silent = false;

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
     * Turn off all logging done by the helper methods of this module. You can
     * still call logMessage to log messages manually.
     *
     * @return self
     */
    public function silent(): self
    {
        $this->silent = true;
        return $this;
    }

    /**
     * Turn the logging done by helper methods back on.
     *
     * @return self
     */
    public function verbose(): self
    {
        $this->silent = false;
        return $this;
    }

    /**
     * Get the stored asset version string to append to asset source URLs. Generates
     * a new version string if none exists.
     *
     * @param string $type  Optional asset class / category.
     * @return string
     */
    public function getAssetVersion(string $type = self::ASSET_CACHE_DEFAULT_KEY): string
    {
        return $this->cache->getFor(
            self::ASSET_CACHE_NAMESPACE,
            $type,
            function () use ($type) {
                return $this->refreshAssetVersion($type);
            }
        );
    }

    /**
     * Refresh the stored asset version string and return it.
     *
     * @param string $type          Optional asset class / category to refresh the version for.
     * @param string|null $version  The new version to store. Defaults to the curernt timestamp.
     * @return string
     */
    public function refreshAssetVersion(string $type = self::ASSET_CACHE_DEFAULT_KEY, ?string $version = null): string
    {
        $version = $version ?? time();
        $this->cache->saveFor(
            self::ASSET_CACHE_NAMESPACE,
            $type,
            $version,
            WireCache::expireReserved
        );
        $this->logMessageIfNotSilent(sprintf(
            $this->_('Updated the asset version for type %1$s to: %2$s'),
            $type,
            $version
        ));
        return $version;
    }

    /**
     * Clear out all stored asset versions. New version strings will be
     * automatically generated the next time they are requested.
     *
     * @return self
     */
    public function clearAllAssetVersions(): self
    {
        $this->cache->deleteFor(self::ASSET_CACHE_NAMESPACE);
        $this->logMessageIfNotSilent($this->_('Cleared all stored asset versions.'));
        return $this;
    }

    /**
     * Clear all files and directories in the specified folder inside the site's
     * cache directory. Includes a safety check to never delete anything outside
     * the cache directory.
     *
     * @param string $directory The name of the folder to clear, without a leading slash.
     * @return self
     */
    public function clearCacheDirectoryContent(string $directory): self
    {
        $dirPath = $this->config->paths->cache . $directory;
        $dirPathFromRoot = $this->config->urls->cache . $directory;
        // check if the directory exists, and exit early if it doesn't
        if (!is_dir($dirPath)) {
            $this->logMessageIfNotSilent(sprintf(
                $this->_('Skipped request to delete missing folder: %s'),
                $dirPathFromRoot
            ));
            return $this;
        }
        // iterate over all contents of the directory and remove them recursively
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
        // log the result
        if ($directory === PageRender::cacheDirName) {
            // special case: the "Page" directory contains the template render cache
            $this->logMessageIfNotSilent($this->_('Cleared the template render cache.'));
        } else {
            $this->logMessageIfNotSilent(sprintf(
                $this->_('Removed all files from the following cache directory: %s'),
                $dirPathFromRoot
            ));
        }
        return $this;
    }

    /**
     * Clears out all cache entries for the specified namespaces using
     * ProcessWire's cache API ($cache / WireCache).
     *
     * @param array $namespaces An array of cache namespaces to clear.
     * @return self
     */
    public function clearWireCacheByNamespaces(array $namespaces): self
    {
        foreach ($namespaces as $namespace) {
            $this->cache->deleteFor($namespace);
        }
        $this->logMessageIfNotSilent(sprintf(
            $this->_('Deleted WireCache entries for the following namespaces: %s'),
            implode(', ', $namespaces)
        ));
        return $this;
    }

    /**
     * Log a message in the dedicated log file for this module. Newly added
     * cache actions should use this method to log messages about what they are
     * doing. The module page will automatically display all messages during
     * this page view.
     *
     * @param string $message   The message to log.
     * @return self
     */
    public function logMessage(string $message): self
    {
        $this->log->save(
            self::LOG_NAME,
            $message
        );
        return $this;
    }

    /**
     * Log a message only if this instance is not currently set to silent.
     *
     * @see self::logMessage
     */
    public function logMessageIfNotSilent(string $message): self
    {
        if (!$this->isSilent) {
            $this->logMessage($message);
        }
        return $this;
    }
}
