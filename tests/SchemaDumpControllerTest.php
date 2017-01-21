<?php

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
            $db->createCommand("DROP TABLE `$table`")->execute();
        }
    }

    public function testActionCreate()
    {
        $this->controller->run('create');
        $this->assertSame(<<<'STDOUT'
// post
$this->createTable('{{%post}}', [
    'id' => Schema::TYPE_PK,
    'user_id' => Schema::TYPE_INTEGER."(11) NOT NULL",
    'title' => Schema::TYPE_STRING."(255) NOT NULL",
    'body' => Schema::TYPE_TEXT." NOT NULL",
    'created_at' => Schema::TYPE_INTEGER."(11) NOT NULL",
    'updated_at' => Schema::TYPE_INTEGER."(11) NOT NULL",
], $this->tableOptions);

// user
$this->createTable('{{%user}}', [
    'id' => Schema::TYPE_PK." COMMENT '主キー'",
    'username' => Schema::TYPE_STRING."(255) NOT NULL COMMENT 'ユーザ名'",
    'password' => Schema::TYPE_STRING."(255) NOT NULL COMMENT 'パスワード'",
], $this->tableOptions);


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
