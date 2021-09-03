# yii2-schemadump

[![Build Status](https://github.com/jamband/yii2-schemadump/workflows/tests/badge.svg)](https://github.com/jamband/yii2-schemadump/actions?workflow=tests) [![Latest Stable Version](https://img.shields.io/packagist/v/jamband/yii2-schemadump)](https://packagist.org/packages/jamband/yii2-schemadump) [![Total Downloads](https://img.shields.io/packagist/dt/jamband/yii2-schemadump)](https://packagist.org/packages/jamband/yii2-schemadump)

Generate the schema from an existing database.

## Demo

![gif](https://raw.githubusercontent.com/jamband/jamband.github.io/main/images/yii2-schemadump.gif)

## Requirements

- PHP 7.3 or later
- Yii 2.x

## Installation

```
composer require --dev jamband/yii2-schemadump
```

## Usage

Add the following in config/console.php:

```php
return [
    ...
    'components' => [
        ...
    ],
    'controllerMap' => [
        'migrate' => [
            'class' => yii\console\controllers\MigrateController::class,
            'templateFile' => '@jamband/schemadump/template.php',
        ],
        'schemadump' => [
            'class' => jamband\schemadump\SchemaDumpController::class,
            'db' => [
                'class' => yii\db\Connection::class,
                'dsn' => 'mysql:host=localhost;dbname=existing_database_name',
                'username' => 'your_username',
                'password' => 'your_password',
            ],
        ],
    ],
    ...
];
```

And run `schemadump` command.

```
cd /path/to/project
./yii schemadump
```

Example output:

```php
// user
$this->createTable('{{%user}}', [
    'id' => $this->primaryKey()->comment('主キー'),
    'username' => $this->string(20)->notNull()->unique()->comment('ユーザ名'),
    'email' => $this->string(255)->notNull()->unique()->comemnt('メールアドレス'),
    'password' => $this->string(255)->notNull()->comment('パスワード'),
], $this->tableOptions);
```

Copy the output code and paste it into a migration file.

## Commands

Generates the 'createTable' code. (default)

```
./yii schemadump
./yii schemadump/create
```

Generates the 'dropTable' code.

```
./yii schemadump/drop
```

Useful commands (for macOS user):

```
./yii schemadump | pbcopy
./yii schemadump/drop | pbcopy
```

Check help.

```
./yii help schemadump
```

## Supports

- Types
- Size
- Unsigned
- NOT NULL
- DEFAULT value
- COMMENT
- Unique key
- Foreign key
- Composite primary keys
- Primary key without AUTO_INCREMENT
- ENUM type (for MySQL)
