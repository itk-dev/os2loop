# OS2Loop media

This module sets permission for viewing the media library.

## Fixtures

```sh
vendor/bin/drush --yes pm:enable os2loop_subscriptions_fixtures
vendor/bin/drush --yes content-fixtures:load --groups=os2loop_user,os2loop_subscriptions,os2loop_taxonomy,os2loop_question
vendor/bin/drush --yes pm:uninstall content_fixtures
```
