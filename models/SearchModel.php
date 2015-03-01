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
            ['encache', 'value' => 0],
            [['pattern'], 'trim'],
            ['encache', 'in','range' => [0,1]],
            [['type'], 'typeValidatior'],
            [
                'pattern', 'filter', 'filter'=>function ($val) {
                return "'" . addcslashes(str_replace("'", "\\'", $val), "\000\n\r\\\032") . "'";
            }
            ],
            ['pattern', 'string', 'min' => 1, 'max' => 300],
            ['perpage', 'integer', 'min' => 10, 'max' => 1000],
        ];
    }

    public function typeValidatior($attribute, $params)
    {
        if (!is_array($this->$attribute) or count($this->$attribute) > 5) {
            $this->addError($attribute, RedismanModule::t('Wrong  redis type - it must be array'));
            return false;
        } else {
            $val_rt = ['string', 'set', 'hash', 'zset', 'list'];
            foreach ($this->$attribute as $t) {
                if (!is_string($t) || !in_array($t, $val_rt)) {
                    $this->addError($attribute, RedismanModule::t('Wrong redis type'));
                    return false;
                }
            }
        }
        return true;
    }


    public function attributeLabels()
    {
        return [
            'pattern' => RedismanModule::t('Search pattern'),
            'type' => RedismanModule::t('Type'),
            'perpage' => RedismanModule::t('Per page'),
        ];
    }

    public function getSearchId(){
        return $this->pattern.':'.implode('',$this->type).":".$this->perpage.":".$this->module->getCurrentConn().":".$this->module->getCurrentDb();
    }

    public function search($params)
    {
       $page=ArrayHelper::getValue($params,'page',0);
       $offset=$page*$this->perpage+1;

        $data=null;
        if($this->encache){
            $data=\Yii::$app->cache->get($this->getSearchId().':'.$page, null);
        }
        if(!$data){
            $conn=$this->module->getConnection();
            $queryScript=$this->scriptBuilder($page, $offset);
            $data=$conn->executeCommand('EVAL', [$queryScript,0]);
        }
        if(!empty($data)){
            $totalcount=array_pop($data);
            $allModels=[];
            foreach($data as $i=>$row){
                $allModels[]=['id'=>$i,'key'=>$row[0],'type'=>$row[1],'size'=>$row[2],'ttl'=>$row[3]];
            }
            if($this->encache){
                $data=\Yii::$app->cache->set($this->getSearchId().':'.$page, $allModels);
            }
        }else{
            $allModels=[];
        }


       return new ArrayDataProvider([
               'key'=>'id',
               'allModels'=>$allModels
           ]);
    }

    public function storeFilter(){
        if($this->validate()){
            \Yii::$app->session->set('RedisManager_searchModel', $this->getAttributes());
            return true;
        }else{
            return false;
        }
    }

    public function restoreFilter(){
        if($data=\Yii::$app->session->get('RedisManager_searchModel',null)){
            $this->setAttributes($data);
        }else{
            $this->setAttributes([]);
            $this->validate();
        }
    }

    public  function typeCondBuiler()
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

    protected function scriptBuilder($page, $offset)
    {
        $typecond=$this->typeCondBuiler();
        $script=<<<EOF
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
        if #all_keys<{$this->perpage} then
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
        return $script;
    }
} 