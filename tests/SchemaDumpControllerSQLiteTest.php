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

class SchemaDumpControllerSQLiteTest extends \PHPUnit_Framework_TestCase
{
    private $controller;

    public function setUp()
    {
        $this->controller = new BufferedSchemaDumpSQLiteController('schemadump', Yii::$app);
    }

    public static function setUpBeforeClass()
    {
        Yii::$app->set('db', [
            'class' => Connection::class,
            'dsn' => 'sqlite::memory:',
        ]);

        Yii::$app->db->open();

        $statements = array_filter(explode(';', file_get_contents(__DIR__.'/schemas/sqlite.sql')), 'trim');
        foreach ($statements as $statement) {
            Yii::$app->db->pdo->exec($statement);
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

// 0020_composite_pks
$this->createTable('{{%0020_composite_pks}}', [
    'foo_id' => $this->integer()->notNull(),
    'bar_id' => $this->integer()->notNull(),
    'PRIMARY KEY (foo_id, bar_id)',
], $this->tableOptions);

// 0030_uks
$this->createTable('{{%0030_uks}}', [
    'id' => $this->primaryKey(),
    'username' => $this->text()->notNull()->unique(),
    'email' => $this->text()->notNull()->unique(),
    'password' => $this->text()->notNull(),
], $this->tableOptions);

// 0100_types
$this->createTable('{{%0100_types}}', [
    'integer' => $this->integer()->notNull(),
    'real' => $this->float()->notNull(),
    'text' => $this->text()->notNull(),
    'blob' => $this->binary()->notNull(),
], $this->tableOptions);

// 0200_default_values
$this->createTable('{{%0200_default_values}}', [
    'integer' => $this->integer()->notNull()->defaultValue(1),
    'string' => $this->text()->notNull()->defaultValue('UNKNOWN'),
], $this->tableOptions);

// 0300_fk_parent
$this->createTable('{{%0300_fk_parent}}', [
    'id' => $this->primaryKey(),
], $this->tableOptions);

// 0310_fk_child
$this->createTable('{{%0310_fk_child}}', [
    'id' => $this->primaryKey(),
    'parent_id' => $this->integer()->notNull(),
], $this->tableOptions);

// fk: 0310_fk_child
$this->addForeignKey('fk_0310_fk_child_parent_id', '{{%0310_fk_child}}', 'parent_id', '{{%0300_fk_parent}}', 'id');


STDOUT
        , $this->controller->flushStdOutBuffer());
    }

    public function testDropAction()
    {
        $this->controller->run('drop');
        $this->assertSame(<<<'STDOUT'
$this->dropTable('{{%0010_pk_ai}}');
$this->dropTable('{{%0020_composite_pks}}');
$this->dropTable('{{%0030_uks}}');
$this->dropTable('{{%0100_types}}');
$this->dropTable('{{%0200_default_values}}');
$this->dropTable('{{%0300_fk_parent}}');
$this->dropTable('{{%0310_fk_child}}'); // fk: parent_id

STDOUT
        , $this->controller->flushStdOutBuffer());
    }
}

class BufferedSchemaDumpSQLiteController extends SchemaDumpController
{
    use StdOutBufferControllerTrait;
}
