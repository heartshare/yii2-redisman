<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 01.03.15
 * Time: 19:21
 */

namespace insolita\redisman\models;

use insolita\redisman\components\NativeConnection;
use insolita\redisman\events\ModifyEvent;
use insolita\redisman\Redisman;
use yii\base\Event;
use yii\base\Model;
use yii\data\ArrayDataProvider;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\NotFoundHttpException;

/**
 * Class RedisItem
 *
 * @package insolita\redisman\models
 */
class RedisItem extends Model
{
    /**
     * Event triggered after modify ir create keys
     */
    const EVENT_AFTER_CHANGE = 'afterchange';
    /**
     * Event triggered after find key Information
     */
    const EVENT_AFTER_FIND = 'afterfind';

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
     * @var string $field (HASH|ZSET field idntifier)
     */
    public $field;

    /**
     * @var array|string $formatvalue
     */
    public $formatvalue;


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

    /**
     * @inheritdoc
     * @return array
     */
    public function rules()
    {
        return [
            ['key', 'required'],
            [['db'], 'required', 'on' => 'move'],
            [['ttl'], 'required', 'on' => 'persist'],
            [['ttl'], 'default', 'value' => -1, 'on' => 'create'],
            [['formatvalue'], 'required', 'on' => ['create', 'update', 'append']],

            ['key', 'string'],
            ['key', 'keyExists', 'except' => 'create'],
            ['key', 'keyExists', 'on' => 'create', 'params' => ['not' => true]],

            ['field', 'required', 'on' => 'remfield'],
            ['field', 'string'],

            ['db', 'integer', 'min' => 0],
            ['db', 'dbValidator', 'on' => 'move'],

            ['type', 'required'],
            [['type'], 'in', 'range' => array_keys(Redisman::$types)],

            [
                ['formatvalue'], 'string', 'on' => 'update, append, create', 'when' => function ($model) {
                return in_array($model->type, [Redisman::REDIS_STRING, Redisman::REDIS_SET, Redisman::REDIS_LIST]);
            }
            ],
            [
                ['formatvalue'], 'isarrayValidator', 'on' => 'update, append, create', 'when' => function ($model) {
                return ($model->type == Redisman::REDIS_HASH
                    || $model->type == Redisman::REDIS_ZSET);
                }
            ],

            [['ttl'], 'integer', 'min' => -1],

        ];
    }

    /**
     * @inheritdoc
     * @return array
     */
    public function scenarios()
    {
        return [
            'default' => [
                'key', 'value', 'formatvalue', 'ttl', 'type', 'size', 'refcount', 'encoding', 'idletime', 'db',
                'storage'
            ],
            'update' => ['key', 'formatvalue'],
            'append' => ['key', 'formatvalue'],
            'persist' => ['key', 'ttl'],
            'move' => ['key', 'db'],
            'delete' => ['key'],
            'create' => ['key', 'formatvalue', 'ttl', 'type'],
            'remfield' => ['key', 'field']
        ];
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        if (urldecode($this->key) !== $this->key) {
            $this->key = urldecode($this->key);
        }
        if ($this->field && Html::decode($this->field) !== $this->field) {
            $this->field = Html::decode($this->field);
        }
        if (!$this->type) {
            $this->type = Redisman::getInstance()->type($this->key);
        }
        return parent::beforeValidate();
    }

    /**
     * @param string $attribute
     * @param array  $params
     *
     * @return bool
     */
    public function keyExists($attribute, $params)
    {
        $check = Redisman::getInstance()->executeCommand('EXISTS', [$this->$attribute]);
        if (!$check && ArrayHelper::getValue($params, 'not', false) !== true) {
            $this->addError($attribute, Redisman::t('redisman', 'Key not found'));
            return false;
        } elseif ($check && ArrayHelper::getValue($params, 'not', false) == true) {
            $this->addError($attribute, Redisman::t('redisman', 'Key exist already'));
            return false;
        }
        return true;
    }

