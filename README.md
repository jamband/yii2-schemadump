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
