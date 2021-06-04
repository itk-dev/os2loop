# OS2Loop user login

Log in via OpenID Connect and SAML.

In the default configuration, both login methods assume that the identitity
provider returns a `name` claim which is used as the Drupal user name and that a
`groups` claim is a list of groups that can be mapped to Drupal roles.

Any changes to the default configuration can be made in `settings.local.php` as
shown in the following sections.

## OpenID Connect

```php
// web/sites/*/settings.local.php
// Enable Windows Azure AD
$config['openid_connect.settings.windows_aad']['enabled'] = 'windows_aad';
$config['openid_connect.settings.windows_aad']['settings']['client_id'] = …; // Get this from your IdP provider
$config['openid_connect.settings.windows_aad']['settings']['client_secret'] = …; // Get this from your IdP provider
$config['openid_connect.settings.windows_aad']['settings']['authorization_endpoint_wa'] = …; // Get this from your OpenID Connect Discovery endpoint
$config['openid_connect.settings.windows_aad']['settings']['token_endpoint_wa'] = …; // Get this from your OpenID Connect Discovery endpoint
$config['openid_connect.settings.windows_aad']['settings']['use_v2'] = true;
// Fake userinfo endpoint to force v2 (cf. https://www.drupal.org/project/openid_connect_windows_aad/issues/3021812).
$config['openid_connect.settings.windows_aad']['settings']['userinfo_endpoint_wa'] = 'use_v2';
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
$config['samlauth.authentication']['user_name_attribute'] = 'name';
$config['samlauth.authentication']['user_mail_attribute'] = 'mail';
$config['samlauth.authentication']['sp_name_id_format'] = 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress';
```
