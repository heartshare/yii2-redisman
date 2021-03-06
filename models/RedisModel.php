<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 28.02.15
 * Time: 22:59
 */

namespace insolita\redisman\models;


use insolita\redisman\Redisman;
use yii\base\Event;
use yii\base\Model;
use yii\caching\TagDependency;
use yii\data\ArrayDataProvider;
use yii\helpers\ArrayHelper;

/**
 * Class RedisModel
 *
 * @package insolita\redisman\models
 */

class RedisModel extends Model
{
    /**
     * @var string $pattern - pattern for search
     */
    public $pattern;
    /**
     * @var array $type - array of searched redis-types
     */
    public $type;
    /**
     * @var integer $perpage
     */
    public $perpage;
    /**
     * @var boolean $encache
     */
    public $encache;

    public function init(){
        parent::init();
    }

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['type'], 'default', 'value' => array_keys(Redisman::$types)],
            [['pattern'], 'default', 'value' => Redisman::getInstance()->defPattern],
            [['perpage'], 'default', 'value' => 20],
            ['encache', 'default', 'value' => 0],
            [['pattern'], 'trim'],
            [['encache'], 'boolean'],
            [['type'], 'typeValidatior'],

            ['pattern', 'string', 'min' => 1, 'max' => 300],
            ['perpage', 'integer', 'min' => 10, 'max' => 1000],
        ];
    }

    /**
     * @param $attribute
     * @param $params
     *
     * @return bool
     */
    public function typeValidatior($attribute, $params)
    {
        if (!is_array($this->$attribute) or count($this->$attribute) > 5) {
            $this->addError($attribute, Redisman::t('redisman','Wrong  redis type - it must be array'));
            return false;
        } else {
            $val_rt = ['string', 'set', 'hash', 'zset', 'list'];
            foreach ($this->$attribute as $t) {
                if (!is_string($t) || !in_array($t, $val_rt)) {
                    $this->addError($attribute, Redisman::t('redisman','Wrong redis type'));
                    return false;
                }
            }
        }
        return true;
    }


    /**
     * @inherit
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'pattern' => Redisman::t('redisman','Search pattern'),
            'type' => Redisman::t('redisman','Type'),
            'perpage' => Redisman::t('redisman','Page Size'),
            'encache' => Redisman::t('redisman','Enable query caching?'),
        ];
    }

    /**
     * Generate search iedntification from params
     * @return string
     */
    protected function getSearchId()
    {
        return $this->pattern . ':' . implode('', $this->type) . ":" . $this->perpage . ":"
        . Redisman::getInstance()->getCurrentConn() . ":" . Redisman::getInstance()->getCurrentDb() . ':' . Redisman::getInstance()->greedySearch;
    }


    /**
     * Search redis keys
     * @param array $params
     *
     * @return PartialDataProvider|ArrayDataProvider
     */
    public function search($params)
    {
        $page = ArrayHelper::getValue($params, 'page', 1);
        $start = ($page - 1) * $this->perpage;
        $end = $start + $this->perpage;
        $data = null;
        if ($this->encache) {
            $data = \Yii::$app->cache->get($this->getSearchId() . ':' . $page, null);
        }
        if (!$data) {
            $queryScript = (!Redisman::getInstance()->greedySearch) ? $this->scriptBuilder($start, $end)
                : $this->scriptBuilderGreedy();
            $data = Redisman::getInstance()->executeCommand('EVAL', [$queryScript, 0]);

        }
        if (!empty($data)) {
            if ($this->encache) {
                $dep=new TagDependency();
                $dep->data='RedisManager_cachedep';
                 \Yii::$app->cache->set(
                    $this->getSearchId() . ':' . $page, $data, Redisman::getInstance()->queryCacheDuration, $dep
                );

            }
            $totalcount = array_pop($data);
            $allModels = [];
            foreach ($data as $i => $row) {
                $allModels[] = [
                    'id' => $start + $i, 'key' => $row[0], 'type' => $row[1], 'size' => $row[2], 'ttl' => $row[3]
                ];
            }

        } else {
            $allModels = [];
            $totalcount = 0;
        }

        return (!Redisman::getInstance()->greedySearch)
            ? new PartialDataProvider(
                [
                    'key' => 'id',
                    'allModels' => $allModels,
                    'totalCount' => $totalcount,
                    'sort' => [
                        'attributes' => ['key', 'type', 'size','ttl'],
                        'defaultOrder'=>['key'=>SORT_ASC]
                    ],
                    'pagination' => [
                        'totalCount' => $totalcount,
                        'pageSize' => $this->perpage,
                    ]
                ]
            )
            : new ArrayDataProvider(
                [
                    'key' => 'id',
                    'allModels' => $allModels,
                    'sort' => [
                        'attributes' => ['key', 'type', 'size','ttl'],
                        'defaultOrder'=>['key'=>SORT_ASC]
                    ],
                    'pagination' => [
                        'totalCount' => $totalcount,
                        'pageSize' => $this->perpage,
                    ]
                ]
            );
    }

    /**
     *  Save search filter
     * @return bool
     */
    public function storeFilter()
    {
        if ($this->validate()) {
            \Yii::$app->session->set('RedisManager_searchModel', $this->getAttributes());
            return true;
        } else {
            return false;
        }
    }

    /**
     * Restore search filter
     */
    public function restoreFilter()
    {
        if ($data = \Yii::$app->session->get('RedisManager_searchModel', null)) {
            $this->setAttributes($data);
        } else {

            $this->validate();
        }
    }

    /**
     * Flush search filter
     */
    public static function resetFilter()
    {
        \Yii::$app->session->set('RedisManager_searchModel', null);
    }


    /**
     *  build condition for lua script
     * @return string
     */
    protected function typeCondBuilder()
    {
        $typecond = "";
        if (count($this->type) == 5) {
            $typecond .= "1==1";
        } else {
            $first = true;
            foreach ($this->type as $t) {
                if ($first) {
                    $typecond .= 'tp == "' . $t . '"';
                } else {
                    $typecond .= ' or tp == "' . $t . '"';
                }
                $first = false;
            }
        }
        return $typecond;
    }


    /**
     *  prepare lua search script
     * @param int $start
     * @param int $end
     *
     * @return string
     */
    protected function scriptBuilder($start, $end)
    {
        $pattern=Redisman::quoteValue($this->pattern);
        $typecond = $this->typeCondBuilder();
        $scriptScan
            = <<<EOF
local all_keys = {};
local keys = {};
local done = false;
local cursor = "0"
local count=0;
local size=0
local tp
repeat
    local result = redis.call("SCAN", cursor, "match", $pattern, "count", 50)
    cursor = result[1];
    keys = result[2];
    for i, key in ipairs(keys) do
        tp=redis.call("TYPE", key)["ok"]
        if count>=$start and count<$end then
           if $typecond then
               if tp == "string" then
                   size=redis.call("STRLEN", key)
                elseif tp == "hash" then
                    size=redis.call("HLEN", key)
                elseif tp == "list" then
                    size=redis.call("LLEN", key)
                elseif tp == "set" then
                    size=redis.call("SCARD", key)
                elseif tp == "zset" then
                    size=redis.call("ZCARD", key)
                else
                    size=9999
                end
               all_keys[#all_keys+1] = {key, tp, size, redis.call("TTL", key)};
           end
        end
        if $typecond then
           count=count+1
        end
    end
    if cursor == "0" then
        done = true;
    end
until done
all_keys[#all_keys+1]=count;
return all_keys;
EOF;

        $scriptKeys
            = <<<EOF
local all_keys = {};
local count=0;
local size=0
local tp

    local result = redis.call("KEYS", $pattern)
    for i, key in ipairs(result) do
        tp=redis.call("TYPE", key)["ok"]
        if count>=$start and count<$end then
           if $typecond then
               if tp == "string" then
                   size=redis.call("STRLEN", key)
                elseif tp == "hash" then
                    size=redis.call("HLEN", key)
                elseif tp == "list" then
                    size=redis.call("LLEN", key)
                elseif tp == "set" then
                    size=redis.call("SCARD", key)
                elseif tp == "zset" then
                    size=redis.call("ZCARD", key)
                else
                    size=9999
                end
               all_keys[#all_keys+1] = {key, tp, size, redis.call("TTL", key)};
           end
        end
        if $typecond then
           count=count+1
        end
    end
all_keys[#all_keys+1]=count;
return all_keys;
EOF;
        return (Redisman::getInstance()->searchMethod == 'SCAN') ? $scriptScan : $scriptKeys;
    }

    /**
     * Prepare lua search script for greedy search type
     * @return string
     */
    protected function scriptBuilderGreedy()
    {
        $pattern=Redisman::quoteValue($this->pattern);
        $typecond = $this->typeCondBuilder();
        $scriptScan
            = <<<EOF
local all_keys = {};
local keys = {};
local done = false;
local cursor = "0"
local count=0;
local size=0
local tp
repeat
    local result = redis.call("SCAN", cursor, "match", $pattern, "count", 50)
    cursor = result[1];
    keys = result[2];
    for i, key in ipairs(keys) do
        tp=redis.call("TYPE", key)["ok"]
           if $typecond then
               if tp == "string" then
                   size=redis.call("STRLEN", key)
                elseif tp == "hash" then
                    size=redis.call("HLEN", key)
                elseif tp == "list" then
                    size=redis.call("LLEN", key)
                elseif tp == "set" then
                    size=redis.call("SCARD", key)
                elseif tp == "zset" then
                    size=redis.call("ZCARD", key)
                else
                    size=9999
                end
               all_keys[#all_keys+1] = {key, tp, size, redis.call("TTL", key)};
           end
        if $typecond then
           count=count+1
        end
    end
    if cursor == "0" then
        done = true;
    end
until done
all_keys[#all_keys+1]=count;
return all_keys;
EOF;

$scriptKeys= <<<EOF
local all_keys = {};
local count=0;
local size=0
local tp
    local result = redis.call("KEYS", $pattern)
    for i, key in ipairs(result) do
        tp=redis.call("TYPE", key)["ok"]
           if $typecond then
               if tp == "string" then
                   size=redis.call("STRLEN", key)
                elseif tp == "hash" then
                    size=redis.call("HLEN", key)
                elseif tp == "list" then
                    size=redis.call("LLEN", key)
                elseif tp == "set" then
                    size=redis.call("SCARD", key)
                elseif tp == "zset" then
                    size=redis.call("ZCARD", key)
                else
                    size=9999
                end
               all_keys[#all_keys+1] = {key, tp, size, redis.call("TTL", key)};
           end
       if $typecond then
           count=count+1
        end
    end
all_keys[#all_keys+1]=count;
return all_keys;
EOF;
        return (Redisman::getInstance()->searchMethod == 'SCAN') ? $scriptScan : $scriptKeys;
    }
}