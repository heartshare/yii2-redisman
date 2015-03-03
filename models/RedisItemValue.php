<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 02.03.15
 * Time: 20:21
 */

namespace insolita\redisman\models;


use insolita\redisman\RedismanModule;
use yii\base\Model;

class RedisItemValue extends Model{
    public $parent_key;
    public $parent_type;

    public $field_key;
    public $field_value;

    public function rules(){
        return [
            [['field_key','field_value'],'required', 'on'=>'create'],
            [['field_value'],'required', 'on'=>'update'],
        ];
    }

    public function attributeLabels(){
        return [
            'field_key'=>RedismanModule::t('redisman','Field key'),
            'field_value'=>RedismanModule::t('redisman','Field value'),
        ];
    }

    public function search($key, $type){

    }
} 