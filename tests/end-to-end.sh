#!/usr/bin/env sh

# Initialise a test project
composer init --name "ci/test" --stability=dev --no-interaction
composer config version "0.0.1"
echo "Created composer.json"

# The first argument to this script should be the project directory path
composer config repositories.local path $1 || exit 1
echo "Added $1 as a local repository"

# Make sure all plugins are allowed
composer config allow-plugins.vidalia/composer-moodleorg true || exit 1
composer config allow-plugins.composer/installers true || exit 1

# Install the plugin
composer require vidalia/composer-moodleorg || exit 1

# Can we search for things?
if $(composer search format_cards | grep moodledotorg/format_cards -q);
then
  echo "Found moodledotorg/format_cards in search results"
else
  echo "Didn't find moodledotorg/format_cards in search results"
  exit 1
fi

# Test basic plugin installation
composer require moodledotorg/format_cards || exit 1
if [ -d course/format/cards ];
then
  echo "Expected path course/format/cards exists"
else
  echo "Expected path course/format/cards doesn't exist"
  exit 1
fi

# Test uninstallation
composer remove moodledotorg/format_cards || exit 1
if [ -d course/format/cards ];
then
  echo "Expected path course/format/cards still exists"
  exit 1
else
  echo "Expected path course/format/cards was removed"
fi

# Try a different plugin type
composer require moodledotorg/mod_zoom || exit 1
if [ -d mod/zoom ];
then
  echo "Expected path mod/zoom exists"
else
  echo "Expected path mod/zoom doesn't exist"
  exit 1
fi

# How about a constraint?
composer require moodledotorg/format_cards:2024.5.21 || exit 1
composer remove moodledotorg/format_cards || exit 1

# Configuring the namespace
COMPOSER_MOODLEORG_NAMESPACE=customnamespace composer require --no-cache customnamespace/format_cards || exit 1
