# OS2Loop Mail notifications

Sends mail notifications to users when content the subscribe to has been created
or edited.

A cron task is run daily to send out notifications.

## Force run

```php
vendor/bin/drush php-eval "Drupal::state()->set('os2loop_mail_notifications', ['last_run_at' => '1970-01-01'] + Drupal::state()->get('os2loop_mail_notifications', []))"
vendor/bin/drush php-eval "var_export(Drupal::state()->get('os2loop_mail_notifications'))"
vendor/bin/drush php-eval "Drupal::service('os2loop_mail_notifications.helper')->cron()"
```
