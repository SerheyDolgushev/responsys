Responsys
=========

General description
-------------------

eZ Publish wrapper for Responsys REST API

Installation
------------
1. Download and enable responsys extension
2. Override extension/responsys/settings/responsys.ini configuration file with correct settings
3. Regenerate eZ Publish autoloads and clear eZ Publish ini caches:
```
$ cd EZP-ROOT
$ php bin/php/ezcache.php --clear-tag=ini
$ php bin/php/ezpgenerateautoloads.php
```
4. Run SQL from extension/responsys/sql/mysql/schema.sql