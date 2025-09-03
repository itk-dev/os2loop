# OS2loop

[![Woodpecker](https://img.shields.io/badge/woodpecker-prod|stg-blue.svg?style=flat-square&logo=data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMiIgaGVpZ2h0PSIyMiI+PHBhdGggZmlsbD0iI2ZmZiIgZD0iTTEuMjYzIDIuNzQ0QzIuNDEgMy44MzIgMi44NDUgNC45MzIgNC4xMTggNS4wOGwuMDM2LjAwN2MtLjU4OC42MDYtMS4wOSAxLjQwMi0xLjQ0MyAyLjQyMy0uMzggMS4wOTYtLjQ4OCAyLjI4NS0uNjE0IDMuNjU5LS4xOSAyLjA0Ni0uNDAxIDQuMzY0LTEuNTU2IDcuMjY5LTIuNDg2IDYuMjU4LTEuMTIgMTEuNjMuMzMyIDE3LjMxNy42NjQgMi42MDQgMS4zNDggNS4yOTcgMS42NDIgOC4xMDdhLjg1Ny44NTcgMCAwMC42MzMuNzQ0Ljg2Ljg2IDAgMDAuOTIyLS4zMjNjLjIyNy0uMzEzLjUyNC0uNzk3Ljg2LTEuNDI0Ljg0IDMuMzIzIDEuMzU1IDYuMTMgMS43ODMgOC42OTdhLjg2Ni44NjYgMCAwMDEuNTE3LjQxYzIuODgtMy40NjMgMy43NjMtOC42MzYgMi4xODQtMTIuNjc0LjQ1OS0yLjQzMyAxLjQwMi00LjQ1IDIuMzk4LTYuNTgzLjUzNi0xLjE1IDEuMDgtMi4zMTggMS41NS0zLjU2Ni4yMjgtLjA4NC41NjktLjMxNC43OS0uNDQxbDEuNzA3LS45ODEtLjI1NiAxLjA1MmEuODY0Ljg2NCAwIDAwMS42NzguNDA4bC42OC0yLjg1OCAxLjI4NS0yLjk1YS44NjMuODYzIDAgMTAtMS41ODEtLjY4N2wtMS4xNTIgMi42NjktMi4zODMgMS4zNzJhMTguOTcgMTguOTcgMCAwMC41MDgtMi45ODFjLjQzMi00Ljg2LS43MTgtOS4wNzQtMy4wNjYtMTEuMjY2LS4xNjMtLjE1Ny0uMjA4LS4yODEtLjI0Ny0uMjYuMDk1LS4xMi4yNDktLjI2LjM1OC0uMzc0IDIuMjgzLTEuNjkzIDYuMDQ3LS4xNDcgOC4zMTkuNzUuNTg5LjIzMi44NzYtLjMzNy4zMTYtLjY3LTEuOTUtMS4xNTMtNS45NDgtNC4xOTYtOC4xODgtNi4xOTMtLjMxMy0uMjc1LS41MjctLjYwNy0uODktLjkxM0M5LjgyNS41NTUgNC4wNzIgMy4wNTcgMS4zNTUgMi41NjljLS4xMDItLjAxOC0uMTY2LjEwMy0uMDkyLjE3NW0xMC45OCA1Ljg5OWMtLjA2IDEuMjQyLS42MDMgMS44LTEgMi4yMDgtLjIxNy4yMjQtLjQyNi40MzYtLjUyNC43MzgtLjIzNi43MTQuMDA4IDEuNTEuNjYgMi4xNDMgMS45NzQgMS44NCAyLjkyNSA1LjUyNyAyLjUzOCA5Ljg2LS4yOTEgMy4yODgtMS40NDggNS43NjMtMi42NzEgOC4zODUtMS4wMzEgMi4yMDctMi4wOTYgNC40ODktMi41NzcgNy4yNTlhLjg1My44NTMgMCAwMC4wNTYuNDhjMS4wMiAyLjQzNCAxLjEzNSA2LjE5Ny0uNjcyIDkuNDZhOTYuNTg2IDk2LjU4NiAwIDAwLTEuOTctOC43MTFjMS45NjQtNC40ODggNC4yMDMtMTEuNzUgMi45MTktMTcuNjY4LS4zMjUtMS40OTctMS4zMDQtMy4yNzYtMi4zODctNC4yMDctLjIwOC0uMTgtLjQwMi0uMjM3LS40OTUtLjE2Ny0uMDg0LjA2LS4xNTEuMjM4LS4wNjIuNDQ0LjU1IDEuMjY2Ljg3OSAyLjU5OSAxLjIyNiA0LjI3NiAxLjEyNSA1LjQ0My0uOTU2IDEyLjQ5LTIuODM1IDE2Ljc4MmwtLjExNi4yNTktLjQ1Ny45ODJjLS4zNTYtMi4wMTQtLjg1LTMuOTUtMS4zMy01Ljg0LTEuMzgtNS40MDYtMi42OC0xMC41MTUtLjQwMS0xNi4yNTQgMS4yNDctMy4xMzcgMS40ODMtNS42OTIgMS42NzItNy43NDYuMTE2LTEuMjYzLjIxNi0yLjM1NS41MjYtMy4yNTIuOTA1LTIuNjA1IDMuMDYyLTMuMTc4IDQuNzQ0LTIuODUyIDEuNjMyLjMxNiAzLjI0IDEuNTkzIDMuMTU2IDMuNDJ6bS0yLjg2OC42MmExLjE3NyAxLjE3NyAwIDEwLjczNi0yLjIzNiAxLjE3OCAxLjE3OCAwIDEwLS43MzYgMi4yMzd6Ii8+PC9zdmc+Cg==)](https://woodpecker.itkdev.dk/repos/10)
[![Github](https://img.shields.io/badge/source-os2loop/os2loop-blue?style=flat-square)](https://github.com/os2loop/os2loop)
[![GitHub Release](https://img.shields.io/github/v/release/itk-dev/os2loop?style=flat-square&logo=data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA0NDggNTEyIj48IS0tIUZvbnQgQXdlc29tZSBGcmVlIDYuNy4yIGJ5IEBmb250YXdlc29tZSAtIGh0dHBzOi8vZm9udGF3ZXNvbWUuY29tIExpY2Vuc2UgLSBodHRwczovL2ZvbnRhd2Vzb21lLmNvbS9saWNlbnNlL2ZyZWUgQ29weXJpZ2h0IDIwMjUgRm9udGljb25zLCBJbmMuLS0+PHBhdGggZmlsbD0iI2ZmZiIgZD0iTTAgODBMMCAyMjkuNWMwIDE3IDYuNyAzMy4zIDE4LjcgNDUuM2wxNzYgMTc2YzI1IDI1IDY1LjUgMjUgOTAuNSAwTDQxOC43IDMxNy4zYzI1LTI1IDI1LTY1LjUgMC05MC41bC0xNzYtMTc2Yy0xMi0xMi0yOC4zLTE4LjctNDUuMy0xOC43TDQ4IDMyQzIxLjUgMzIgMCA1My41IDAgODB6bTExMiAzMmEzMiAzMiAwIDEgMSAwIDY0IDMyIDMyIDAgMSAxIDAtNjR6Ii8+PC9zdmc+)](https://github.com/itk-dev/os2loop/releases)
[![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/itk-dev/os2loop/pr.yaml?style=flat-square&logo=github)](https://github.com/itk-dev/os2loop/actions/workflows/pr.yaml)
[![Codecov](https://img.shields.io/codecov/c/github/itk-dev/os2loop?style=flat-square&logo=codecov)](https://codecov.io/gh/itk-dev/os2loop)
[![GitHub last commit](https://img.shields.io/github/last-commit/itk-dev/os2loop?style=flat-square)](https://github.com/itk-dev/os2loop/commits/develop/)
[![GitHub License](https://img.shields.io/github/license/itk-dev/os2loop?style=flat-square)](https://github.com/itk-dev/os2loop/blob/develop/LICENSE)

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
vendor/bin/drush --yes locale:update
```

You must also build the [OS2Loop
theme](web/profiles/custom/os2loop/themes/os2loop_theme/README.md) assets; see
[Building
assets](web/profiles/custom/os2loop/themes/os2loop_theme/README.md#building-assets)
for details.

After installing the site you should check out [Configuring
OS2Loop](docs/configuring-os2loop.md) for important and useful post-install
instructions.

See [OS2Loop modules](docs/modules.md) for a complete list of custom OS2Loop
modules.

### Development

See [docs/development](docs/development/README.md) for details on development.

[Install Task](https://taskfile.dev/installation/) and run

```sh
task dev:up
task dev:install-site --yes
# Get the site url
echo "http://$(docker compose port nginx 8080)"
# Get admin sign in url
task dev:drush -- --yes --uri="http://$(docker compose port nginx 8080)" user:login
```

### Modules

Uses a dev version of views_flag_refresh since the module
is not yet available on drupal.org

#### Mails

Mails are caught by [Mailpit](https://github.com/axllent/mailpit) and can be
read on the url reported by

```sh
echo "http://$(docker compose port mail 8025)"
```

### Fixtures

We have fixtures for all content types.

To load all content type fixtures, run:

```sh
# Find and enable all fixtures modules
docker compose exec phpfpm vendor/bin/drush --yes pm:enable $(find web/profiles/custom/os2loop/modules/ -type f -name 'os2loop_*_fixtures.info.yml' -exec basename -s .info.yml {} \;)
# Disable "Entity Reference Integrity Enforce" module to be able to delete (purge) content before loading fixtures.
docker compose exec phpfpm vendor/bin/drush --yes pm:uninstall entity_reference_integrity_enforce
# Load the fixtures
docker compose exec phpfpm vendor/bin/drush --yes content-fixtures:load
# Uninstall all fixtures modules
docker compose exec phpfpm vendor/bin/drush --yes pm:uninstall content_fixtures
# Enable "Entity Reference Integrity Enforce" module
docker compose exec phpfpm vendor/bin/drush --yes pm:enable entity_reference_integrity_enforce
```

Commands to get machine names

```sh
docker compose exec phpfpm vendor/bin/drush field:info
```

## Updates

```sh
docker compose exec phpfpm composer install --no-dev --optimize-autoloader
docker compose exec phpfpm vendor/bin/drush --yes updatedb
docker compose exec phpfpm vendor/bin/drush --yes config:import
docker compose exec phpfpm vendor/bin/drush --yes locale:update
docker compose exec phpfpm vendor/bin/drush --yes cache:rebuild
```

## Translations

Import translations by running

```sh
(cd web && ../vendor/bin/drush locale:import --type=customized --override=none da profiles/custom/os2loop/translations/translations.da.po)
```

Export translations by running

```sh
(cd web && ../vendor/bin/drush locale:export da --types=customized > profiles/custom/os2loop/translations/translations.da.po)
```

Open `web/profiles/custom/os2loop/translations/translations.da.po` with the
latest version of [Poedit](https://poedit.net/) to clean up and then save the
file.

See
<https://medium.com/limoengroen/how-to-deploy-drupal-interface-translations-5653294c4af6>
for further details.

## Coding standards

```sh
docker compose exec phpfpm vendor/bin/phpcs
docker compose exec phpfpm vendor/bin/phpcbf
```

```sh
docker compose run --rm node yarn install
docker compose run --rm node yarn coding-standards-apply
docker compose run --rm node yarn coding-standards-check
```

### GitHub Actions

We use [GitHub Actions](https://github.com/features/actions) to check coding
standards whenever a pull request is made.

Before making a pull request you can run the GitHub Actions locally to check for
any problems:

[Install `act`](https://github.com/nektos/act#installation) and run

```sh
act -P ubuntu-latest=shivammathur/node:focal pull_request
```

(cf. <https://github.com/shivammathur/setup-php#local-testing-setup>).

## Build theme assets

See
[os2loop_theme/README.md](web/profiles/custom/os2loop/themes/os2loop_theme/README.md).
