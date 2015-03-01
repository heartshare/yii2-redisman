<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 28.02.15
 * Time: 22:59
 */

namespace insolita\redisman\models;


use insolita\redisman\RedismanModule;
use yii\base\Model;
use yii\data\ArrayDataProvider;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\NotFoundHttpException;

/**
 * @property string  $pattern
 * @property array   $type
 * @property integer $perpage
 **/
class SearchModel extends Model
{
    public $pattern;
    public $type;
    public $perpage;
    public $encache;

    /**
     * @var \insolita\redisman\RedismanModule $module
     **/
    private $module;

    public function init()
    {
        parent::init();
        $this->module = \Yii::$app->getModule('redisman');
    }

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['type'], 'default', 'value' => ['string', 'set', 'hash', 'zset', 'list']],
            [['pattern'], 'default', 'value' => '*:*'],
            [['perpage'], 'default', 'value' => 20],
            ['encache', 'default', 'value' => 0],
            [['pattern'], 'trim'],
            [['encache'], 'boolean'],
            [['type'], 'typeValidatior'],
            [
                'pattern', 'filter', 'filter' => function ($val) {
                return addcslashes(str_replace("'", "\\'", $val), "\000\n\r\\\032");
            }
            ],
            ['pattern', 'string', 'min' => 1, 'max' => 300],
            ['perpage', 'integer', 'min' => 10, 'max' => 1000],
        ];
    }

    public function typeValidatior($attribute, $params)
    {
        if (!is_array($this->$attribute) or count($this->$attribute) > 5) {
            $this->addError($attribute, RedismanModule::t('redisman','Wrong  redis type - it must be array'));
            return false;
        } else {
            $val_rt = ['string', 'set', 'hash', 'zset', 'list'];
            foreach ($this->$attribute as $t) {
                if (!is_string($t) || !in_array($t, $val_rt)) {
                    $this->addError($attribute, RedismanModule::t('redisman','Wrong redis type'));
                    return false;
                }
            }
        }
        return true;
    }


    public function attributeLabels()
    {
        return [
            'pattern' => RedismanModule::t('redisman','Search pattern'),
            'type' => RedismanModule::t('redisman','Type'),
            'perpage' => RedismanModule::t('redisman','Page Size'),
            'encache' => RedismanModule::t('redisman','Enable query caching?'),
        ];
    }

    public function getSearchId()
    {
        return $this->pattern . ':' . implode('', $this->type) . ":" . $this->perpage . ":"
        . $this->module->getCurrentConn() . ":" . $this->module->getCurrentDb() . ':' . $this->module->greedySearch;
    }




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
            $conn = $this->module->getConnection();
            $queryScript = (!$this->module->greedySearch) ? $this->scriptBuilder($start, $end)
                : $this->scriptBuilderGreedy();
            $data = $conn->executeCommand('EVAL', [$queryScript, 0]);

        }
        if (!empty($data)) {
            $totalcount = array_pop($data);
            $allModels = [];
            foreach ($data as $i => $row) {
                $allModels[] = [
                    'id' => $start + $i, 'key' => $row[0], 'type' => $row[1], 'size' => $row[2], 'ttl' => $row[3]
                ];
            }
            if ($this->encache) {
                \Yii::$app->cache->set(
                    $this->getSearchId() . ':' . $page, $allModels, $this->module->queryCacheDuration
                );
            }
        } else {
            $allModels = [];
            $totalcount = 0;
        }

        return (!$this->module->greedySearch)
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

    public function storeFilter()
    {
        if ($this->validate()) {
            \Yii::$app->session->set('RedisManager_searchModel', $this->getAttributes());
            return true;
        } else {
            return false;
        }
    }

    public function restoreFilter()
    {
        if ($data = \Yii::$app->session->get('RedisManager_searchModel', null)) {
            $this->setAttributes($data);
        } else {

            $this->validate();
        }
    }

    public static function resetFilter()
    {
        \Yii::$app->session->set('RedisManager_searchModel', null);
    }


    public function typeCondBuilder()
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


    protected function scriptBuilder($start, $end)
    {
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
    local result = redis.call("SCAN", cursor, "match", "{$this->pattern}", "count", 50)
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

    local result = redis.call("KEYS", "{$this->pattern}")
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
        return ($this->module->searchMethod == 'SCAN') ? $scriptScan : $scriptKeys;
    }

    protected function scriptBuilderGreedy()
    {
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
    local result = redis.call("SCAN", cursor, "match", "{$this->pattern}", "count", 50)
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
    local result = redis.call("KEYS", "{$this->pattern}")
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
        return ($this->module->searchMethod == 'SCAN') ? $scriptScan : $scriptKeys;
    }
}