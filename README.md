# ProcessWire Cache Control

This is a module for the [ProcessWire CMF](https://processwire.com/) that adds a _Clear all caches_ button to the backend. It also provides an API for developers to add different cache actions and an interface to execute them.

## Table of Contents

- [Motivation and breakdown](#description)
- [Installation](#installation)
- [Basic usage and configuration](#features)
- [HTTP cache busting for assets](#http-cache-busting-for-assets)
- [Defining additional cache actions](#defining-additional-cache-actions)
- [Listing and executing cache actions through the API](#listing-and-executing-cache-actions-through-the-api)
- [Modifying the default cache action](#modifying-the-default-cache-action)
- [Permission system](#permission-system)

## Motivation and breakdown

The fastest ProcessWire sites use multiple caching mechanisms to speed up page delivery. This includes, but is not limited to:

- The template render cache provided by ProcessWire.
- The database cache (`$cache` / `WireCache`) that you can use in your templates to store the results of long-running queries or other computations.
- The commercial [ProCache module](https://processwire.com/store/pro-cache/).
- HTTP `Cache-Control` headers that tell browsers to cache static assets like JavaScript and CSS files for some time.
- Other site-specific caching mechanisms, like [Twig's compilation cache](https://twig.symfony.com/doc/3.x/api.html#compilation-cache) if you're using Twig.

This means that you have to clear all those caches whenever you deploy a change your site, often manually. This module aims to make that easier by providing a single _Clear all caches_ link in the admin interface that will clear all those caches immediately. As a developer, you can configure what exactly this button does, if you need some cache layers to be more persistent than others. You can also add different cache actions to the Cache Control interface through the API.

## Installation

The module can be downloaded through the ProcessWire backend using the module's class name `ProcessCacheControl`. Alternatively, you can grab the currently published version from the [module directory](https://modules.processwire.com/modules/process-cache-control/) or the [Github repository](https://github.com/MoritzLost/ProcessCacheControl).

Note that the repository contains to separate modules:

- `ProcessCacheControl` is the 'main' module, it provides the admin interface and the module configuration.
- `CacheControlTools` is a utility module with fewer access restrictions that you can use to perform some actions directly.

`ProcessCacheControl` installs and uninstalls `CacheControlTools` automatically alongside it.

## Basic usage and configuration

This module adds a new _Cache Control_ page to the _Setup_ section of the admin menu. Click on that to go to the module page, which lists all available cache actions along with a system log. You can also use the flyout menu to start a cache action from anywhere in the backend.

By default, only one _Clear all_ cache action is available. You can change the behaviour of this action through the module configuration. By default, the module will:

- Clear the template render cache, if it exists.
- Clear the database cache through the `$cache` API by expiring and/or deleting all entries (except for reserved system entries).
- Clear the ProCache module cache, if it is installed.
- Clear all stored asset versions (this requires some setup, see [HTTP cache busting for assets](#http-cache-busting-for-assets)).

The module configuration provides some options to change that behaviour. In particular, you can select folders in your site's cache directory to be cleared out as well. This allows you to clear site-specific cache folders as well, such as the Twig compilation cache or the ProCache static cache.

Go to Modules -> Configure -> ProcessCacheControl to configure the default _Clear all_ action.

## HTTP cache busting for assets

For your static assets (JavaScript and CSS files, for example), you want the browser to store those as long as possible. This is why you usually want to sent `Cache-Control` or `Expires` headers that specify the lifetime of an asset. For Apache, a sample configuration could look like this:

```apacheconf
<FilesMatch "\.(css|js)$">
    Header set Cache-Control "max-age=31622400, public"
</FilesMatch>
```

The problem with long cache times is that if you replace the files on the server, users that have previously visited your site may not get the new version because their browser still has the old one in the cache. The usual workaround for that is to add a version parameter to the asset's URL that the server ignores, but forces the browser to request the file from the server again if it changes:

```php
<link rel="stylesheet" type="text/css" href="/site/css/main.css?v=1.2.3">
<script src="/site/js/main.js?v=1.2.3"></script>
```

This module provides an interface for managing those asset versions, and automatically refreshes them when all caches are cleared. All you need to do to make this work is to request the current version string from the module, and add it to your assets in the source code:

```php
$CacheControlTools = wire('modules')->get('CacheControlTools');
$currentVersion = $CacheControlTools->getAssetVersion();
<link rel="stylesheet" type="text/css" href="/site/css/main.css?v=<?= $currentVersion ?>">
<script src="/site/js/main.js?v=<?= $currentVersion ?>"></script>
```

The first time `getAssetVersion()` in called, it will create a version string from the current timestamp and store it in the database cache. On subsequent requests, this version string will be used until it is cleared from the cache (which happens during the _Clear all_ operation).

You can also specify asset categories, and have seperate versions for each of them:

```php
$CacheControlTools = wire('modules')->get('CacheControlTools');
 // you can use any asset category you want
$cssVersion = $CacheControlTools->getAssetVersion('css');
$jsVersion = $CacheControlTools->getAssetVersion('javascript');
<link rel="stylesheet" type="text/css" href="/site/css/main.css?v=<?= $cssVersion ?>">
<script src="/site/js/main.js?v=<?= $jsVersion ?>"></script>
```

If you want to manually manage versions, you can use the following method:

```php
// refresh the asset version for the default asset category
$CacheControlTools->refreshAssetVersion();

// refresh the asset version for the asset category "javascript"
$CacheControlTools->refreshAssetVersion('javascript');

// manually set the asset version for the asset category "javascript" to "v1.2.3"
$CacheControlTools->refreshAssetVersion('javascript', 'v1.2.3');
```

Finally, you can clear all stored asset versions for all asset categories, causing them to be recreated the next time they are requested:

```php
$CacheControlTools->clearAllAssetVersions();
```

## Defining additional cache actions

By default, the module provides only the _Clear all_ cache action that can be triggered from the setup menu or the module page. Additional actions can be added through a hook. A _cache action_ consists of a unique ID, a human-readable title and a callback that is called when the hook is executed. The callback needs to accept exactly one argument, which is an instance of the utility module `CacheControlTools`.

To add cache actions to the module, hook after `ProcessCacheControl::getActionDefinitions`. This methods returns an array of cache actions. You can add your own actions by modifying the return value.

```php
wire()->addHookAfter('ProcessCacheControl::getActionDefinitions', function (HookEvent $e) {
    $actions = $e->return;
    $actions[] = [
        // id should consist only of lowercase letters and hyphens
        'id' => 'my-custom-action',
        // the action will be displayed with this title in the flyout menu
        'title' => 'My Custom Action',
        // the callback to be called when this action is executed
        'callback' => function (\ProcessWire\CacheControlTools $tools) {
            // clear the asset versions for asset category 'javascript'
            $tools->refreshAssetVersion('javascript');

            // clear a custom cache directory (inside /site/assets/cache/)
            $tools->clearCacheDirectoryContent('MyCustomCacheLocation');

            // clear some database caches
            $tools->clearWireCacheByNamespaces([
                'my-custom-namespace-1',
                'my-custom-namespace-2',
            ]);

            // add a log message for the user
            $tools->logMessage('My custom action finished successfully!');
        }
    ];
    $e->return = $actions;
});
```

- The `CacheControlTools` module has a couple of helper methods that you can use to invalidate database caches, clear out cache directories and refresh asset versions. Currently there is no external documentation, but the [source code is well documentated](CacheControlTools.module).
- Of course, you can also write custom code inside the callback to perform whatever tasks you want.
- To inform the user about what your cache action is doing, add log messages with `$tools->logMessage('Your message')`.
- By default, the utility methods write some log messages on their own. To toggle this behaviour, simply call `$tools->silent()` and `$tools->verbose()` respectively.
- For reference, check out the [action defintion of the default _Clear all_ action](https://github.com/MoritzLost/ProcessCacheControl/blob/master/ProcessCacheControl.module#L75-L95) and the [corresponding callback](https://github.com/MoritzLost/ProcessCacheControl/blob/master/ProcessCacheControl.module#L135-L181).
- If you want to remove the default action, simply replace the return value in your hook with an array of your custom actions instead of adding to it.

## Listing and executing cache actions through the API

You may want to include links to the Process page or specific actions somewhere else on your site (for example, in a list of context links for logged-in editors). In this case, you'll always want to check if the current user can use the module before doing anything with it. If they don't have the required permission, trying to instantiate the module will throw an error. Because of this, you can check if the user has access using a static method:

```php
$canUseProcessCacheControl = \ProcessWire\ProcessCacheControl::canUseModule();
```

If this returns true, you can safely instantiate the module.

After that, you can use the instance to list all actions the current user can execute and output links that directly execute a specific action:

```php
$ProcessCacheControl = wire('modules')->get('ProcessCacheControl');

// get all actions the current user can execute
$userExecutableActions = $ProcessCacheControl->getAllowedActions();

// output links to those actions
foreach ($userExecutableActions as $actionDefinition) {
    echo '<a href="' . $ProcessCacheControl->getProcessUrl($actionDefinition['id']) . '">' . $actionDefinition['title'] . '</a>';
}
```

You can also manually execute cache actions through the API by using their ID. Before you do that, you can check if an action with the ID exists, and if the current user can execute it.

```php
$ProcessCacheControl = wire('modules')->get('ProcessCacheControl');

$actionId = 'my-custom-action';

// check if the action exists
$actionExists = $ProcessCacheControl->actionExists($actionId);

// check if the current user can execute it
$canExecuteAction = $ProcessCacheControl->canExecuteAction($actionId);

// execute a custom cache action
$ProcessCacheControl->executeAction($actionId);

// you can also execute the default 'Clear all' cache action this way
$ProcessCacheControl->executeAction(\ProcessWire\ProcessCacheControl::DEFAULT_ACTION_ID);
```

## Modifying the default cache action

The default _Clear all_ cache action can be modified by hooking before or after `ProcessCacheControl::clearAll`.

```php
wire()->addHookAfter('ProcessCacheControl::clearAll', function (HookEvent $e) {
    $tools = $e->arguments(0);
    // do some additional stuff
    $tools->logMessage('Performed some additional task during the default cache action.');
});
```

## Permission system

The module comes with a permission system that allows you to give different user roles access to different cache actions. To access the Cache Control interface and execute any of the available cache actions, the user role needs the 'cache-control' permission that is added during installation.

By default, this permission allows the user role to execute all cache actions. If you want to limit access to a specific cache action, you can create a new permission in the form `cache-control-[ID]`, where `[ID]` is the ID of the custom action. For example: `cache-control-my-custom-action`. If this permission exists, `executeAction` will check if the user has it before executing `my-custom-action`. Note that you don't need to define specific permissions for all actions. If the specific permission for an action doesn't exist, a user only needs the normal `cache-control` permission to execute it.
