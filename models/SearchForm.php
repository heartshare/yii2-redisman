<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 28.02.15
 * Time: 22:59
 */

namespace insolita\redisman\models;


use yii\base\Model;

class SearchForm extends Model{
   public $pattern;
   public $type;

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
            [['pattern', 'type'], 'required'],
            [['type'], 'in','range'=>[0,1,2,3,4]],
            ['db', 'integer'],
            ['db', 'validateDb'],
        ];
    }
} 