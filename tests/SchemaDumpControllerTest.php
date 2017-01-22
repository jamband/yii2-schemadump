<?php

/*
 * This file is part of yii2-schemadump.
 *
 * (c) Tomoki Morita <tmsongbooks215@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tests;

use Yii;
use yii\db\Connection;
use jamband\schemadump\SchemaDumpController;

class SchemaDumpControllerText extends \PHPUnit_Framework_TestCase
{
    private $controller;

    public function setUp()
    {
        $this->controller = new BufferedSchemaDumpController('schemadump', Yii::$app);
    }

    public static function setUpBeforeClass()
    {
        Yii::$app->set('db', [
            'class' => Connection::class,
            'dsn' => 'mysql:host=localhost;dbname=yii2_schemadump_test',
            'username' => 'root',
            'password' => getenv('DB_PASS'),
        ]);

        Yii::$app->db->open();

        $statements = array_filter(explode(';', file_get_contents(__DIR__.'/mysql.sql')), 'trim');
        foreach ($statements as $statement) {
            Yii::$app->db->pdo->exec($statement);
        }
    }

    public static function tearDownAfterClass()
    {
        $db = Yii::$app->db;
        foreach ($db->schema->getTableNames() as $table) {
            $db->createCommand()->checkIntegrity(false)->execute();
            $db->createCommand("DROP TABLE `$table`")->execute();
        }
    }

    public function testActionCreate()
    {
        $this->controller->run('create');
        $this->assertSame(<<<'STDOUT'
// 0010_pk_ai
$this->createTable('{{%0010_pk_ai}}', [
    'id' => Schema::TYPE_PK,
], $this->tableOptions);

// 0020_pk_not_ai
$this->createTable('{{%0020_pk_not_ai}}', [
    'id' => Schema::TYPE_INTEGER."(11) NOT NULL",
    'PRIMARY KEY (id)',
], $this->tableOptions);

// 0030_pk_bigint_ai
$this->createTable('{{%0030_pk_bigint_ai}}', [
    'id' => Schema::TYPE_BIGPK,
], $this->tableOptions);

// 0040_pk_unsigned_ai
$this->createTable('{{%0040_pk_unsigned_ai}}', [
    'id' => Schema::TYPE_INTEGER."(10) UNSIGNED NOT NULL AUTO_INCREMENT",
    'PRIMARY KEY (id)',
], $this->tableOptions);

// 0050_pk_bigint_unsigned_ai
$this->createTable('{{%0050_pk_bigint_unsigned_ai}}', [
    'id' => Schema::TYPE_BIGINT."(20) UNSIGNED NOT NULL AUTO_INCREMENT",
    'PRIMARY KEY (id)',
], $this->tableOptions);

// 0060_composite_pks
$this->createTable('{{%0060_composite_pks}}', [
    'foo_id' => Schema::TYPE_INTEGER."(11) NOT NULL",
    'bar_id' => Schema::TYPE_INTEGER."(11) NOT NULL",
    'PRIMARY KEY (foo_id, bar_id)',
], $this->tableOptions);

// 0100_types
$this->createTable('{{%0100_types}}', [
    'char' => Schema::TYPE_CHAR."(20) NOT NULL",
    'varchar' => Schema::TYPE_STRING."(20) NOT NULL",
    'text' => Schema::TYPE_TEXT." NOT NULL",
    'smallint' => Schema::TYPE_SMALLINT."(6) NOT NULL",
    'integer' => Schema::TYPE_INTEGER."(11) NOT NULL",
    'bigint' => Schema::TYPE_BIGINT."(20) NOT NULL",
    'float' => Schema::TYPE_FLOAT." NOT NULL",
    'float_decimal' => Schema::TYPE_FLOAT."(20,10) NOT NULL",
    'double' => Schema::TYPE_DOUBLE."(20,10) NOT NULL",
    'decimal' => Schema::TYPE_DECIMAL."(20,10) NOT NULL",
    'money' => Schema::TYPE_DECIMAL."(19,4) NOT NULL",
    'datetime' => Schema::TYPE_DATETIME." NOT NULL",
    'timestamp' => Schema::TYPE_TIMESTAMP." NOT NULL DEFAULT CURRENT_TIMESTAMP",
    'time' => Schema::TYPE_TIME." NOT NULL",
    'date' => Schema::TYPE_DATE." NOT NULL",
    'binary' => Schema::TYPE_BINARY." NOT NULL",
    'boolean' => Schema::TYPE_BOOLEAN." NOT NULL DEFAULT '0'",
    'tinyint_1' => Schema::TYPE_BOOLEAN." NOT NULL DEFAULT '0'",
], $this->tableOptions);

// 0200_default_values
$this->createTable('{{%0200_default_values}}', [
    'string' => Schema::TYPE_STRING."(255) NOT NULL DEFAULT 'UNKNOWN'",
    'special_characters' => Schema::TYPE_STRING."(255) NOT NULL DEFAULT '\'\"'",
], $this->tableOptions);

// 0300_comment
$this->createTable('{{%0300_comment}}', [
    'username' => Schema::TYPE_STRING."(20) NOT NULL COMMENT 'ユーザ名'",
    'special_characters' => Schema::TYPE_STRING."(20) NOT NULL COMMENT '\'\"'",
], $this->tableOptions);

// 0400_fk_parent
$this->createTable('{{%0400_fk_parent}}', [
    'id' => Schema::TYPE_PK,
], $this->tableOptions);

// 0410_fk_child
$this->createTable('{{%0410_fk_child}}', [
    'id' => Schema::TYPE_PK,
    'parent_id' => Schema::TYPE_INTEGER."(11) NOT NULL",
], $this->tableOptions);

// fk: 0410_fk_child
$this->addForeignKey('fk_0410_fk_child_parent_id', '{{%0410_fk_child}}', 'parent_id', '{{%0400_fk_parent}}', 'id');


STDOUT
        , $this->controller->flushStdOutBuffer());
    }

    public function testDropAction()
    {
        $this->controller->run('drop');
        $this->assertSame(<<<'STDOUT'
$this->dropTable('{{%0010_pk_ai}}');
$this->dropTable('{{%0020_pk_not_ai}}');
$this->dropTable('{{%0030_pk_bigint_ai}}');
$this->dropTable('{{%0040_pk_unsigned_ai}}');
$this->dropTable('{{%0050_pk_bigint_unsigned_ai}}');
$this->dropTable('{{%0060_composite_pks}}');
$this->dropTable('{{%0100_types}}');
$this->dropTable('{{%0200_default_values}}');
$this->dropTable('{{%0300_comment}}');
$this->dropTable('{{%0400_fk_parent}}');
$this->dropTable('{{%0410_fk_child}}'); // fk: parent_id

STDOUT
        , $this->controller->flushStdOutBuffer());
    }
}

class BufferedSchemaDumpController extends SchemaDumpController
{
    private $stdOutBuffer = '';

    /**
     * @param string $string
     */
    public function stdout($string)
    {
        $this->stdOutBuffer .= $string;
    }

    /**
     * @return string
     */
    public function flushStdOutBuffer()
    {
        $result = $this->stdOutBuffer;
        $this->stdOutBuffer = '';
        return $result;
    }
}
