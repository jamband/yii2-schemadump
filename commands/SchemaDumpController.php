<?php

/*
 * This file is part of yii2-schemadump.
 *
 * (c) Tomoki Morita <tmsongbooks215@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace jamband\commands;

use Yii;
use yii\console\Exception;
use yii\console\Controller;
use yii\db\Connection;

/**
 * Generate the migration code from database schema.
 */
class SchemaDumpController extends Controller
{
    /**
     * @inheritdoc
     */
    public $defaultAction = 'create';

    /**
     * @var string a migration table name
     */
    public $migrationTable = 'migration';

    /**
     * @var Connection|string the DB connection object or the application component ID of the DB connection.
     */
    public $db = 'db';

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return array_merge(
            parent::options($actionID),
            ['migrationTable', 'db']
        );
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            if (is_string($this->db)) {
                $this->db = Yii::$app->get($this->db);
            }
            if (!$this->db instanceof Connection) {
                throw new Exception("The 'db' option must refer to the application component ID of a DB connection.");
            }
            return true;
        }
        return false;
    }

    /**
     * Generates the 'createTable' code.
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema name.
     * @return integer the status of the action execution
     */
    public function actionCreate($schema = '')
    {
        $stdout = '';
        $tables = $this->db->schema->getTableSchemas($schema);

        foreach ($tables as $table) {
            if ($table->name === $this->migrationTable) {
                continue;
            }

            $stdout .= "// $table->name\n";
            $stdout .= "\$this->createTable('{{%$table->name}}', [\n";

            foreach ($table->columns as $column) {
                $stdout .= "    '$column->name' => {$this->getSchemaType($column)} . \"{$this->otherDefinition($column)}\",\n";
            }

            $stdout .= "], \$this->tableOptions);\n\n";
        }

        foreach ($tables as $table) {
            $stdout .= $this->generateForeignKey($table);
        }

        echo strtr($stdout, [' . ""' => '']);
    }

    /**
     * Generates the 'dropTable' code.
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema name.
     * @return integer the status of the action execution
     */
    public function actionDrop($schema = '')
    {
        $stdout = '';
        $tables = $this->db->schema->getTableSchemas($schema);

        foreach ($tables as $table) {
            if ($table->name === $this->migrationTable) {
                continue;
            }

            $stdout .= "\$this->dropTable('{{%$table->name}}');";

            if (!empty($table->foreignKeys)) {
                $stdout .= " // fk: ";

                foreach ($table->foreignKeys as $fk) {
                    foreach ($fk as $k => $v) {
                        if ($k === 0) {
                            continue;
                        }
                        $stdout .= "$k, ";
                    }
                }

                $stdout = substr($stdout, 0, -2);
            }

            $stdout .= "\n";
        }

        echo $stdout;
    }

    /**
     * Returns the schema type.
     * @param ColumnSchema[] $column
     * @return string the schema type
     */
    private function getSchemaType($column)
    {
        // type: pk
        if ($column->isPrimaryKey) {
            if ($column->type === 'bigint') {
                return 'Schema::TYPE_BIGPK';
            }
            return 'Schema::TYPE_PK';
        }

        // type: other
        if ($column->dbType === 'tinyint(1)') {
            return 'Schema::TYPE_BOOLEAN';
        }
        switch ($column->type) {
            case 'string':
                return 'Schema::TYPE_STRING';
            case 'text':
                return 'Schema::TYPE_TEXT';
            case 'smallint':
                return 'Schema::TYPE_SMALLINT';
            case 'integer':
                return 'Schema::TYPE_INTEGER';
            case 'bigint':
                return 'Schema::TYPE_BIGINT';
            case 'float':
                return 'Schema::TYPE_FLOAT';
            case 'decimal':
                return 'Schema::TYPE_DECIMAL';
            case 'datetime':
                return 'Schema::TYPE_DATETIME';
            case 'timestamp':
                return 'Schema::TYPE_TIMESTAMP';
            case 'time':
                return 'Schema::TYPE_TIME';
            case 'date':
                return 'Schema::TYPE_DATE';
            case 'binary':
                return 'Schema::TYPE_BINARY';
            case 'boolean':
                return 'Schema::TYPE_BOOLEAN';
            case 'money':
                return 'Schema::TYPE_MONEY';
        }
    }

    /**
     * Returns the other definition.
     * @param ColumnSchema[] $column
     * @return string the other definition
     */
    private function otherDefinition($column)
    {
        $definition = '';

        if ($column->size !== null && !$column->isPrimaryKey && $column->dbType !== 'tinyint(1)') {
            $definition .= "($column->size)";
        }
        if ($column->unsigned) {
            $definition .= ' UNSIGNED';
        }
        if (!$column->allowNull && !$column->isPrimaryKey) {
            $definition .= ' NOT NULL';
        }
        if ($column->defaultValue !== null) {
            $definition .= " DEFAULT '$column->defaultValue'";
        }
        if ($column->comment !== '') {
            $definition .= " COMMENT '$column->comment'";
        }

        return $definition;
    }

    /**
     * Generates foreign key definition.
     * @param TableSchema[] $table
     * @return string foreign key definition
     */
    private function generateForeignKey($table)
    {
        $stdout = '';
        $foreignKeys = $table->foreignKeys;

        if (empty($foreignKeys)) {
            return $stdout;
        }

        $stdout = "// fk: $table->name\n";

        foreach ($foreignKeys as $fk) {
            $refTable = '';
            $refColumns = '';
            $columns = '';

            foreach ($fk as $k => $v) {
                if ($k === 0) {
                    $refTable = $v;
                } else {
                    $columns = $k;
                    $refColumns = $v;
                }
            }

            $stdout .= "\$this->addForeignKey('fk_{$table->name}_{$columns}', '{{%$table->name}}', '$columns', '{{%$refTable}}', '$refColumns');\n";
        }

        return "$stdout\n";
    }
}
