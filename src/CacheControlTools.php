<?php
namespace ProcessWire\ProcessCacheControl;

use ProcessWire\Wire;

class CacheControlTools extends Wire
{
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

    public function getAssetVersion(): string
    {}
}
