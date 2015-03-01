<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 28.02.15
 * Time: 10:25
 */

namespace insolita\redisman\models;


use insolita\redisman\RedismanModule;
use yii\base\Model;

/**
 * Class ConnectionForm
 *
 * @package insolita\redisman\models
 */
class ConnectionForm extends Model
{

    /**
     * @var string $connection
     */
    public $connection;
    /**
     * @var integer $db
     */
    public $db;

    /**
     * @var \insolita\redisman\RedismanModule $module
     **/
    private $module;

    /**
     * @inherit
     */
    public function init()
    {
        parent::init();
        $this->module = \Yii::$app->getModule('redisman');
    }

    /**
     * @inherit
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['connection', 'db'], 'required'],
            [['connection'], 'in', 'range' => $this->module->connectionList()],
            ['db', 'integer'],
            ['db', 'validateDb'],
        ];
    }

    /**
     * @inherit
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'connection' => RedismanModule::t('redisman', 'Connection'),
            'db' => RedismanModule::t('redisman', 'Database')
        ];
    }

    /**
     * @param $attribute
     * @param $params
     *
     * @return bool
     */
    public function validateDb($attribute, $params)
    {
        if (!$this->hasErrors('connection')) {
            $totalDbCount = $this->module->totalDbCount();
            if ($this->$attribute >= $totalDbCount[$this->connection]) {
                $this->addError($attribute, RedismanModule::t('redisman','Database with current number not allowed for this connection'));
                return false;
            }
            return true;
        } else {
            return false;
        }
    }
} 