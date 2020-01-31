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
            ], // @TODO: Sensible default, but only if folders exist
            // HTTP ASSET CACHE???
            // CLEAR ACTIVE SESSIONS (// except current user?)?
        ];
    }

    public function getInputFields()
    {
        $inputfields = parent::getInputfields();

        $WireCacheExpireAll = wire()->modules->get('InputfieldCheckbox');
        $WireCacheExpireAll->name = 'WireCacheExpireAll';
        $WireCacheExpireAll->label = $this->_('Cache API: Expire all');
        $WireCacheExpireAll->label2 = $this->_('Expire all caches in the database that have an expiration date.');
        // @TODO: Link to method in WireCache
        $WireCacheExpireAll->columnWidth = 25;
        $WireCacheExpireAll->collapsed = Inputfield::collapsedNever;

        $WireCacheDeleteAll = wire()->modules->get('InputfieldCheckbox');
        $WireCacheDeleteAll->name = 'WireCacheDeleteAll';
        $WireCacheDeleteAll->label = $this->_('Cache API: Delete all');
        $WireCacheDeleteAll->checkboxOnly = false;
        $WireCacheDeleteAll->label2 = $this->_('Delete all caches in the database, except for the reserved system cache.');
        // @TODO: Link to method in WireCache
        $WireCacheDeleteAll->columnWidth = 25;
        $WireCacheDeleteAll->collapsed = Inputfield::collapsedNever;

        $WireCacheDeleteNamespaces = wire()->modules->get('InputfieldTextarea');
        $WireCacheDeleteNamespaces->name = 'WireCacheDeleteNamespaces';
        $WireCacheDeleteNamespaces->label = $this->_('Cache API: Delete caches by namespace.');
        $WireCacheDeleteNamespaces->description = $this->_('Specify one namespace per line. All cache entries in the database matching the specified namespace(s) will be deleted.');
        $WireCacheDeleteNamespaces->notes = $this->_('You can use this to selectively clear  caches from specific namespaces. In this case, you may want to turn of the Expire all and Delete all options.');
        $WireCacheDeleteNamespaces->columnWidth = 50;
        $WireCacheDeleteNamespaces->collapsed = Inputfield::collapsedNever;
        // @TODO: Link to documentation in WireCache

        // Show location
        $ClearCacheDirectories = wire()->modules->get("InputfieldCheckboxes");
        $ClearCacheDirectories->name = 'ClearCacheDirectories';
        $ClearCacheDirectories->label = $this->_('Clear Cache Directories');
        $ClearCacheDirectories->description = $this->_("Select which folders in your site's cache directory you want to clear.");
        $ClearCacheDirectories->notes = $this->_('"Page" should always be selected, as it contains the template render cache. If a folder in your cache directory does not appear in this list, it may not be writable by the server.');
        $ClearCacheDirectories->columnWidth = 50;

        $cacheDirectories = $this->getCacheDirectories();
        foreach ($cacheDirectories as $cacheDir) {
            $ClearCacheDirectories->addOption($cacheDir, $cacheDir);
        }

        $defaultActionFieldset = wire()->modules->get('InputfieldFieldset');
        $defaultActionFieldset->label = $this->_('Default "Clear All" action');
        $defaultActionFieldset->description = $this->_('Those options control what happens when you the default "Clear all" action is executed. Consult the documentation to find out how to add custom actions to the module.');
        $defaultActionFieldset->collapsed = Inputfield::collapsedNo;

        $defaultActionFieldset->add($WireCacheExpireAll);
        $defaultActionFieldset->add($WireCacheDeleteAll);
        $defaultActionFieldset->add($WireCacheDeleteNamespaces);
        $defaultActionFieldset->add($ClearCacheDirectories);

        $inputfields->add($defaultActionFieldset);

        return $inputfields;
    }

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
