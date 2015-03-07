<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 01.03.15
 * Time: 19:21
 */

namespace insolita\redisman\models;

use insolita\redisman\Redisman;
use yii\base\Model;
use yii\data\ArrayDataProvider;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\NotFoundHttpException;

/**
 * Class RedisItem
 *
 * @package insolita\redisman\models
 */
class RedisItem extends Model
{
    const EVENT_AFTER_CHANGE='onchange';
    /**
     * @var string $key
     */
    public $key;
    /**
     * @var integer $size
     */
    public $size;
    /**
     * @var integer $ttl
     */
    public $ttl;
    /**
     * @var string $type
     */
    public $type;
    /**
     * @var integer $refcount
     */
    public $refcount;
    /**
     * @var integer $idletime
     */
    public $idletime;
    /**
     * @var string $encoding
     */
    public $encoding;
    /**
     * @var integer $db
     */
    public $db;
    /**
     * @var string $storage
     */
    public $storage;
    /**
     * @var array|string $value
     */
    public $value;

    /**
     * @var string $field  (HASH|ZSET field idntifier)
     */
    public $field;

    /**
     * @var array|string $formatvalue
     */
    public $formatvalue;

    /**
     * @var array|string  $appendvalue
     */
    public $appendvalue;
    /**
     * @var int $appendpos
     */
    public $appendpos;
    /**
     * @var array $oldAttributes
     */
    private $oldAttributes= [];


    /**
     * @inheritdoc
     * @return array
     */
    public function rules()
    {
        return [
            ['key','required'],
            [['db'],'required','on'=>'move'],
            [['ttl'],'required','on'=>'persist'],
            [['formatvalue'],'required','on'=>['create','update','append']],

            ['key','string'],
            ['key','keyExists'],

            ['field','required','on'=>'remfield'],
            ['field','string'],

            ['db','integer','min'=>0],
            ['db','dbValidator','on'=>'move'],

            ['type','required'],
            [['type'], 'in','range'=>array_keys(Redisman::$types)],

            [['formatvalue'], 'string','on'=>'update, create', 'when'=>function($model){
                return in_array($model->type,[Redisman::REDIS_STRING,Redisman::REDIS_SET,Redisman::REDIS_LIST]);
            }],
            [['formatvalue'], 'isarrayValidator','on'=>'update, create', 'when'=>function($model){return ($model->type==Redisman::REDIS_HASH||$model->type==Redisman::REDIS_ZSET);}],

            [['appendvalue'], 'string','on'=>'append', 'when'=>function($model){
                return in_array($model->type,[Redisman::REDIS_STRING,Redisman::REDIS_SET,Redisman::REDIS_LIST]);
            }],
            [['appendvalue'], 'isarrayValidator','on'=>'append', 'when'=>function($model){return ($model->type==Redisman::REDIS_HASH||$model->type==Redisman::REDIS_ZSET);}],
            [['ttl'], 'integer', 'min' => -1]
        ];
    }

    /**
     * @inheritdoc
     * @return array
     */
    public function scenarios()
    {
        return [
            'default' => ['key', 'value','formatvalue', 'ttl', 'type', 'size', 'refcount', 'encoding', 'idletime', 'db', 'storage'],
            'update' => ['key','formatvalue'],
            'append' => ['key','appendvalue'],
            'persist' => ['key','ttl'],
            'move' => ['key','db'],
             'delete'=>['key'],
            'create' => ['key', 'formatvalue', 'ttl'],
            'remfield'=>['key','field']
        ];
    }

    public function beforeValidate(){
        if(urldecode($this->key)!==$this->key){
            $this->key=urldecode($this->key);
        }
        if($this->field && Html::decode($this->field)!==$this->field){
            $this->field=Html::decode($this->field);
        }
        if(!$this->type){
            $this->type=Redisman::getInstance()->type($this->key);
        }
        return parent::beforeValidate();
    }

    public function keyExists($attribute,$params){
        if(!$check=Redisman::getInstance()->executeCommand('EXISTS',[$this->$attribute])){
            $this->addError($attribute,Redisman::t('redisman', 'Key not found'));
            return false;
        }
        return true;
    }

    public function isarrayValidator($attribute, $params){
        if(!is_array($this->$attribute)){
            $this->addError($attribute,Redisman::t('redisman', 'Wrong field type'));
            return false;
        }
        if(empty($this->$attribute)){
            $this->addError($attribute,Redisman::t('redisman', 'Can`t be Empty'));
            return false;
        }
        return true;
    }

    public function dbValidator($attribute, $params){
          if($this->$attribute==Redisman::getInstance()->getCurrentDb()){
              $this->addError($attribute,Redisman::t('redisman', 'Bad idea - try move in itself'));
              return false;
          }elseif(!in_array($this->$attribute, array_keys(Redisman::getInstance()->dbList()))){
              $this->addError($attribute,Redisman::t('redisman', 'Try to move in unavailable db'));
              return false;
          }
         return true;
    }

