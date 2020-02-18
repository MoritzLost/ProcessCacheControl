# Changelog

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
