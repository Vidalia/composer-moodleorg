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