    public function update(){
        switch($this->type){
        case Redisman::REDIS_STRING:
            $this->addKey($this->type, $this->key, $this->formatvalue);
            break;
        case Redisman::REDIS_HASH:
            foreach($this->formatvalue as $field=>$val){
                if(!empty($val)){
                    Redisman::getInstance()->executeCommand('HSET',[$this->key, $field, $val]);
                }
            }
            break;
        case Redisman::REDIS_ZSET:
            foreach($this->formatvalue as $field=>$score){
                if(is_numeric($score)){
                    Redisman::getInstance()->executeCommand('ZADD',[$this->key,$score,$field]);
                }
            }
            break;
        case Redisman::REDIS_LIST:
        case Redisman::REDIS_SET:
            $insvalue = explode("\r\n",$this->formatvalue);
            foreach($insvalue as &$fv){
                $fv=trim($fv);
            }
            Redisman::getInstance()->executeCommand('DEL',[$this->key]);
            $this->addKey($this->type, $this->key, $insvalue);
            break;

        }
    }

    public function append(){
        switch($this->type){
        case Redisman::REDIS_STRING:
            Redisman::getInstance()->executeCommand('APPEND',[$this->key, $this->appendvalue]);
            break;
        case Redisman::REDIS_HASH:
            foreach($this->appendvalue as $row){
                if(!empty($row['field']) && !empty($row['value'])){
                    Redisman::getInstance()->executeCommand('HSET',[$this->key, $row['field'], $row['value']]);
                }
            }
            break;
        case Redisman::REDIS_ZSET:
            foreach($this->appendvalue as $row){
                if(!empty($row['field']) && !empty($row['score']) && is_numeric($row['score'])){
                    Redisman::getInstance()->executeCommand('ZADD',[$this->key, $row['score'], $row['field']]);
                }
            }
            break;
        case Redisman::REDIS_LIST:
        case Redisman::REDIS_SET:
            $insvalue = explode("\r\n",$this->appendvalue);
            foreach($insvalue as &$fv){
                $fv=trim($fv);
            }
            $this->addKey($this->type, $this->key, $insvalue);
            break;

        }
    }

