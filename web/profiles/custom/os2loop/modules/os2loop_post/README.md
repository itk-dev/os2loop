# OS2Loop Post

## Fixtures

```sh
vendor/bin/drush --yes pm:enable os2loop_post_fixtures
vendor/bin/drush --yes content-fixtures:load \
  --groups=os2loop_post,os2loop_file,os2loop_taxonomy
vendor/bin/drush --yes pm:uninstall content_fixtures
```

## Automated UI tests

```sh
TEST_SESSION_ENABLED=true vendor/bin/drush serve &
vendor/bin/drush --yes pm:enable os2loop_post_tests_cypress
CYPRESS_DRUPAL_DRUSH=$PWD/vendor/bin/drush $PWD/vendor/bin/drush cypress:run os2loop_post_tests_cypress
```

Use `…/drush cypress:open` to run tests interactively, i.e.

```sh
CYPRESS_DRUPAL_DRUSH=$PWD/vendor/bin/drush $PWD/vendor/bin/drush cypress:open
```

Screenshots of any failing test runs can be found in
`web/drupal-cypress-environment/cypress/screenshots`.
