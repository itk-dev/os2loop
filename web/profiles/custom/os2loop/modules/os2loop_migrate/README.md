# OS2Loop Migrate

Migrates content from sites built on <https://github.com/os2loop/profile>.

## Getting ready

### Settings up the source

Define the source database with key `migrate` (cf.
<https://www.drupal.org/docs/upgrading-drupal/upgrade-using-drush#s-define-the-source-database>):

```php
$databases['migrate']['default'] = [
  'database' => 'db',
  'username' => 'db',
  'password' => 'db',
  'prefix' => '',
  'host' => '0.0.0.0',
  'port' => '55008',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
];
```

Check that you can connect to your `migrate` database.

```sh
vendor/bin/drush sql:query --database=migrate 'SHOW TABLES'
```

Clean up source database:

```sh
# Delete non-existing comments with upvotes.
vendor/bin/drush sql:query --database=migrate "DELETE FROM flagging WHERE fid = 1 AND entity_id NOT IN (SELECT cid FROM comment)"
# Delete flagging of non-existing messages;
vendor/bin/drush sql:query --database=migrate "DELETE FROM flagging WHERE fid = 2 AND entity_id NOT IN (SELECT mid FROM message)"
# Delete subscriptions on non-existing nodes.
vendor/bin/drush sql:query --database=migrate "DELETE FROM flagging WHERE fid = 3 AND entity_id NOT IN (SELECT nid FROM node)"
```

### Enabling migration modules and configuration

Install the OS2Loop Migrate module:

```sh
vendor/bin/drush --yes pm:enable os2loop_migrate
```

<!--
Import the migration configuration:

```sh
vendor/bin/drush --yes pm:enable config
vendor/bin/drush --yes config:import --partial --source=profiles/custom/os2loop/modules/os2loop_migrate/config/install
vendor/bin/drush --yes pm:uninstall config
```
-->

## Clean up after migration

After completing the migration, you should uninstall the modules used for the
migration (this will also remove the migrations configuration):

```sh
vendor/bin/drush --yes pm:uninstall migrate
```

## Reindex content

```sh
vendor/bin/drush cache:rebuild
vendor/bin/drush search-api:status os2loop_search_db_index
vendor/bin/drush search-api:reset-tracker os2loop_search_db_index
vendor/bin/drush search-api:index os2loop_search_db_index
```

Finally, i.e. when you're completely done with the migration, you can remove the
migrations tables (prefixed with `migrate_`) from the database:

```sh
for t in $(vendor/bin/drush sql:query "SHOW TABLES LIKE 'migrate\_%'"); do vendor/bin/drush sql:query "DROP TABLE $t"; done
```

## Migrating content

Migrate all Loop content:

```sh
vendor/bin/drush migrate:import --tag=os2loop
```

The actual migration of content should be performed in the following order.

### 1. Files

```sh
mkdir -p migrate/sites/default
rsync --archive --compress --delete «old site root»/sites/default/files migrate/sites/default
vendor/bin/drush migrate:import upgrade_d7_file
```

### 2. Taxonomies

```sh
vendor/bin/drush migrate:import upgrade_d7_taxonomy_term_subject
vendor/bin/drush migrate:import upgrade_d7_taxonomy_term_keyword
vendor/bin/drush migrate:import upgrade_d7_taxonomy_term_profession
```

### 3. Users

```sh
vendor/bin/drush migrate:import upgrade_d7_user
```

### 4. Nodes

```sh
vendor/bin/drush migrate:import upgrade_d7_node_complete_page
vendor/bin/drush migrate:import upgrade_d7_node_complete_external_sources
vendor/bin/drush migrate:import upgrade_d7_node_complete_post
vendor/bin/drush migrate:import upgrade_d7_node_complete_loop_documents_document
vendor/bin/drush migrate:import upgrade_d7_node_complete_loop_documents_collection
# Custom drush command to migrate documents in collections.
vendor/bin/drush os2loop:migrate:collection-documents
```

#### Empty question titles

Migrated questions use (part of) their body as title. To set empty titles, run
the following commands:

```sql
vendor/bin/drush sql:query "UPDATE node_field_data AS node JOIN node_field_revision AS revision ON node.nid = revision.nid and node.vid = revision.vid SET node.title = '', revision.title = '' WHERE node.type = 'os2loop_question'"
```

### 5. Comments

```sh
vendor/bin/drush migrate:import upgrade_d7_comment
```

### 6. Flagging

```sh
vendor/bin/drush migrate:import upgrade_d7_flagging
```

<!--
### n. Messages

```sh
# Text formats are defined in config.
# vendor/bin/drush migrate:import upgrade_d7_filter_format
# Message templates are defined in config.
# vendor/bin/drush migrate:import upgrade_d7_message_template
vendor/bin/drush migrate:import upgrade_d7_message
```

```sh
mysqldump --user=db --password=db --host=127.0.0.1 --port=58847 \
  --no-create-info --complete-insert db flagging
```
-->

## Tips and tricks

If a migration gets stuck:

```sh
vendor/bin/drush migrate:reset-status
```

Reverting a migration:

```sh
vendor/bin/drush migrate:rollback
```

Migration status:

```sh
vendor/bin/drush migrate:status --tag="Drupal 7"
```

Migration messages:

```sh
vendor/bin/drush migrate:messages
```

<!--
```sh
composer require --dev drupal/migrate_tools drupal/migrate_upgrade
vendor/bin/drush pm:enable migrate_upgrade
vendor/bin/drush migrate:upgrade --configure-only --legacy-db-key=migrate
vendor/bin/drush migrate:status --tag="Drupal 7"

# Migrate users
vendor/bin/drush migrate:import --execute-dependencies upgrade_d7_user
# Migrate content
vendor/bin/drush migrate:import --execute-dependencies upgrade_d7_node_complete:external_sources
```

<https://www.drupal.org/docs/upgrading-drupal/upgrade-using-drush>

<https://www.drupal.org/docs/upgrading-drupal/customize-migrations-when-upgrading-to-drupal-8-or-later>

<https://drupal.stackexchange.com/questions/278097/can-i-safely-delete-the-migrate-tables-after-migration?rq=1>
<https://www.drupal.org/project/drupal/issues/2713327>

"Altering migrations"
(<https://www.lullabot.com/articles/overview-migrating-drupal-sites-8>)

Flags:

<https://deninet.com/tag/building-custom-migration-drupal-8>
<https://www.drupal.org/project/migrate_extras/issues/1794568>
<https://www.drupal.org/node/2503815>
<https://www.drupal.org/project/flag/issues/2409901>
-->
