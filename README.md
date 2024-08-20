# Moodle.org Composer Repository

composer-moodleorg is a [Composer plugin](https://getcomposer.org/doc/articles/plugins.md) that enables you to download 
and install plugins from the [Moodle plugins directory](https://moodle.org/plugins/).

## Installation

Install the plugin with Composer:
```shell
composer global require vidalia/composer-moodleorg
```

## Installing Packages

Moodle plugins aren't namespaced by default, which is a requirement for Composer packages.
By default, plugins available from the plugins directory use the `moodledotorg/` namespace.

For example, to install `format_cards`:
```shell
composer require moodledotorg/format_cards
```

## Version Management

Moodle plugins aren't required to define SemVer versions, which is a requirement for Composer packages.
Instead we munge the Moodle plugin integer version into a CalVer string that's compatible with SemVer.
The Moodle version `yyyymmddpp` becomes `yyyy.m.dpp`, with leading zeroes removed.

For example, to install `format_cards` version `2024052100` you would use
```shell
composer require moodledotorg/format_cards:2024.5.2100
```

## Rate limiting

The Moodle.org plugin directory is meant for human use, so automatically downloading a large number
of packages triggers the rate limiter. By default, the plugin limits the number of concurrent HTTP
requests by setting `COMPOSER_MAX_PARALLEL_HTTP=1`. It also imposes a 12-second cool-off period after
downloading 12 files from Moodle.org.

The environment variables `COMPOSER_MOODLEORG_THROTTLE_COUNT` and `COMPOSER_MOODLEORG_THROTTLE_SLEEP`
can be used to tweak the number of files to be downloaded, and the cool-off period in seconds.

You can disable throttling entirely with `COMPOSER_MOODLEORG_NO_THROTTLE`, which also prevents setting
`COMPOSER_MAX_PARALLEL_HTTP`.
