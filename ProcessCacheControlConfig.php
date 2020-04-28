<?php

namespace Processwire;

use ProcessWire\Inputfield;

class ProcessCacheControlConfig extends ModuleConfig
{
    public function getDefaults()
    {
        return [
            'WireCacheExpireAll' => true,
            'WireCacheDeleteAll' => true,
            'WireCacheDeleteNamespaces' => '',
            'PageRenderExpireAll' => true,
            'ClearCacheDirectories' => [
                'Page'
            ],
            'ClearAllAssetVersions' => true,
            'ClearProCache' => true,
        ];
    }

    public function getInputFields()
    {
        $inputfields = parent::getInputfields();

        // expire all caches
        $WireCacheExpireAll = wire()->modules->get('InputfieldCheckbox');
        $WireCacheExpireAll->name = 'WireCacheExpireAll';
        $WireCacheExpireAll->label = $this->_('Cache API: Expire all');
        $WireCacheExpireAll->label2 = $this->_('Expire all caches in the database that have an expiration date.');
        $WireCacheExpireAll->notes = $this->_('See [$cache->expireAll](https://processwire.com/api/ref/wire-cache/expire-all/) in the API documentation.');
        $WireCacheExpireAll->columnWidth = 25;
        $WireCacheExpireAll->collapsed = Inputfield::collapsedNever;

        // delete all caches
        $WireCacheDeleteAll = wire()->modules->get('InputfieldCheckbox');
        $WireCacheDeleteAll->name = 'WireCacheDeleteAll';
        $WireCacheDeleteAll->label = $this->_('Cache API: Delete all');
        $WireCacheDeleteAll->label2 = $this->_('Delete all caches in the database, except for the reserved system cache.');
        $WireCacheDeleteAll->notes = $this->_('See [$cache->deleteAll](https://processwire.com/api/ref/wire-cache/delete-all/) in the API documentation.');
        $WireCacheDeleteAll->columnWidth = 25;
        $WireCacheDeleteAll->collapsed = Inputfield::collapsedNever;

        // delete individual cache namespaces
        $WireCacheDeleteNamespaces = wire()->modules->get('InputfieldTextarea');
        $WireCacheDeleteNamespaces->name = 'WireCacheDeleteNamespaces';
        $WireCacheDeleteNamespaces->label = $this->_('Cache API: Delete caches by namespace.');
        $WireCacheDeleteNamespaces->description = $this->_('Specify one namespace per line. All cache entries in the database matching the specified namespace(s) will be deleted.');
        $WireCacheDeleteNamespaces->notes = $this->_("You can use this to selectively clear caches from specific namespaces. In this case, you may want to turn of the Expire all and Delete all options.\n See [\$cache->deleteFor](https://processwire.com/api/ref/wire-cache/delete-for/) in the API documentation.");
        $WireCacheDeleteNamespaces->columnWidth = 50;
        $WireCacheDeleteNamespaces->collapsed = Inputfield::collapsedNever;

        // clear individual cache directories
        $ClearCacheDirectories = wire()->modules->get('InputfieldCheckboxes');
        $ClearCacheDirectories->name = 'ClearCacheDirectories';
        $ClearCacheDirectories->label = $this->_('Clear Cache Directories');
        $ClearCacheDirectories->description = sprintf(
            $this->_("Select which folders in your site's cache directory you want to clear.\n Current cache directory: `%s`"),
            $this->config->urls->cache
        );
        $ClearCacheDirectories->notes = $this->_("If a 'Page' entry exists it should always be selected, as it contains the template render cache.\n If a folder in your cache directory does not appear in this list, it may not be writable by the server.");
        $ClearCacheDirectories->columnWidth = 34;
        $ClearCacheDirectories->collapsed = Inputfield::collapsedNever;
        $cacheDirectories = $this->getCacheDirectories();
        foreach ($cacheDirectories as $cacheDir) {
            $ClearCacheDirectories->addOption($cacheDir, $cacheDir);
        }

        // clear ProCache
        $ClearProCache = wire()->modules->get('InputfieldCheckbox');
        $ClearProCache->name = 'ClearProCache';
        $ClearProCache->label = $this->_('ProCache');
        $ClearProCache->label2 = $this->_('Clear the entire cache of the commercial ProCache module.');
        $siteHasProcache = $this->wire('procache') !== null;
        $ClearProCache->description = $siteHasProcache
            ? $this->_('Your site appears to be running ProCache.')
            : $this->_("Your site doesn't appear to be running ProCache, so this option will have no effect.");
        $ClearProCache->notes = $this->_('This uses [$procache->clearAll](https://processwire.com/store/pro-cache/#procache-api).');
        $ClearProCache->columnWidth = 33;
        $ClearProCache->collapsed = Inputfield::collapsedNever;

        // clear asset versions
        $ClearAllAssetVersions = wire()->modules->get('InputfieldCheckbox');
        $ClearAllAssetVersions->name = 'ClearAllAssetVersions';
        $ClearAllAssetVersions->label = $this->_('Asset versions');
        $ClearAllAssetVersions->label2 = $this->_('Refresh all stored asset versions.');
        $ClearAllAssetVersions->notes = $this->_('This requires some setup in your templates. See the [documentation](https://github.com/MoritzLost/ProcessCacheControl) for details.');
        $ClearAllAssetVersions->columnWidth = 33;
        $ClearAllAssetVersions->collapsed = Inputfield::collapsedNever;

        // wrap inside a fieldset for the default action
        $defaultActionFieldset = wire()->modules->get('InputfieldFieldset');
        $defaultActionFieldset->label = $this->_('Default "Clear all" action');
        $defaultActionFieldset->description = $this->_('These options control what happens when the default "Clear all" action is executed. Consult the documentation to find out how to add custom actions to the module.');
        $defaultActionFieldset->collapsed = Inputfield::collapsedNo;
        $defaultActionFieldset->add($WireCacheExpireAll);
        $defaultActionFieldset->add($WireCacheDeleteAll);
        $defaultActionFieldset->add($WireCacheDeleteNamespaces);
        $defaultActionFieldset->add($ClearCacheDirectories);
        $defaultActionFieldset->add($ClearProCache);
        $defaultActionFieldset->add($ClearAllAssetVersions);

        $inputfields->add($defaultActionFieldset);
        return $inputfields;
    }

    /**
     * Get all all writable folders in the site's cache directory.
     *
     * @return array
     */
    protected function getCacheDirectories(): array
    {
        $cacheDir = $this->config->paths->cache;
        $cacheDirIterator = new \DirectoryIterator($cacheDir);
        $cacheDirectories = [];
        foreach ($cacheDirIterator as $fileinfo) {
            if (!$fileinfo->isDir() || $fileinfo->isDot()) continue;
            if (!$fileinfo->isWritable()) continue;
            $cacheDirectories[] = $fileinfo->getBasename();
        }
        return $cacheDirectories;
    }
}
