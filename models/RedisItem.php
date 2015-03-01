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

class RedisItem extends Model
{
    public $key;
    public $size;
    public $ttl;
    public $type;
    public $refcount;
    public $idletime;
    public $encoding;
    public $db;
    public $storage;
    public $value;
    public $newttl;

    private $changedFields = [];
    /**
     * @var \insolita\redisman\RedismanModule $module
     **/
    private $module;

    public function init()
    {
        parent::init();
        $this->module = \Yii::$app->getModule('redisman');
    }

    public function rules()
    {
        return [
            [['value'], 'itemValidator'],
            [['ttl'], 'integer']
        ];
    }

    public function itemValidator($attribute, $params)
    {
        if ($this->type == RedismanModule::REDIS_STRING) {
            return true;
        } elseif ($this->type == RedismanModule::REDIS_LIST) {
            return true;
        } elseif ($this->type == RedismanModule::REDIS_HASH) {
            return true;
        } elseif ($this->type == RedismanModule::REDIS_SET) {
            return true;
        } elseif ($this->type == RedismanModule::REDIS_ZSET) {
            return true;
        }
    }

    public function attributeLabels()
    {
        return [
            'key' => RedismanModule::t('redisman','Key'),
            'value' => RedismanModule::t('redisman','Value'),
            'size' => RedismanModule::t('redisman','Key Length'),
            'ttl' => RedismanModule::t('redisman','Expire'),
            'type' => RedismanModule::t('redisman','Keys type'),
            'refcount' => RedismanModule::t('redisman','Refcount'),
            'idletime' => RedismanModule::t('redisman','Idle time'),
            'encoding' => RedismanModule::t('redisman','Encoding'),
            'newttl' => RedismanModule::t('redisman','Set Expire'),
            'db'=>RedismanModule::t('redisman','Current Db num'),
            'storage'=>RedismanModule::t('redisman','Current Db storage'),
        ];
    }

    /**
     * @return RedisItem
     **/
    public function find($key)
    {
        $conn = $this->module->getConnection();
        $value = $this->getKeyVal($key);
        if ($value) {
            list($type, $size, $ttl, $refcount, $idletype, $encoding) = $conn->executeCommand(
                'EVAL', [$this->infoScript($key), 0]
            );
        } else {
            throw new NotFoundHttpException(RedismanModule::t('redisman','key not found'));
        }
        if ($type == RedismanModule::REDIS_HASH || $type == RedismanModule::REDIS_ZSET
            || $type == RedismanModule::REDIS_SET
        ) {
            $value = $this->arrayAssociative($value);
            $value = Json::encode($value);
        } elseif ($type != RedismanModule::REDIS_STRING) {
            $value = Json::encode($value);
        }
        $this->setAttributes(
            ArrayHelper::merge(
                [
                    'class' => 'insolita\redisman\models\RedisItem',
                    'db'=>$this->module->getCurrentDb(),
                    'storage'=>$this->module->getCurrentConn()
                ],
                compact('value', 'type', 'size', 'ttl', 'refcount', 'idletime', 'encoding')
            ), false
        );
        return $this;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function getKeyVal($key)
    {
        $conn = $this->module->getConnection();
        $type = $conn->type($key);
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


    /**
     * @param int $type
     * @param string $key
     * @param (string|array)$value
     *
     * @return boolean
     */
    public function addKey($type, $key, $value)
    {
        $conn = $this->module->getConnection();
        if ($type == RedismanModule::REDIS_STRING) {
            return $conn->set($key, $value);
        } elseif (is_array($value)) {
            array_unshift($value, $key);
            if ($type == RedismanModule::REDIS_LIST) {
                return $conn->executeCommand('RPUSH', $value);
            } elseif ($type == RedismanModule::REDIS_SET) {
                return $conn->executeCommand('SADD', $value);
            } elseif ($type == RedismanModule::REDIS_ZSET) {
                return $conn->executeCommand('ZADD', $value);
            } elseif ($type == RedismanModule::REDIS_HASH) {
                return $conn->executeCommand('HMSET', $value);
            } else {
                return false;
            }

        } else {
            return false;
        }

    }

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

    protected function infoScript($key)
    {
        $script
            = <<<EOF
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