    public function remfield(){
        switch($this->type) {
        case Redisman::REDIS_HASH:
            Redisman::getInstance()->executeCommand('HDEL',[$this->key, $this->field]);
            break;
        case Redisman::REDIS_ZSET:
            Redisman::getInstance()->executeCommand('ZREM',[$this->key, $this->field]);
            break;
        default:
        }
     }



    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'key' => Redisman::t('redisman', 'Key'),
            'value' => Redisman::t('redisman', 'Value'),
            'formatvalue' => Redisman::t('redisman', 'Value'),
            'appendvalue' => Redisman::t('redisman', 'Append Value'),
            'size' => Redisman::t('redisman', 'Key Length'),
            'ttl' => Redisman::t('redisman', 'Expire'),
            'type' => Redisman::t('redisman', 'Keys type'),
            'refcount' => Redisman::t('redisman', 'Refcount'),
            'idletime' => Redisman::t('redisman', 'Idle time'),
            'encoding' => Redisman::t('redisman', 'Encoding'),
            'newttl' => Redisman::t('redisman', 'Set Expire'),
            'db' => Redisman::t('redisman', 'Current Db num'),
            'storage' => Redisman::t('redisman', 'Current Db storage'),
        ];
    }

    public static function find($key){
        $ex = Redisman::getInstance()->executeCommand(
            'EXISTS', [$key]
        );
        if(!$ex){
            throw new NotFoundHttpException(Redisman::t('redisman', 'key not found'));
        }else {
            list($type, $size, $ttl, $refcount, $idletype, $encoding) = Redisman::getInstance()->executeCommand(
                'EVAL', [self::infoScript($key), 0]
            );
            $model=new static;
            $model->setAttributes(
                ArrayHelper::merge(
                    [
                        'class' => 'insolita\redisman\models\RedisItem',
                        'key'=>$key,
                        'db' => Redisman::getInstance()->getCurrentDb(),
                        'storage' => Redisman::getInstance()->getCurrentConn()
                    ],
                    compact('type', 'size', 'ttl', 'refcount', 'idletime', 'encoding')
                ), false
            );
            $model->afterFind();
            return $model;
        }
    }

    public function findValue(){
        $this->value = $this->getValue();
        switch($this->type){
        case Redisman::REDIS_STRING:
            $this->formatvalue = $this->value;
            break;
        case Redisman::REDIS_HASH:
        case Redisman::REDIS_ZSET:
                $this->value = $this->arrayAssociative($this->value);
                $this->formatvalue =$this->valueDataProvider();
            break;
        case Redisman::REDIS_LIST:
        case Redisman::REDIS_SET:
                $this->formatvalue = implode("\r\n",$this->value);
            break;

        }
        $this->oldAttributes['value']=$this->value;
        $this->oldAttributes['formatvalue']=$this->formatvalue;
        return $this;
    }


    public function afterFind(){
        $this->oldAttributes=$this->getAttributes();
    }

    /**
     * Get any redis key
     *
     * @return bool|string|array
     */
    public function getValue()
    {
        switch($this->type){
        case Redisman::REDIS_STRING: return Redisman::getInstance()->executeCommand('GET', [$this->key]);break;
        case Redisman::REDIS_LIST:  return Redisman::getInstance()->executeCommand('LRANGE', [$this->key, 0, -1]);break;
        case Redisman::REDIS_HASH: return Redisman::getInstance()->executeCommand('HGETALL', [$this->key]);break;break;
        case Redisman::REDIS_SET: return Redisman::getInstance()->executeCommand('SMEMBERS', [$this->key]);
        case Redisman::REDIS_ZSET: return Redisman::getInstance()->executeCommand('ZRANGE', [$this->key, 0, -1, 'WITHSCORES']);break;
        default:return false;
        }
    }

    public function valueDataProvider(){
        $totalcount = count($this->value);
        $allModels =$sort= [];
        if($this->type==Redisman::REDIS_HASH){
            foreach ($this->value as $key => $val) {
                $allModels[] = [
                    'field' => $key, 'value' => $val
                ];
                $sort=[
                    'attributes' => ['field', 'value'],
                    'defaultOrder'=>['field'=>SORT_ASC]
                ];
            }
        }elseif($this->type==Redisman::REDIS_ZSET){
            foreach ($this->value as $key => $val) {
                $allModels[] = [
                    'score' => $val, 'field' => $key
                ];
                $sort=[
                    'attributes' => ['field', 'score'],
                    'defaultOrder'=>['field'=>SORT_ASC]
                ];
            }
        }else{
            return false;
        }

        return new ArrayDataProvider([
                'key' => 'field',
                'allModels' => $allModels,
                'totalCount' => $totalcount,
                'sort' =>$sort,
                'pagination' => [
                    'totalCount' => $totalcount,
                    'pageSize' => 15,
                ]
            ]);
    }

    /**
     * Add any redis key function
     *
     * @param int    $type
     * @param string $key
     * @param (string|array) $value
     *
     * @return boolean
     */
    public function addKey($type, $key, $value)
    {
        if ($type == Redisman::REDIS_STRING) {
            return Redisman::getInstance()->executeCommand('SET',[$key, $value]);
        } elseif (is_array($value)) {
            array_unshift($value, $key);
            switch($type){
            case Redisman::REDIS_LIST:  return Redisman::getInstance()->executeCommand('RPUSH', $value);break;
            case Redisman::REDIS_HASH: return Redisman::getInstance()->executeCommand('HMSET', $value);break;
            case Redisman::REDIS_SET: return Redisman::getInstance()->executeCommand('SADD', $value);break;
            case Redisman::REDIS_ZSET: return Redisman::getInstance()->executeCommand('ZADD', $value);break;
            default:return false;
            }

        } else {
            return false;
        }

    }

    public function persist(){
       if($this->ttl==-1){
           Redisman::getInstance()->executeCommand('PERSIST', [$this->key]);
       }else{
           Redisman::getInstance()->executeCommand('EXPIRE', [$this->key, $this->ttl]);
       }
    }

    public function move(){
        Redisman::getInstance()->executeCommand('MOVE', [$this->key, $this->db]);
    }

    public function delete(){
        Redisman::getInstance()->executeCommand('DEL', [$this->key]);
    }

    /**
     * Converted redis-returned array into normal hash array
     *
     * @param array $arr
     *
     * @return array
     */
    protected  function arrayAssociative($arr)
    {
        $newarr = [];
        if (!empty($arr) && count($arr) % 2 == 0) {
            $arr = array_chunk($arr, 2);
            foreach ($arr as $pair) {
                $newarr[Html::encode($pair[0])] = Html::encode($pair[1]);
            }
        }
        unset($arr);
        return $newarr;
    }


    /**
     * Generate script for searching key information
     *
     * @param string $key
     *
     * @return string
     */
    protected static function infoScript($key)
    {
        $key=Redisman::quoteValue($key);
         $script
            = <<<EOF
local tp=redis.call("TYPE", $key)["ok"]
local size=9999
if tp == "string" then
    size=redis.call("STRLEN", $key)
elseif tp == "hash" then
    size=redis.call("HLEN", $key)
elseif tp == "list" then
    size=redis.call("LLEN", $key)
elseif tp == "set" then
    size=redis.call("SCARD", $key)
elseif tp == "zset" then
    size=redis.call("ZCARD", $key)
else
    size=9999
end
local info={tp, size, redis.call("TTL", $key),
            redis.call("OBJECT","REFCOUNT", $key),redis.call("OBJECT","IDLETIME", $key),
            redis.call("OBJECT", "ENCODING", $key)};
return info;
EOF;
        return $script;
    }

} 