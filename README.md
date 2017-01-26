# yii2-schemadump

[![Latest Stable Version](https://poser.pugx.org/jamband/yii2-schemadump/v/stable.svg)](https://packagist.org/packages/jamband/yii2-schemadump) [![Total Downloads](https://poser.pugx.org/jamband/yii2-schemadump/downloads.svg)](https://packagist.org/packages/jamband/yii2-schemadump) [![Latest Unstable Version](https://poser.pugx.org/jamband/yii2-schemadump/v/unstable.svg)](https://packagist.org/packages/jamband/yii2-schemadump) [![License](https://poser.pugx.org/jamband/yii2-schemadump/license.svg)](https://packagist.org/packages/jamband/yii2-schemadump) [![Build Status](https://travis-ci.org/jamband/yii2-schemadump.svg?branch=master)](https://travis-ci.org/jamband/yii2-schemadump)

Generate the schema from an existing database.

## Demo

![gif](https://raw.githubusercontent.com/jamband/jamband.github.io/master/images/yii2-schemadump.gif)

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
