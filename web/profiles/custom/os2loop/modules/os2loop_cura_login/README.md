# Cura login

Use `https://os2loop.example.com/os2loop-cura-login/start` as `linkURL` (or is it `formPostUrl`?) in the Cura link
configuration.

## How does it work?

Cura will make a `POST` `multipart/form-data` request to `/os2loop-cura-login/start` with a `payload` parameter containing
a JWT like

``` json
{
  "header" : {
    "alg" : "HS256"
  },
  "payload" : {
    "brugerId" : "az…",
    "organisationsNavn" : "Digitalisering",
    "brugerensNavn" : "…",
    "sorid" : "aarhus",
    "exp" : 1756978913
  },
  "signature" : "…"
}
```

However, it can also make a `GET` request to `/os2loop-cura-login/start/{payload}` or
`/os2loop-cura-login/start/payload={payload}`.

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

Run

``` shell
drush config:get os2loop_cura_login.settings --include-overridden
```

to show the current module config.
