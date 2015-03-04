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
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\NotFoundHttpException;

/**
 * Class RedisItem
 *
 * @package insolita\redisman\models
 */
class RedisItem extends Model
{
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
     * @var string $formatvalue
     */
    public $formatvalue;

    /**
     * @var string $appendvalue
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
            ['key','string'],
            ['key','keyExists'],
            ['db','integer','min'=>0],
            ['db','dbValidator','on'=>'move'],
            [['type'], 'in','range'=>array_keys(Redisman::$types)],
            [['value'], 'string', 'when'=>function($model){return $model->type==Redisman::REDIS_STRING;}],
            [['formatvalue'], 'string'],
            [['formatvalue'], 'itemValidator', 'when'=>function($model){return $model->type!=Redisman::REDIS_STRING;}],
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
            'update' => ['key','value','formatvalue',  'ttl'],
            'append' => ['key','value','formatvalue'],
            'persist' => ['key','ttl'],
            'move' => ['key','db'],
             'delete'=>['key'],
            'create' => ['key', 'value','formatvalue', 'ttl']
        ];
    }

    public function keyExists($attribute,$params){
        if(!$check=Redisman::getInstance()->executeCommand('EXISTS',[$this->$attribute])){
            $this->addError($attribute,Redisman::t('redisman', 'Key not found'));
            return false;
        }
        return true;
    }

    public function dbValidator($attribute, $params){
          if($this->$attribute==$this->oldAttributes[$attribute]){
              $this->addError($attribute,Redisman::t('redisman', 'Bad idea - try move in itself'));
              return false;
          }elseif(!is_array($this->$attribute, Redisman::getInstance()->dbList())){
              $this->addError($attribute,Redisman::t('redisman', 'Try to move in unavailable db'));
              return false;
          }
         return true;
    }
    /**
     * @param $attribute
     * @param $params
     *
     * @return bool
     */
    public function itemValidator($attribute, $params)
    {
 
                $val=Json::decode($this->attribute);
                $json=$this->$attribute;
                $parsejson=mb_substr($json,1,mb_strlen($json)-1);
                /**
                 * формат список с заданным разделителем
                **/
                /**
                 * "val1","val2","val3".... REDIS_LIST|REDIS_SET
                 * "field1","val1","field2","val2"....REDIS_HASH  % 2
                 * 1,'test1val', 2,'iufri', 5, 'ifurirf'  REDIS_ZSET % 2 - первый в паре - integer
                */


                if ($this->type == Redisman::REDIS_LIST) {

                } elseif ($this->type == Redisman::REDIS_HASH) {
                    return true;
                } elseif ($this->type == Redisman::REDIS_SET) {
                    return true;
                } elseif ($this->type == Redisman::REDIS_ZSET) {
                    return true;
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

    public function findInfo(){
        $info = Redisman::getInstance()->executeCommand(
            'EVAL', [$this->infoScript($this->key), 0]
        );
        if(!$info){
            throw new NotFoundHttpException(Redisman::t('redisman', 'key not found'));
        }else {
            list($type, $size, $ttl, $refcount, $idletype, $encoding) = Redisman::getInstance()->executeCommand(
                'EVAL', [$this->infoScript($this->key), 0]
            );
            $this->setAttributes(
                ArrayHelper::merge(
                    [
                        'class' => 'insolita\redisman\models\RedisItem',
                        'key'=>$this->key,
                        'db' => Redisman::getInstance()->getCurrentDb(),
                        'storage' => Redisman::getInstance()->getCurrentConn()
                    ],
                    compact('type', 'size', 'ttl', 'refcount', 'idletime', 'encoding')
                ), false
            );
            $this->afterFind();
            return $this;
        }
    }

    public function findValue(){
        $value = $this->getKeyVal();
        switch($this->type){
        case Redisman::REDIS_STRING:
            $formatvalue = $value;
            break;
        case Redisman::REDIS_HASH:
        case Redisman::REDIS_ZSET:
            if($this->size<5000){
                $value = $this->arrayAssociative($value);
                $formatvalue = Json::encode($value);
            }else{
                //@TODO: big value
            }
            break;
        case Redisman::REDIS_LIST:
        case Redisman::REDIS_SET:
            if($this->size<5000){
                $formatvalue = implode("\r\n",$value);
            }else{
                //@TODO: big value
            }
        }
        $this->setAttributes(
                compact('value','formatvalue')
            , false
        );
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
    public function getKeyVal()
    {
        switch($this->type){
        case Redisman::REDIS_STRING: return Redisman::getInstance()->executeCommand('GET', [$this->key]);
        case Redisman::REDIS_LIST:  return Redisman::getInstance()->executeCommand('LRANGE', [$this->key, 0, -1]);
        case Redisman::REDIS_HASH: return Redisman::getInstance()->executeCommand('HGETALL', [$this->key]);
        case Redisman::REDIS_SET: return Redisman::getInstance()->executeCommand('SMEMBERS', [$this->key]);
        case Redisman::REDIS_ZSET: return Redisman::getInstance()->executeCommand('ZRANGE', [$this->key, 0, -1, 'WITHSCORES']);
        default:return false;
        }
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
            case Redisman::REDIS_LIST:  return Redisman::getInstance()->executeCommand('RPUSH', $value);
            case Redisman::REDIS_HASH: return Redisman::getInstance()->executeCommand('HMSET', $value);
            case Redisman::REDIS_ZSET: return Redisman::getInstance()->executeCommand('SADD', $value);
            case Redisman::REDIS_SET: return Redisman::getInstance()->executeCommand('ZADD', $value);
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
        Redisman::getInstance()->executeCommand('DELETE', [$this->key]);
    }

    /**
     * Converted redis-returned array into normal hash array
     *
     * @param array $arr
     *
     * @return array
     */
    protected function arrayAssociative($arr)
    {
        $newarr = [];
        if (!empty($arr) && count($arr) % 2 == 0) {
            $arr = array_chunk($arr, 2);
            foreach ($arr as $pair) {
                $newarr[$pair[0]] = $pair[1];
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
    protected function infoScript($key)
    {
        $script
            = <<<EOF
local iskey = redis.call("EXISTS", "$key");
if iskey=="0" then
return 0
end
local tp=redis.call("TYPE", "$key")["ok"]
local size=9999
if tp == "string" then
    size=redis.call("STRLEN", "$key")
elseif tp == "hash" then
    size=redis.call("HLEN", "$key")
elseif tp == "list" then
    size=redis.call("LLEN", "$key")
elseif tp == "set" then
    size=redis.call("SCARD", "$key")
elseif tp == "zset" then
    size=redis.call("ZCARD", "$key")
else
    size=9999
end
local info={tp, size, redis.call("TTL", "$key"),
            redis.call("OBJECT","REFCOUNT", "$key"),redis.call("OBJECT","IDLETIME", "$key"),
            redis.call("OBJECT", "ENCODING", "$key"),redis.call("TTL", "$key")};
return info;
EOF;
        return $script;
    }

} 