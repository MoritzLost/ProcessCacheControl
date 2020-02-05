# ProcessWire Cache Control

This is a module for the [ProcessWire CMF](https://processwire.com/) that adds a _Clear all caches_ button to the backend. It also provides an API for developers to add different cache actions and an interface to execute them.

## Motivation and breakdown

The fastest ProcessWire sites use multiple caching mechanisms to speed up page delivery. This includes, but is not limited to:

- The template render cache provided by ProcessWire.
- The database cache ($cache / WireCache) that you can use in your templates to store the results of long-running queries or other computations.
- HTTP Cache-Control headers that tell browsers to cache static assets like JavaScript and CSS files for some time.
- Other site-specific caching mechanisms, like [Twig's compilation cache](https://twig.symfony.com/doc/3.x/api.html#compilation-cache) if you're using Twig.

This means that you have to clear all those caches whenever you deploy a change your site, often manually. This module aims to make that easier by providing a single "Clear all caches" button in the admin interface that will clear all those caches immediately. As a developer, you can configure what exactly this button does, if you need some cache layers to be more persistent than others. You can also add different cache actions to the Cache Control interface through the API.

## Basic usage and configuration

This module adds a new _Cache Control_ option to the _Setup_ section of the admin menu. Click on that to go to the module page, which lists all available cache actions along with a system log. You can also use the flyout menu to start a cache action from anywhere in the backend.

By default, only one _Clear all_ cache action is available. You can change the behaviour of this action through the module configuration. By default, the module will:

- Clear the template render cache, if it exists.
- Clear the database cache through the $cache API by expiring and/or deleting all entries (except for reserved system entries).
- Clear all stored asset versions (this requires some setup, see [HTTP cache busting for assets](#http-cache-busting-for-assets)).

The module configuration provides some options to change that behaviour. In particular, you can select folders in your site's cache directory to be cleared out as well. This allows you to clear custom site-specific cache directories as well, such as the Twig compilation cache or the ProCache static cache.

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
$CacheControlTools = wire('modules')->get('ProcessCacheControl')->getTools();
$currentVersion = $CacheControlTools->getAssetVersion();
<link rel="stylesheet" type="text/css" href="/site/css/main.css?v=<?= $currentVersion ?>">
<script src="/site/js/main.js?v=<?= $currentVersion ?>"></script>
```

By default, `getAssetVersion()` will create a version string from the current timestamp and store it in the database cache. On subsequent requests, this version will be used until it is cleared from the cache (which happens during the _Clear all_ operation).

You can also specify asset categories, and have seperate versions for each of them:

```php
$CacheControlTools = wire('modules')->get('ProcessCacheControl')->getTools();
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

By default, the module provides only the _Clear all_ cache action that can be triggered from the setup menu or the module page. Additional actions can be added through a hook. A _cache action_ consists of a unique ID, a human-readable title and a callback that is called when the hook is executed. The callback needs to accept exactly one argument, which is an instance of the utility class `CacheControlTools`.

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
        'callback' => function (\ProcessWire\ProcessCacheControl\CacheControlTools $tools) {
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

- The `CacheControlTools` class has a couple of helper methods that you can use to invalidate database caches, clear out cache directories and refresh asset versions. Checkout the full API documentation below to discover the utility methods of the `CacheControlTools`!
- Of course, you can also write custom code inside the callback to perform whatever tasks you want.
- To inform the user about what your cache action is doing, add log messages with `$tools->logMessage('Your message')`.
- By default, the utility methods write some log messages on their own. To toggle this behavious, simply call `$tools->silent()` and `$tools->verbose()` respectively.
- For reference, check out the [action defintion of the default _Clear all_ action](https://github.com/MoritzLost/ProcessCacheControl/blob/master/ProcessCacheControl.module#L62-L82) and the [corresponding callback](https://github.com/MoritzLost/ProcessCacheControl/blob/master/ProcessCacheControl.module#L124-L161).
- If you want to remove the default action, simply replace the return value in your hook with an array of your custom actions instead of adding to it.

## Triggering cache actions through the API

You can manually execute cache actions through the API by using the ID you defined in the previous step. This way, the module will check if the current user has the permission to execute this action before executing it (see [permission system](#permission-system)).

```php
$ProcessCacheControl = wire('modules')->get('ProcessCacheControl');

// execute a custom job
$ProcessCacheControl->executeAction('my-custom-action');

// execute the default 'Clear all' job
$ProcessCacheControl->executeAction(\ProcessWire\ProcessCacheControl::DEFAULT_ACTION_ID);
```

## Modifying the default cache action

The default __Clear all__ cache action can be modified by hooking before or after `ProcessCacheControl::clearAll`.

```php
wire()->addHookAfter('ProcessCacheControl::clearAll', function (HookEvent $e) {
    $tools = $e->arguments(0);
    // do some additional stuff
    $tools->logMessage('Performed some additional task during the default job.');
});
```

## Permission system

The module comes with a permission system that allows you to give different user roles access to different jobs. To access the Cache Control interface and execute any of the available cache actions, the user role needs the 'cache-control' permission that is added during installation.

By default, this permission allows the user role to execute all cache actions. If you want to limit access to a specific cache action, you can create a new permission in the form `cache-control-[ID]`, where `[ID]` is the ID of the custom action. For example: `cache-control-my-custom-action`. If this permission exists, `executeAction` will check if the user has it before executing `my-custom-action`. Note that you don't need to define specific permissions for all actions. If the specific permission for an action doesn't exist, a user only needs the normal `cache-control` permission to execute it.

# API documentation