    /**
     * @param string $attribute
     * @param array  $params
     *
     * @return bool
     */
    public function isarrayValidator($attribute, $params)
    {
        if (!is_array($this->$attribute)) {
            $this->addError($attribute, Redisman::t('redisman', 'Wrong field type'));
            return false;
        }
        if (empty($this->$attribute)) {
            $this->addError($attribute, Redisman::t('redisman', 'Can`t be Empty'));
            return false;
        }
        foreach($this->$attribute as $k=>$v){
            if(!is_string($v) || !is_numeric($v)){
                $this->addError($attribute, Redisman::t('redisman', 'Wrong array data'));
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $attribute
     * @param array  $params
     *
     * @return bool
     */
    public function dbValidator($attribute, $params)
    {
        if ($this->$attribute == Redisman::getInstance()->getCurrentDb()) {
            $this->addError($attribute, Redisman::t('redisman', 'Bad idea - try move in itself'));
            return false;
        } elseif (!in_array($this->$attribute, array_keys(Redisman::getInstance()->dbList()))) {
            $this->addError($attribute, Redisman::t('redisman', 'Try to move in unavailable db'));
            return false;
        }
        return true;
    }

    /**
     * Update key data
     */
    public function update()
    {
        $event = new ModifyEvent();
        $event->key = $this->key;
        $event->operation = 'update';
        $event->connection = Redisman::getInstance()->getCurrentConn();
        $event->db = Redisman::getInstance()->getCurrentDb();
        if ($this->type == Redisman::REDIS_LIST || $this->type == Redisman::REDIS_SET) {
            $event->command = Html::encode('DEL ' . $this->key);
            Redisman::getInstance()->executeCommand('DEL', [$this->key]);
        }
        $event->command .= $this->save();
        $this->trigger(self::EVENT_AFTER_CHANGE, $event);
    }

    /**
     * Create new key with data
     */
    public function create()
    {
        $event = new ModifyEvent();
        $event->key = $this->key;
        $event->operation = 'create';
        $event->connection = Redisman::getInstance()->getCurrentConn();
        $event->db = Redisman::getInstance()->getCurrentDb();
        $event->command = $this->save();
        if ($this->ttl > -1) {
            $event->command .= Html::encode(' EXPIRE ' . $this->key . ' ' . $this->ttl);
            Redisman::getInstance()->executeCommand('EXPIRE', [$this->key, $this->ttl]);

        }
        $this->trigger(self::EVENT_AFTER_CHANGE, $event);
    }

    /**
     * Append key data to existed
     */
    public function append()
    {
        $event = new ModifyEvent();
        $event->key = $this->key;
        $event->operation = 'append';
        $event->connection = Redisman::getInstance()->getCurrentConn();
        $event->db = Redisman::getInstance()->getCurrentDb();
        $event->command = $this->save();
        $this->trigger(self::EVENT_AFTER_CHANGE, $event);
    }

    /**
     * Remove field from hash or zset
     */
    public function remfield()
    {
        $event = new ModifyEvent();
        $event->key = $this->key;
        $event->operation = 'remfield';
        $event->connection = Redisman::getInstance()->getCurrentConn();
        $event->db = Redisman::getInstance()->getCurrentDb();
        switch ($this->type) {
        case Redisman::REDIS_HASH:
            $event->operation = Html::encode('HDEL ' . $this->key . ' ' . $this->field);
            Redisman::getInstance()->executeCommand('HDEL', [$this->key, $this->field]);
            $this->trigger(self::EVENT_AFTER_CHANGE, $event);
            break;
        case Redisman::REDIS_ZSET:
            $event->operation = Html::encode('ZREM ' . $this->key . ' ' . $this->field);
            Redisman::getInstance()->executeCommand('ZREM', [$this->key, $this->field]);
            $this->trigger(self::EVENT_AFTER_CHANGE, $event);
            break;
        default:
        }
    }


    /**
     * @param $key
     *
     * @return RedisItem
     * @throws NotFoundHttpException
     */
    public static function find($key)
    {
        $ex = Redisman::getInstance()->executeCommand(
            'EXISTS', [$key]
        );
        if (!$ex) {
            throw new NotFoundHttpException(Redisman::t('redisman', 'key not found'));
        } else {
            list($type, $size, $ttl, $refcount, $idletype, $encoding) = Redisman::getInstance()->executeCommand(
                'EVAL', [self::infoScript($key), 0]
            );
            $model = new static;
            $model->setAttributes(
                ArrayHelper::merge(
                    [
                        'class' => 'insolita\redisman\models\RedisItem',
                        'key' => $key,
                        'db' => Redisman::getInstance()->getCurrentDb(),
                        'storage' => Redisman::getInstance()->getCurrentConn()
                    ],
                    compact('type', 'size', 'ttl', 'refcount', 'idletime', 'encoding')
                ), false
            );
            $event = new Event();
            $event->data = $model->getAttributes();
            $model->trigger(self::EVENT_AFTER_FIND, $event);
            return $model;
        }
    }

    /**
     * @return $this
     */
    public function findValue()
    {
        $this->value = $this->getValue();
        switch ($this->type) {
        case Redisman::REDIS_STRING:
            $this->formatvalue = $this->value;
            break;
        case Redisman::REDIS_HASH:
        case Redisman::REDIS_ZSET:
            if (!Redisman::getInstance()->getConnection() instanceof NativeConnection) {
                $this->value = $this->arrayAssociative($this->value);
            }
            $this->formatvalue = $this->valueDataProvider();
            break;
        case Redisman::REDIS_LIST:
        case Redisman::REDIS_SET:
            $this->formatvalue = implode("\r\n", $this->value);
            break;

        }

        return $this;
    }


    /**
     * Get any redis key
     *
     * @return bool|string|array
     */
    public function getValue()
    {
        switch ($this->type) {
        case Redisman::REDIS_STRING:
            return Redisman::getInstance()->executeCommand('GET', [$this->key]);
            break;
        case Redisman::REDIS_LIST:
            return Redisman::getInstance()->executeCommand('LRANGE', [$this->key, 0, -1]);
            break;
        case Redisman::REDIS_HASH:
            return Redisman::getInstance()->executeCommand('HGETALL', [$this->key]);
            break;
            break;
        case Redisman::REDIS_SET:
            return Redisman::getInstance()->executeCommand('SMEMBERS', [$this->key]);
        case Redisman::REDIS_ZSET:
            return Redisman::getInstance()->executeCommand('ZRANGE', [$this->key, 0, -1, 'WITHSCORES']);
            break;
        default:
            return false;
        }
    }

    /**
     * @return bool|ArrayDataProvider
     */
    public function valueDataProvider()
    {
        $totalcount = count($this->value);
        $allModels = $sort = [];
        if ($this->type == Redisman::REDIS_HASH) {
            foreach ($this->value as $key => $val) {
                $allModels[] = [
                    'field' => $key, 'value' => $val
                ];
                $sort = [
                    'attributes' => ['field', 'value'],
                    'defaultOrder' => ['field' => SORT_ASC]
                ];
            }
        } elseif ($this->type == Redisman::REDIS_ZSET) {
            foreach ($this->value as $key => $val) {
                $allModels[] = [
                    'score' => $val, 'field' => $key
                ];
                $sort = [
                    'attributes' => ['field', 'score'],
                    'defaultOrder' => ['field' => SORT_ASC]
                ];
            }
        } else {
            return false;
        }

        return new ArrayDataProvider(
            [
                'key' => 'field',
                'allModels' => $allModels,
                'totalCount' => $totalcount,
                'sort' => $sort,
                'pagination' => [
                    'totalCount' => $totalcount,
                    'pageSize' => 15,
                ]
            ]
        );
    }


    /**
     * @return string
     */
    public function save()
    {
        $command = '';
        switch ($this->type) {
        case Redisman::REDIS_STRING:
            $command = Html::encode('SET ' . $this->key . ' ' . $this->formatvalue);
            Redisman::getInstance()->executeCommand('SET', [$this->key, $this->formatvalue]);
            break;
        case Redisman::REDIS_HASH:
            foreach ($this->formatvalue as $row) {
                if (!empty($row['field']) && !empty($row['value'])) {
                    $command .= Html::encode(' HSET ' . $this->key . ' ' . $row['field'] . ' ' . $row['value']);
                    Redisman::getInstance()->executeCommand('HSET', [$this->key, $row['field'], $row['value']]);
                }
            }

            break;
        case Redisman::REDIS_ZSET:
            foreach ($this->formatvalue as $row) {
                if (!empty($row['field']) && !empty($row['score']) && is_numeric($row['score'])) {
                    $command .= Html::encode(' ZADD ' . $this->key . ' ' . $row['score'] . ' ' . $row['field']);
                    Redisman::getInstance()->executeCommand('ZADD', [$this->key, $row['score'], $row['field']]);
                }
            }
            break;
        case Redisman::REDIS_LIST:
            $insvalue = explode("\r\n", $this->formatvalue);
            foreach ($insvalue as &$fv) {
                $fv = trim($fv);
            }
            array_unshift($insvalue, $this->key);
            $command = Html::encode('RPUSH ' . $this->key . ' ' . implode(' ', $insvalue));
            Redisman::getInstance()->executeCommand('RPUSH', $insvalue);
            break;
        case Redisman::REDIS_SET:
            $insvalue = explode("\r\n", $this->formatvalue);
            foreach ($insvalue as &$fv) {
                $fv = trim($fv);
            }
            array_unshift($insvalue, $this->key);
            $command = Html::encode('SADD ' . $this->key . ' ' . implode(' ', $insvalue));
            Redisman::getInstance()->executeCommand('SADD', $insvalue);
            break;
        }
        return $command;
    }

    /**
     * Set\remove key expiration
     */
    public function persist()
    {
        $event = new ModifyEvent();
        $event->key = $this->key;
        $event->connection = Redisman::getInstance()->getCurrentConn();
        $event->db = Redisman::getInstance()->getCurrentDb();
        if ($this->ttl == -1) {
            $event->operation = 'persist';
            $event->command = Html::encode('PERSIST ' . $this->key);
            Redisman::getInstance()->executeCommand('PERSIST', [$this->key]);
        } else {
            $event->operation = 'expire';
            $event->command = Html::encode('EXPIRE ' . $this->key . ' ' . $this->ttl);
            Redisman::getInstance()->executeCommand('EXPIRE', [$this->key, $this->ttl]);
        }
        $this->trigger(self::EVENT_AFTER_CHANGE, $event);
    }

    /**
     * Move key between databases
     */
    public function move()
    {
        $event = new ModifyEvent();
        $event->key = $this->key;
        $event->operation = 'move';
        $event->connection = Redisman::getInstance()->getCurrentConn();
        $event->db = Redisman::getInstance()->getCurrentDb();
        $event->command = Html::encode('MOVE ' . $this->key . ' ' . $this->db);
        Redisman::getInstance()->executeCommand('MOVE', [$this->key, $this->db]);
        $this->trigger(self::EVENT_AFTER_CHANGE, $event);
    }

    /**
     * Delete key
     */
    public function delete()
    {
        $event = new ModifyEvent();
        $event->key = $this->key;
        $event->operation = 'delete';
        $event->connection = Redisman::getInstance()->getCurrentConn();
        $event->db = Redisman::getInstance()->getCurrentDb();
        $event->command = Html::encode('DEL ' . $this->key);
        Redisman::getInstance()->executeCommand('DEL', [$this->key]);
        $this->trigger(self::EVENT_AFTER_CHANGE, $event);
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
        $key = Redisman::quoteValue($key);
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