# Changelog

## [1.1.1] - 2023-09-26

- **Bugfix:** Fix a potential installation issue. @see https://github.com/processwire/processwire-issues/issues/1462

## [1.1.0] - 2021-03-05

- **Feature:** Asset versions are now saved with WireCache::expireNever instead of WireCache::expireReserved, allowing it to be deleted using $cache->deleteAll() calls.
- **Bugfix:** Fix saved asset versions expiring after one day when generated implicitly using CacheControlTools::getAssetVersion. Asset versions will now be correctly saved without an expiration time.
- **Docs:** Fix grammar mistake in the module configuration. fixes issue #2

## [1.0.0] - 2020-04-01

- **Milestone:** First stable release!
- **Feature:** Added experimental ProCache integration. The module now has an additional option to clear the ProCache during the default action. This uses $procache->clearAll().
- **Docs:** Added documentation and usage examples for the new methods added in the previous release.

## [0.5.0] - 2020-03-19

- **Feature:** The process page only displays actions the current user can execute, both in the setup menu and on the process page itself.
- **Feature:** Added a static method `ProcessCacheControl::canUseModule` to check if a user has access to the module. This should be used before instantiating the module to avoid errors.
- **Feature:** Added new helper methods to check if a user can execute a specific action and get all actions a user can execute.
- **Feature:** Added a helper method to get the URL of the process page, or the URL that executes a specific action.
- **Feature:** Added a helper method to check if an action exists.
- **Feature:** Added a clear success message through the system messages system after an action is executed on the Process page.
- **Refactor:** If a non-existent action is requested on the process page (through a GET-parameter), the module will display an warning message instead of throwing an error.

## [0.4.0] - 2020-02-18

- **Refactor / Bugfix:** The `CacheControlTools` class is now a ProcessWire module as well and is installed alongside the main module. This was necessary because the `ProcessCacheControl` module restricts access through the module system, so it couldn't be instantiated during requests from unprivileged users at all.
    - Note that the CacheControlTools has changed to the ProcessWire namespace. Before: `\ProcessWire\ProcessCacheControl\CacheControlTools` | Now: `ProcessWire\CacheControlTools`
    - The documentation has been updated accordingly.
- **Refactor:** The module is no longer autoloaded for every request.
- **Docs:** Added installation instructions to the README.

## [0.3.0] - 2020-02-16

- **Bugfix:** Removed a trailing comma after the last argument in a function call that was preventing installation on PHP<=7.3 ([issue on Github](https://github.com/MoritzLost/ProcessCacheControl/issues/1)).

## [0.2.0] - 2020-02-06

Initial public beta release with a functional "Clear all caches" cache action, a simple interface to trigger actions and view the action log, and an developer API to add new actions, trigger actions and perform cache management tasks.
