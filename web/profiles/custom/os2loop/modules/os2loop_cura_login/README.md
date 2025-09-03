# Cura login

Use `https://os2loop.example.com/os2loop-cura-login/start` as `linkURL` (or is it `formPostUrl`?) in the Cura link
configuration.

## Example

``` shell
curl "http://$(docker compose port nginx 8080)/os2loop-cura-login/start"
```


``` shell
drush os2loop-cura-login:get-login-url --help
```

``` shell
drush --uri='http://nginx:8080' os2loop-cura-login:get-login-url test@example.com --secret=$(drush config:get --format string os2loop_cura_login.settings signing_secret --include-overridden) --algorithm=$(drush config:get --format string os2loop_cura_login.settings signing_algorithm --include-overridden)
```


## Development and debugging

``` php
# settings.local.php
$config['os2loop_cura_login.settings']['log_level'] = \Drupal\Core\Logger\RfcLogLevel::DEBUG;
```
