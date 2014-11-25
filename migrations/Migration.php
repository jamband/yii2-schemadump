<?php

namespace jambands\migrations;

use Yii;

/**
 * Migration class file.
 */
class Migration extends \yii\db\Migration
{
    /**
     * @var string the table options
     */
    protected $tableOptions = '';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (Yii::$app->db->driverName === 'mysql') {
            $this->tableOptions = 'ENGINE=InnoDB CHARACTER SET=utf8 COLLATE=utf8_general_ci';
        }
    }
}
