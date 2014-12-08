<?php

/*
 * This file is part of yii2-schemadump.
 *
 * (c) Tomoki Morita <tmsongbooks215@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace jamband\schemadump;

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
     * @var array the column types
     * @see \yii\db\Schema
     */
    private $type = [
        'pk'        => 'Schema::TYPE_PK',
        'bigpk'     => 'Schema::TYPE_BIGPK',
        'string'    => 'Schema::TYPE_STRING',
        'text'      => 'Schema::TYPE_TEXT',
        'smallint'  => 'Schema::TYPE_SMALLINT',
        'integer'   => 'Schema::TYPE_INTEGER',
        'bigint'    => 'Schema::TYPE_BIGINT',
        'float'     => 'Schema::TYPE_FLOAT',
        'decimal'   => 'Schema::TYPE_DECIMAL',
        'datetime'  => 'Schema::TYPE_DATETIME',
        'timestamp' => 'Schema::TYPE_TIMESTAMP',
        'time'      => 'Schema::TYPE_TIME',
        'date'      => 'Schema::TYPE_DATE',
        'binary'    => 'Schema::TYPE_BINARY',
        'boolean'   => 'Schema::TYPE_BOOLEAN',
        'money'     => 'Schema::TYPE_MONEY',
    ];

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

        $offset = 0;
        foreach ($tables as $table) {
            if ($table->name === $this->migrationTable) {
                continue;
            }

            $stdout .= "// $table->name\n";
            $stdout .= "\$this->createTable('{{%$table->name}}', [\n";

            foreach ($table->columns as $column) {
                $stdout .= "    '$column->name' => {$this->getSchemaType($column)} . \"{$this->otherDefinition($column)}\",\n";
            }

            if (!empty($table->primaryKey)) {
                if (count($table->primaryKey) >= 2) {
                    $stdout .= "    'PRIMARY KEY (" . implode(', ', $table->primaryKey) . ")',\n";

                } elseif (false === strpos($stdout, $this->type['pk'], $offset) && false === strpos($stdout, $this->type['bigpk'], $offset)) {
                    $stdout .= "    'PRIMARY KEY ({$table->primaryKey[0]})',\n";
                }
            }

            $stdout .= "], \$this->tableOptions);\n\n";
            $offset = mb_strlen($stdout, Yii::$app->charset);
        }

        foreach ($tables as $table) {
            $stdout .= $this->generateForeignKey($table);
        }

        $this->stdout(strtr($stdout, [
            ' . ""' => '',
            '" . "' => '',
        ]));
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

        $this->stdout($stdout);
    }

    /**
     * Returns the schema type.
     * @param ColumnSchema[] $column
     * @return string the schema type
     */
    private function getSchemaType($column)
    {
        if ($column->autoIncrement && !$column->unsigned) {
            return ($column->type === 'bigint') ? $this->type['bigpk'] : $this->type['pk'];
        }
        if ($column->dbType === 'tinyint(1)') {
            return $this->type['boolean'];
        }
        if ($column->enumValues !== null) {
            return "\"$column->dbType\"";
        }

        return $this->type[$column->type];
    }

    /**
     * Returns the other definition.
     * @param ColumnSchema[] $column
     * @return string the other definition
     */
    private function otherDefinition($column)
    {
        $definition = '';

        if (
            ($column->size !== null && !$column->autoIncrement && $column->dbType !== 'tinyint(1)') ||
            ($column->size !== null && $column->unsigned)
        ) {
            $definition .= "($column->size)";
        }

        if ($column->unsigned) {
            $definition .= ' UNSIGNED';
        }

        if ($column->allowNull) {
            $definition .= ' NULL';

        } elseif (!$column->autoIncrement) {
            $definition .= ' NOT NULL';

        } elseif ($column->autoIncrement && $column->unsigned) {
            $definition .= ' NOT NULL AUTO_INCREMENT';
        }

        if ($column->defaultValue instanceof \yii\db\Expression) {
            $definition .= " DEFAULT $column->defaultValue";

        } elseif ($column->defaultValue !== null) {
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
