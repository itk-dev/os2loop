# OS2Loop user login

Log in via OpenID Connect and SAML.

@todo Assumptions on user info returned from IdP: `name` used as Drupal user
name, `groups` with a list of group names mapped to Drupal roles.

## OpenID Connect

```php
// web/sites/*/settings.local.php
// Enable Windows Azure AD
$config['openid_connect.settings.windows_aad']['enabled'] = 'windows_aad';
$config['openid_connect.settings.windows_aad']['settings']['client_id'] = …;
$config['openid_connect.settings.windows_aad']['settings']['client_secret'] = …;
$config['openid_connect.settings.windows_aad']['settings']['authorization_endpoint_wa'] = …; // Get this from your OpenID Connect Discovery endpoint
$config['openid_connect.settings.windows_aad']['settings']['token_endpoint_wa'] = …; // Get this from your OpenID Connect Discovery endpoint
$config['openid_connect.settings.windows_aad']['settings']['use_v2'] = true;
// Fake userinfo endpoint to force v2 (cf. https://www.drupal.org/project/openid_connect_windows_aad/issues/3021812).
$config['openid_connect.settings.windows_aad']['settings']['userinfo_endpoint_wa'] = 'use_v2';
// Use `upn` as Drupal user name.
$config['openid_connect.settings.windows_aad']['settings']['user_name_attribute'] = 'upn';
```

@todo Document default role mapping

## SAML

```php
// web/sites/*/settings.local.php
$config['samlauth.authentication']['sp_entity_id'] = 'os2loop;
$config['samlauth.authentication']['wsp_name_id_format'] = '';
// Folder containing `certs` folder with files `sp.{crt,key}`.
$config['samlauth.authentication']['sp_cert_folder'] = __DIR__.'/../../..';
$config['samlauth.authentication']['idp_entity_id'] = …; // Get this from you IdP metadata.
$config['samlauth.authentication']['idp_single_sign_on_service'] = …; // Get this from you IdP metadata.
$config['samlauth.authentication']['idp_x509_certificate'] = …; // Get this from you IdP metadata.
$config['samlauth.authentication']['unique_id_attribute'] = 'upn';
$config['samlauth.authentication']['create_users'] = TRUE;
$config['samlauth.authentication']['user_name_attribute'] = 'upn';
$config['samlauth.authentication']['user_mail_attribute'] = 'mail';
$config['samlauth.authentication']['sp_name_id_format'] = 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress';
```
