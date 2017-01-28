<?php

/*
 * This file is part of yii2-schemadump.
 *
 * (c) Tomoki Morita <tmsongbooks215@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace jamband\schemadump\tests;

use Yii;
use yii\db\Connection;
use jamband\schemadump\SchemaDumpController;

class SchemaDumpControllerMySQLTest extends \PHPUnit_Framework_TestCase
{
    private $controller;

    public function setUp()
    {
        $this->controller = new BufferedSchemaDumpMySQLController('schemadump', Yii::$app);
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

        $statements = array_filter(explode(';', file_get_contents(__DIR__.'/schemas/mysql.sql')), 'trim');
        foreach ($statements as $statement) {
            Yii::$app->db->pdo->exec($statement);
        }
    }

    public static function tearDownAfterClass()
    {
        $db = Yii::$app->db;
        $db->createCommand()->checkIntegrity(false)->execute();

        foreach ($db->schema->getTableNames() as $table) {
            $db->createCommand("DROP TABLE `$table`")->execute();
        }
    }

    public function testActionCreate()
    {
        $this->controller->run('create');
        $this->assertSame(<<<'STDOUT'
// 0010_pk_ai
$this->createTable('{{%0010_pk_ai}}', [
    'id' => $this->primaryKey(),
], $this->tableOptions);

// 0020_pk_not_ai
$this->createTable('{{%0020_pk_not_ai}}', [
    'id' => $this->integer(11)->notNull(),
    'PRIMARY KEY (id)',
], $this->tableOptions);

// 0030_pk_bigint_ai
$this->createTable('{{%0030_pk_bigint_ai}}', [
    'id' => $this->bigPrimaryKey(),
], $this->tableOptions);

// 0040_pk_unsigned_ai
$this->createTable('{{%0040_pk_unsigned_ai}}', [
    'id' => $this->primaryKey()->unsigned(),
], $this->tableOptions);

// 0050_pk_bigint_unsigned_ai
$this->createTable('{{%0050_pk_bigint_unsigned_ai}}', [
    'id' => $this->bigPrimaryKey()->unsigned(),
], $this->tableOptions);

// 0060_composite_pks
$this->createTable('{{%0060_composite_pks}}', [
    'foo_id' => $this->integer(11)->notNull(),
    'bar_id' => $this->integer(11)->notNull(),
    'PRIMARY KEY (foo_id, bar_id)',
], $this->tableOptions);

// 0070_uks
$this->createTable('{{%0070_uks}}', [
    'id' => $this->primaryKey(),
    'username' => $this->string(20)->notNull()->unique(),
    'email' => $this->string(255)->notNull()->unique(),
    'password' => $this->string(255)->notNull(),
], $this->tableOptions);

// 0100_types
$this->createTable('{{%0100_types}}', [
    'char' => $this->char(20)->notNull(),
    'varchar' => $this->string(20)->notNull(),
    'text' => $this->text()->notNull(),
    'smallint' => $this->smallInteger(6)->notNull(),
    'integer' => $this->integer(11)->notNull(),
    'bigint' => $this->bigInteger(20)->notNull(),
    'float' => $this->float()->notNull(),
    'float_decimal' => $this->float(20,10)->notNull(),
    'double' => $this->double(20,10)->notNull(),
    'decimal' => $this->decimal(20,10)->notNull(),
    'money' => $this->decimal(19,4)->notNull(),
    'datetime' => $this->datetime()->notNull(),
    'timestamp' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
    'time' => $this->time()->notNull(),
    'date' => $this->date()->notNull(),
    'binary' => $this->binary()->notNull(),
    'boolean' => $this->boolean()->notNull()->defaultValue(0),
    'tinyint_1' => $this->boolean()->notNull()->defaultValue(0),
    'enum' => "ENUM ('foo', 'bar', 'baz') NOT NULL",
], $this->tableOptions);

// 0200_default_values
$this->createTable('{{%0200_default_values}}', [
    'integer' => $this->smallInteger(6)->notNull()->defaultValue(1),
    'string' => $this->string(255)->notNull()->defaultValue('UNKNOWN'),
    'enum' => "ENUM ('foo', 'bar', 'baz') DEFAULT NULL",
    'enum_foo' => "ENUM ('foo', 'bar', 'baz') NOT NULL DEFAULT 'foo'",
], $this->tableOptions);

// 0300_comment
$this->createTable('{{%0300_comment}}', [
    'username' => $this->string(20)->notNull()->comment('ユーザ名'),
    'enum' => "ENUM ('foo', 'bar', 'baz') NOT NULL COMMENT 'foo'",
], $this->tableOptions);

// 0400_fk_parent
$this->createTable('{{%0400_fk_parent}}', [
    'id' => $this->primaryKey(),
], $this->tableOptions);

// 0410_fk_child
$this->createTable('{{%0410_fk_child}}', [
    'id' => $this->primaryKey(),
    'parent_id' => $this->integer(11)->notNull(),
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
$this->dropTable('{{%0070_uks}}');
$this->dropTable('{{%0100_types}}');
$this->dropTable('{{%0200_default_values}}');
$this->dropTable('{{%0300_comment}}');
$this->dropTable('{{%0400_fk_parent}}');
$this->dropTable('{{%0410_fk_child}}'); // fk: parent_id

STDOUT
        , $this->controller->flushStdOutBuffer());
    }
}

class BufferedSchemaDumpMySQLController extends SchemaDumpController
{
    use StdOutBufferControllerTrait;
}
