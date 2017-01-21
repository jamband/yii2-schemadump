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

use ReflectionClass;
use Yii;
use yii\console\Controller;
use yii\db\Connection;
use yii\db\Schema;
use yii\di\Instance;

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
        return array_merge(parent::options($actionID), [
            'migrationTable',
            'db',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        $this->db = Instance::ensure($this->db, Connection::class);
        return true;
    }

    /**
     * Generates the 'createTable' code.
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema name.
     * @return integer the status of the action execution
     */
    public function actionCreate($schema = '')
    {
        $stdout = '';
        foreach ($this->getTableSchemas($schema) as $table) {
            if ($table->name === $this->migrationTable) {
                continue;
            }
            $stdout .= static::generateCreateTable($table->name).
                static::generateColumns($table->columns).
                static::generatePrimaryKey($table->primaryKey, $table->columns).
                static::generateTableOption();
        }
        foreach ($this->getTableSchemas($schema) as $table) {
            $stdout .= $this->generateForeignKey($table);
        }
        $this->stdout($stdout);
    }

    /**
     * Generates the 'dropTable' code.
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema name.
     * @return integer the status of the action execution
     */
    public function actionDrop($schema = '')
    {
        $stdout = '';
        foreach ($this->getTableSchemas($schema) as $table) {
            if ($table->name === $this->migrationTable) {
                continue;
            }
            $stdout .= "\$this->dropTable('{{%$table->name}}');";

            if (!empty($table->foreignKeys)) {
                $stdout .= " // fk: ";

                foreach ($table->foreignKeys as $fk) {
                    foreach ($fk as $k => $v) {
                        if (0 === $k) {
                            continue;
                        }
                        $stdout .= "$k, ";
                    }
                }
                $stdout = rtrim($stdout, ', ');
            }
            $stdout .= "\n";
        }
        $this->stdout($stdout);
    }

    /**
     * @param string $tableName
     * @return string
     */
    private static function generateCreateTable($tableName)
    {
        return sprintf("// %s\n", $tableName).
            sprintf("\$this->createTable('{{%%%s}}', [\n", $tableName);
    }


    /**
     * Returns the columns definition.
     * @param array $columns
     * @return string
     */
    private static function generateColumns(array $columns)
    {
        $definition = '';
        foreach ($columns as $column) {
            $definition .= sprintf("    '%s' => %s.\"%s\",\n",
                $column->name, static::getSchemaType($column), static::other($column));
        }
        return str_replace('.""', '', $definition);
    }

    /**
     * Returns the primary key definition.
     * @param array $pk
     * @param array $columns
     * @return string the primary key definition
     */
    private static function generatePrimaryKey(array $pk, array $columns)
    {
        if (empty($pk)) {
            return '';
        }
        // Composite primary keys
        if (2 <= count($pk)) {
            $compositePk = implode(', ', $pk);
            return "    'PRIMARY KEY ($compositePk)',\n";
        }
        // Primary key not an auto-increment
        $flag = false;
        foreach ($columns as $column) {
            if ($column->autoIncrement && !$column->unsigned) {
                $flag = true;
            }
        }
        if (false === $flag) {
            return sprintf("    'PRIMARY KEY (%s)',\n", $pk[0]);
        }
        return '';
    }

    /**
     * @return string
     */
    private static function generateTableOption()
    {
        return "], \$this->tableOptions);\n\n";
    }

    /**
     * Returns the foreign key definition.
     * @param TableSchema[] $table
     * @return string|null foreign key definition or null
     */
    private function generateForeignKey($table)
    {
        if (empty($table->foreignKeys)) {
            return;
        }
        $definition = "// fk: $table->name\n";

        foreach ($table->foreignKeys as $fk) {
            $refTable = '';
            $refColumns = '';
            $columns = '';

            foreach ($fk as $k => $v) {
                if (0 === $k) {
                    $refTable = $v;
                } else {
                    $columns = $k;
                    $refColumns = $v;
                }
            }
            $definition .= sprintf("\$this->addForeignKey('%s', '{{%%%s}}', '%s', '{{%%%s}}', '%s');\n",
                'fk_'.$table->name.'_'.$columns, $table->name, $columns, $refTable, $refColumns);
        }
        return "$definition\n";
    }

    /**
     * Returns the schema type.
     * @param ColumnSchema[] $column
     * @return string the schema type
     */
    private static function getSchemaType($column)
    {
        if ($column->autoIncrement && !$column->unsigned) {
            if ('bigint' === $column->type) {
                return static::type('bigpk');
            }
            return static::type('pk');
        }
        if ('tinyint(1)' === $column->dbType) {
            return static::type('boolean');
        }
        if (null !== $column->enumValues) {
            return "\$column->dbType\"";
        }
        return static::type($column->type);
    }

    /**
     * @param string $schema
     * @return array
     */
    private function getTableSchemas($schema)
    {
        return $this->db->schema->getTableSchemas($schema);
    }

    /**
     * Returns the other definition.
     * @param ColumnSchema[] $column
     * @return string the other definition
     */
    private static function other($column)
    {
        $definition = '';

        // size
        if (null !== $column->scale && 0 < $column->scale) {
            $definition .= "($column->precision,$column->scale)";

        } elseif (null !== $column->size && !$column->autoIncrement && 'tinyint(1)' !== $column->dbType) {
            $definition .= "($column->size)";

        } elseif (null !== $column->size && $column->unsigned) {
            $definition .= "($column->size)";
        }

        // unsigned
        if ($column->unsigned) {
            $definition .= ' UNSIGNED';
        }

        // null, auto-increment
        if ($column->allowNull) {
            $definition .= ' NULL';

        } elseif (!$column->autoIncrement) {
            $definition .= ' NOT NULL';

        } elseif ($column->autoIncrement && $column->unsigned) {
            $definition .= ' NOT NULL AUTO_INCREMENT';
        }

        // default value
        if ($column->defaultValue instanceof \yii\db\Expression) {
            $definition .= " DEFAULT $column->defaultValue";

        } elseif (null !== $column->defaultValue) {
            $definition .= " DEFAULT '".addslashes($column->defaultValue)."'";
        }

        // comment
        if ('' !== $column->comment) {
            $definition .= " COMMENT '".addslashes($column->comment)."'";
        }

        return $definition;
    }

    /**
     * Returns the constant strings of yii\db\Schema class. e.g. Schema::TYPE_PK
     * @param string $type the column type
     * @return string
     */
    private static function type($type)
    {
        $class = new ReflectionClass(Schema::class);
        return $class->getShortName().'::'.implode(array_keys($class->getConstants(), $type));
    }
}
