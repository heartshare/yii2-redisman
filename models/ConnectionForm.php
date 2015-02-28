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

class ConnectionForm extends Model{

    public $connection;
    public $db;

    /**
     *  @var \insolita\redisman\RedismanModule $module
    **/
    private $module;

    public function init(){
        parent::init();
        $this->module=\Yii::$app->getModule('redisman');
    }

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['connection', 'db'], 'required'],
            [['connection'], 'in','range'=>$this->module->connectionList()],
            ['db', 'validateDb'],
        ];
    }

    public function attributes(){
        return [
            'connection'=>RedismanModule::t('Connection'),
            'db'=>RedismanModule::t('Database')
        ];
    }

    public function ValidateDb($attribute, $params){
        if(!$this->hasErrors('connection')){
            $totalDbCount=$this->module->totalDbCount();
            if($this->$attribute >=$totalDbCount[$this->connection]){
                $this->addError($attribute,'Wrong Db');
                return false;
            }
            return true;
        }else{
            return false;
        }
    }
} 