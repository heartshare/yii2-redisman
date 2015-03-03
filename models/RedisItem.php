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
     * @var \insolita\redisman\RedismanModule $module
     **/
    private $module;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->module = \Yii::$app->getModule('redisman');
    }

    /**
     * @inheritdoc
     * @return array
     */
    public function rules()
    {
        return [
            ['key','string'],
            ['db','integer','min'=>0],
            ['db','dbValidator','on'=>'move'],
            [['type'], 'in','range'=>array_keys(RedismanModule::$types)],
            [['value'], 'string', 'when'=>function($model){return $model->type==RedismanModule::REDIS_STRING;}],
            [['formatvalue'], 'string'],
            [['formatvalue'], 'itemValidator', 'when'=>function($model){return $model->type!=RedismanModule::REDIS_STRING;}],
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
            'update' => ['value','formatvalue',  'ttl'],
            'append' => ['value','formatvalue'],
            'persist' => ['ttl'],
            'move' => ['db'],
            'create' => ['key', 'value','formatvalue', 'ttl']
        ];
    }

    public function dbValidator($attribute, $params){
          if($this->$attribute==$this->oldAttributes[$attribute]){
              $this->addError($attribute,RedismanModule::t('redisman', 'Bad idea - try move in itself'));
              return false;
          }elseif(!is_array($this->$attribute, $this->module->dbList())){
              $this->addError($attribute,RedismanModule::t('redisman', 'Try to move in unavailable db'));
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


                if ($this->type == RedismanModule::REDIS_LIST) {

                } elseif ($this->type == RedismanModule::REDIS_HASH) {
                    return true;
                } elseif ($this->type == RedismanModule::REDIS_SET) {
                    return true;
                } elseif ($this->type == RedismanModule::REDIS_ZSET) {
                    return true;
                } 
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'key' => RedismanModule::t('redisman', 'Key'),
            'value' => RedismanModule::t('redisman', 'Value'),
            'formatvalue' => RedismanModule::t('redisman', 'Value'),
            'appendvalue' => RedismanModule::t('redisman', 'Append Value'),
            'size' => RedismanModule::t('redisman', 'Key Length'),
            'ttl' => RedismanModule::t('redisman', 'Expire'),
            'type' => RedismanModule::t('redisman', 'Keys type'),
            'refcount' => RedismanModule::t('redisman', 'Refcount'),
            'idletime' => RedismanModule::t('redisman', 'Idle time'),
            'encoding' => RedismanModule::t('redisman', 'Encoding'),
            'newttl' => RedismanModule::t('redisman', 'Set Expire'),
            'db' => RedismanModule::t('redisman', 'Current Db num'),
            'storage' => RedismanModule::t('redisman', 'Current Db storage'),
        ];
    }

    /**
     * Find key value and properties by key
     *
     * @param string $key
     * @return RedisItem
     * @throws \yii\web\NotFoundHttpException'
     **/
    public function find($key)
    {
        $conn = $this->module->getConnection();
        $info = $conn->executeCommand(
            'EVAL', [$this->infoScript($key), 0]
        );
        if(!$info){
            throw new NotFoundHttpException(RedismanModule::t('redisman', 'key not found'));
        }else{
            list($type, $size, $ttl, $refcount, $idletype, $encoding) = $conn->executeCommand(
                'EVAL', [$this->infoScript($key), 0]
            );
            switch($type){
            case RedismanModule::REDIS_STRING:
                $value = $this->getKeyVal($key, $type);
                $formatvalue = $value;
                break;
            case RedismanModule::REDIS_HASH:
            case RedismanModule::REDIS_ZSET:
                if($size<5000){
                    $value = $this->getKeyVal($key, $type);
                    $value = $this->arrayAssociative($value);
                    $formatvalue = Json::encode($value);
                }else{
                    //@TODO: big value
                }
                break;
            case RedismanModule::REDIS_LIST:
            case RedismanModule::REDIS_SET:
                if($size<5000){
                    $value = $this->getKeyVal($key, $type);
                    $formatvalue = implode("\r\n",$value);
                }else{
                    //@TODO: big value
                }
            }
            $this->setAttributes(
                ArrayHelper::merge(
                    [
                        'class' => 'insolita\redisman\models\RedisItem',
                        'key'=>$key,
                        'db' => $this->module->getCurrentDb(),
                        'storage' => $this->module->getCurrentConn()
                    ],
                    compact('value','formatvalue', 'type', 'size', 'ttl', 'refcount', 'idletime', 'encoding')
                ), false
            );
            $this->afterFind();
            return $this;

        }

    }

    public function afterFind(){
        $this->oldAttributes=$this->getAttributes();
    }

    /**
     * Get any redis key function
     *
     * @param string $key
     *
     * @return bool
     */
    public function getKeyVal($key, $type=null)
    {
        if(!$type){
            $type = $this->module->executeCommand('TYPE', [$key]);
        }

        switch($type){
        case RedismanModule::REDIS_LIST:  return $this->module->executeCommand('RPUSH', $value);
        case RedismanModule::REDIS_HASH: return $this->module->executeCommand('HMSET', $value);
        case RedismanModule::REDIS_ZSET: return $this->module->executeCommand('SADD', $value);
        case RedismanModule::REDIS_SET: return $this->module->executeCommand('ZADD', $value);
        default:return false;
        }


        if ($type == RedismanModule::REDIS_STRING) {
            return $conn->get($key);
        } elseif ($type == RedismanModule::REDIS_HASH) {
            return $conn->hgetall($key);
        } elseif ($type == RedismanModule::REDIS_ZSET) {
            return $conn->zrange($key, 0, -1, 'withscores');
        } elseif ($type == RedismanModule::REDIS_SET) {
            return $conn->smembers($key);
        } elseif ($type == RedismanModule::REDIS_LIST) {
            return $conn->lrange($key, 0, -1);
        } else {
            return false;
        }
    }

    public function searchVals($params){
        $data=$this->getKeyVal();
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
        if ($type == RedismanModule::REDIS_STRING) {
            return $this->module->executeCommand('SET',[$key, $value]);
        } elseif (is_array($value)) {
            array_unshift($value, $key);
            switch($type){
            case RedismanModule::REDIS_LIST:  return $this->module->executeCommand('RPUSH', $value);
            case RedismanModule::REDIS_HASH: return $this->module->executeCommand('HMSET', $value);
            case RedismanModule::REDIS_ZSET: return $this->module->executeCommand('SADD', $value);
            case RedismanModule::REDIS_SET: return $this->module->executeCommand('ZADD', $value);
            default:return false;
            }

        } else {
            return false;
        }

    }

    public function persist(){
       if($this->ttl==-1){
           $this->module->executeCommand('PERSIST', [$this->key]);
       }else{
           $this->module->executeCommand('EXPIRE', [$this->key, $this->ttl]);
       }
    }

    public function move(){
        $this->module->executeCommand('MOVE', [$this->key, $this->db]);
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