<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 01.03.15
 * Time: 19:21
 */

namespace insolita\redisman\models;

use insolita\redisman\RedismanModule;
use yii\base\Model;

class RedisItem extends Model
{
    public $key;
    public $size;
    public $ttl;
    public $type;
    public $refcount;
    public $idletime;
    public $encoding;


    public $value;
    public $newttl;

    private $changedFields=[];

    public function rules(){
        return [
            [['value'],'itemValidator'],
            [['ttl'],'integer']
        ];
    }

    public function itemValidator($attribute,$params){
        if($this->type==RedismanModule::REDIS_STRING){
            return true;
        }elseif($this->type==RedismanModule::REDIS_LIST){
            return true;
        }elseif($this->type==RedismanModule::REDIS_HASH){
            return true;
        }elseif($this->type==RedismanModule::REDIS_SET){
            return true;
        }elseif($this->type==RedismanModule::REDIS_ZSET){
            return true;
        }
    }

    public function attributeLabels(){
        return [
            'key'=>RedismanModule::t('Key'),
            'value'=>RedismanModule::t('Value'),
            'size'=>RedismanModule::t('Size'),
            'ttl'=>RedismanModule::t('Ttl'),
            'type'=>RedismanModule::t('Type'),
            'refcount'=>RedismanModule::t('Refcount'),
            'idletime'=>RedismanModule::t('Idletime'),
            'encoding'=>RedismanModule::t('Encoding'),
            'newttl'=>RedismanModule::t('New TTl'),
        ];
    }


} 