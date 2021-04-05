# OS2loop

[![Github](https://img.shields.io/badge/source-os2loop/os2loop-blue?style=flat-square)](https://github.com/os2loop/os2loop)
[![Release](https://img.shields.io/github/v/release/os2loop/os2loop?sort=semver&style=flat-square)](https://github.com/os2loop/os2loop/releases)
[![PHP Version](https://img.shields.io/badge/PHP-%5E7.4-9cf)](https://www.php.net/downloads)
[![Build Status](https://img.shields.io/github/workflow/status/itk-dev/os2loop/PR%20Review?&logo=github&style=flat-square)](https://github.com/os2loop/os2loop/actions?query=workflow%3A%22Test+%26+Code+Style+Review%22)
[![Read License](https://img.shields.io/github/license/os2loop/os2loop)](https://github.com/os2loop/os2loop/blob/master/LICENSE.txt)
[![Github downloads](https://img.shields.io/github/downloads/os2loop/os2loop/total?style=flat-square&colorB=darkmagenta)](https://packagist.org/packages/os2loop/os2loop/stats)

OS2loop is a question-answering system built on Drupal 9. See [os2.eu/produkt/os2loop](https://os2.eu/produkt/os2loop)
(in Danish) for more information.

## Installation

### Production

Create local settings file with database connection:

```sh
cat <<'EOF' > web/sites/default/settings.local.php
<?php
$databases['default']['default'] = [
 'database' => getenv('DATABASE_DATABASE') ?: 'db',
 'username' => getenv('DATABASE_USERNAME') ?: 'db',
 'password' => getenv('DATABASE_PASSWORD') ?: 'db',
 'host' => getenv('DATABASE_HOST') ?: 'mariadb',
 'port' => getenv('DATABASE_PORT') ?: '',
 'driver' => getenv('DATABASE_DRIVER') ?: 'mysql',
 'prefix' => '',
];
EOF
```

```sh
composer install --no-dev --optimize-autoloader
vendor/bin/drush --yes site:install os2loop --existing-config
```

### Development

See [docs/development](docs/development/README.md) for details on development.

```sh
docker-compose up --detach
docker-compose exec phpfpm composer install
docker-compose exec phpfpm vendor/bin/drush --yes site:install os2loop --existing-config
# Get the site url
echo "http://$(docker-compose port nginx 80)"
# Get admin sign in url
docker-compose exec phpfpm vendor/bin/drush --yes \
  --uri="http://$(docker-compose port nginx 80)" user:login
```

#### Using `symfony` binary

```sh
docker-compose up --detach
symfony composer install
symfony php vendor/bin/drush --yes site:install os2loop --existing-config
# Start the server
symfony local:server:start --port=8000 --daemon
# Get the site url
echo "http://127.0.0.1:8000"
# Get admin sign in url
symfony php vendor/bin/drush --uri=https://127.0.0.1:8000 user:login
```

### Fixtures

We have fixtures for all content types.

To load all content type fixtures, run:

```sh
# Find and enable all fixtures modules
vendor/bin/drush --yes pm:enable $(find web/profiles/custom/os2loop/modules/ -type f -name 'os2loop_*_fixtures.info.yml' -exec basename -s .info.yml {} \;)
# Load the fixtures
vendor/bin/drush --yes content-fixtures:load
# Uninstall all fixtures modules
vendor/bin/drush --yes pm:uninstall content_fixtures
```

## Updates

```sh
composer install --no-dev --optimize-autoloader
vendor/bin/drush --yes updatedb
vendor/bin/drush --yes config:import
vendor/bin/drush --yes cache:rebuild
```

## Coding standards

```sh
composer coding-standards-check
composer coding-standards-apply
```

```sh
docker run --volume ${PWD}:/app --workdir /app node:latest yarn install
docker run --volume ${PWD}:/app --workdir /app node:latest yarn coding-standards-check
docker run --volume ${PWD}:/app --workdir /app node:latest yarn coding-standards-apply
```

### Twigcs

To run only twigcs:

```sh
composer coding-standards-check/twigcs
```

But this is also a part of

```sh
composer coding-standards-check
```

## Build assets

```sh
docker run --volume ${PWD}:/app --workdir /app node:latest yarn install
docker run --volume ${PWD}:/app --workdir /app node:latest yarn encore dev
 ```

## Automated UI tests

See
<web/profiles/custom/os2loop/modules/os2loop_post/README.md#automated-ui-tests>.
