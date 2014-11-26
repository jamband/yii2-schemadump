# yii2-schemadump

This is a command to generate the schema from an existing database with Yii 2 Framework.

## Demo

Under development ...

## Installation

```
php composer.phar require --dev --prefer-dist jamband/yii2-schemadump "*"
```

or add in composer.json (require-dev section)
```
"jamband/yii2-schemadump": "*"
```

## Usage

Add the following in config/console.php:

```php
<?php
return [
    ...
    'components' => [
        ...
    ],
    'controllerMap' => [
        'migrate' => [
            'class' => 'yii\console\controllers\MigrateController',
            'templateFile' => '@vendor/jamband/yii2-schemadump/migrations/template.php',
        ],
        'schemadump' => [
            'class' => 'jamband\commands\SchemaDumpController',
        ],
    ],
    ...
];
```

And run `schemadump` command.

```
cd /path/to/project
./yii schemadump <existing_database_name>
```

Example output:

```
// user
$this->createTable('{{%user}}', [
    'id' => Schema::TYPE_PK . " COMMENT '主キー'",
    'username' => Schema::TYPE_STRING . "(255) NOT NULL COMMENT 'ユーザ名'",
    'email' => Schema::TYPE_STRING . "(255) NOT NULL COMMENT 'メールアドレス'",
    'password' => Schema::TYPE_STRING . "(255) NOT NULL COMMENT 'パスワード'",
], $this->tableOptions);
```

Copy the output code and paste it into a file.

## Commands

Generates the 'createTable' code. (default)

```
./yii schemadump <existing_database_name>
./yii schemadump/create <existing_database_name>
```

Generates the 'dropTable' code.

```
./yii schemadump/drop <existing_database_name>
```

Useful commands (for OS X user):

```
./yii schemadump <existing_database_name> | pbcopy
./yii schemadump/drop <existing_database_name> | pbcopy
```

Check help.

```
./yii help schemadump
```
