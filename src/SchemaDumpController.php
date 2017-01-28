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
use yii\console\Controller;
use yii\db\Connection;
use yii\db\Expression;
use yii\db\Schema;
use yii\db\TableSchema;
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
     * @return integer the status of the action execution
     */
    public function actionCreate()
    {
        $stdout = '';
        foreach ($this->db->schema->getTableSchemas() as $table) {
            if ($table->name === $this->migrationTable) {
                continue;
            }
            $stdout .= static::generateCreateTable($table->name).
                static::generateColumns($table->columns, $this->db->schema->findUniqueIndexes($table)).
                static::generatePrimaryKey($table->primaryKey, $table->columns).
                static::generateTableOptions();
        }
        foreach ($this->db->schema->getTableSchemas() as $table) {
            $stdout .= $this->generateForeignKey($table);
        }
        $this->stdout($stdout);
    }

    /**
     * Generates the 'dropTable' code.
     * @return integer the status of the action execution
     */
    public function actionDrop()
    {
        $stdout = '';
        foreach ($this->db->schema->getTableSchemas() as $table) {
            if ($table->name === $this->migrationTable) {
                continue;
            }
            $stdout .= static::generateDropTable($table->name);
            if (!empty($table->foreignKeys)) {
                $stdout .= ' // fk: ';

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
     * @param string $name
     * @return string
     */
    private static function generateCreateTable($name)
    {
        return sprintf("// %s\n", $name).
            sprintf("\$this->createTable('{{%%%s}}', [\n", $name);
    }

    /**
     * Returns the columns definition.
     * @param array $columns
     * @param array $unique unique indexes
     * @return string
     */
    private static function generateColumns(array $columns, array $unique)
    {
        $definition = '';
        foreach ($columns as $column) {
            $tmp = sprintf("    '%s' => \$this->%s%s,\n",
                $column->name, static::getSchemaType($column), static::other($column, $unique));

            if (null !== $column->enumValues) {
                $tmp = static::replaceEnumColumn($tmp);
            }
            $definition .= $tmp;
        }
        return $definition;
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
            if ($column->autoIncrement) {
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
    private static function generateTableOptions()
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
     * @param string $name
     * @return string
     */
    private static function generateDropTable($name)
    {
        return "\$this->dropTable('{{%$name}}');";
    }

    /**
     * Returns the schema type.
     * @param ColumnSchema[] $column
     * @return string the schema type
     */
    private static function getSchemaType($column)
    {
        // primary key
        if ($column->isPrimaryKey && $column->autoIncrement) {
            if ('bigint' === $column->type) {
                return 'bigPrimaryKey()';
            }
            return 'primaryKey()';
        }

        // boolean
        if ('tinyint(1)' === $column->dbType) {
            return 'boolean()';
        }

        // smallint
        if ('smallint' === $column->type) {
            if (null === $column->size) {
                return 'smallInteger()';
            }
            return 'smallInteger';
        }

        // bigint
        if ('bigint' === $column->type) {
            if (null === $column->size) {
                return 'bigInteger()';
            }
            return 'bigInteger';
        }

        // enum
        if (null !== $column->enumValues) {
            // https://github.com/yiisoft/yii2/issues/9797
            $enumValues = array_map('addslashes', $column->enumValues);
            return "enum(['".implode('\', \'', $enumValues)."'])";
        }

        // others
        if (null === $column->size && 0 >= $column->scale) {
            return $column->type.'()';
        }
        return $column->type;
    }

    /**
     * Returns the other definition.
     * @param ColumnSchema[] $column
     * @param array $unique
     * @return string the other definition
     */
    private static function other($column, array $unique)
    {
        $definition = '';

        // size
        if (null !== $column->scale && 0 < $column->scale) {
            $definition .= "($column->precision,$column->scale)";

        } elseif (null !== $column->size && !$column->autoIncrement && 'tinyint(1)' !== $column->dbType) {
            $definition .= "($column->size)";

        } elseif (null !== $column->size && !$column->isPrimaryKey && $column->unsigned) {
            $definition .= "($column->size)";
        }

        // unsigned
        if ($column->unsigned) {
            $definition .= '->unsigned()';
        }

        // null
        if ($column->allowNull) {
            $definition .= '->null()';

        } elseif (!$column->autoIncrement) {
            $definition .= '->notNull()';
        }

        // unique key
        if (!$column->isPrimaryKey && !empty($unique)) {
            foreach ($unique as $name) {
                if (reset($name) === $column->name) {
                    $definition .= '->unique()';
                }
            }
        }

        // default value
        if ($column->defaultValue instanceof Expression) {
            $definition .= "->defaultExpression('$column->defaultValue')";

        } elseif (is_int($column->defaultValue)) {
            $definition .= "->defaultValue($column->defaultValue)";

        } elseif (is_bool($column->defaultValue)) {
            $definition .= '->defaultValue('.var_export($column->defaultValue, true).')';

        } elseif (is_string($column->defaultValue)) {
            $definition .= "->defaultValue('".addslashes($column->defaultValue)."')";
        }

        // comment
        if (null !== $column->comment && '' !== $column->comment) {
            $definition .= "->comment('".addslashes($column->comment)."')";
        }

        // append

        return $definition;
    }

    /**
     * @param string $tmp temporary definition
     * @return string
     */
    private static function replaceEnumColumn($tmp)
    {
        return preg_replace("/,\n/", "\",\n", strtr($tmp, [
            '()' => '',
            '])' => ')',
            '),' => ',',
            '$this->enum([' => '"ENUM (',
            '->notNull' => ' NOT NULL',
            '->null' => ' DEFAULT NULL',
            '->defaultValue(' => ' DEFAULT ',
            '->comment(' => ' COMMENT ',
        ]));
    }
}
