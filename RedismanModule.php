<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 17.08.14
 * Time: 11:19
 */

namespace insolita\redisman;


use yii\base\ErrorException;
use yii\base\InvalidConfigException;
use yii\base\Module;

/**
 * Class RedismanModule
 *
 * @package insolita\redisman
 */
class RedismanModule extends Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'insolita\redisman\controllers';

    /**
     * @var array the the internalization configuration for this module
     */
    public $i18n = [];

    public $layout='main';


    /**
     * @var array $connections - array of available redis connections
     **/

    public $connections;

    /**
     * @var string $defRedis - key of default redis connection (from array $connections)
     **/

    public $defRedis = null;

    /**
     * @var \yii\redis\Connection $_connect current redis connection
     **/
    private $_connect = null;

    /**
     * @var int $_dbCurrent  selected database
     **/
    private $_dbCurrent = 0;

    /**
     * @var string $_conCurrent current redis connection key
     **/
    private $_conCurrent = null;

    /**
     * @var int $_dbCount count allowed databases
     **/
    private $_dbCount = null;

    /**
     * @var array $_totalDbCount session_cached counters for all connections
     **/
    private $_totalDbCount=[];

    /**
     * @var array $_pattern - search pattern
     **/
    private $_pattern=null;


    /**
     * @var array
     */
    public static $types=array(
        \Redis::REDIS_STRING=>'REDIS_STRING',
        \Redis::REDIS_SET=>'REDIS_SET',
        \Redis::REDIS_LIST=>'REDIS_LIST',
        \Redis::REDIS_ZSET=>'REDIS_ZSET',
        \Redis::REDIS_HASH=>'REDIS_HASH');

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        \Yii::setAlias('@redisman', __DIR__);
        $this->registerTranslations();

        if (empty($this->connections)) {
            throw new InvalidConfigException(
                self::t('Wrong module configuration! Please set array of available redis connections')
            );
        }

        if (empty($this->defRedis) or !in_array($this->defRedis, $this->connectionList())) {
            throw new InvalidConfigException(self::t('Wrong module configuration! Wrong configuration defRedis param'));
        }
        $this->restoreFromSession();
        $this->getConnection(false, $this->_dbCurrent);
        $this->totalDbCount();
    }

    /**
     * @return array
     */
    public function connectionList()
    {
        $k= array_keys($this->connections);
        return array_combine($k,$k);
    }

    /**
     * @return array
     */
    public function dbList()
    {
        $dblist=[];
        for($i=0;$i<$this->_dbCount;$i++){
            $dblist[$i]='Db №'.$i;
        }
        return $dblist;
    }

    /**
     * @return int
     */
    public function getDbCount(){
        return $this->_dbCount;
    }

    /**
     * @return int
     */
    public function getCurrentDb(){
        return $this->_dbCurrent;
    }

    /**
     * @return string
     */
    public function getCurrentConn(){
        return $this->_conCurrent;
    }

    /**
     * @return string
     */
    public function getCurrentName(){
        return ucfirst($this->_conCurrent).' db#'.$this->_dbCurrent.' ['.$this->_connect->hostname.']';
    }

    /**
     * @return \yii\redis\Connection
     **/
    public function getConnection($force = false, $db = null)
    {

        if (!$this->_connect || $force) {
            if (!$this->_conCurrent) {
                $this->_conCurrent = $this->defRedis;
            }
            $this->_connect = \Yii::createObject($this->connections[$this->_conCurrent]);
            $this->_dbCount = $this->_connect->executeCommand('CONFIG',['GET', 'databases'])[1];
            if(!is_null($db) && $db<=$this->_dbCount && $db!=$this->_connect->database){
                $this->_connect->select($db);
                $this->_dbCurrent=$db;
            }else{
                $this->_dbCurrent=$this->_connect->database;
            }

        }
        return $this->_connect;
    }


    /**
     * @param $name
     * @param $db
     * @return \yii\redis\Connection
     * @throws ErrorException
     */
    public function setConnection($name, $db=null)
    {
        if (!isset($this->connections[$name])) {
            throw new ErrorException(self::t('Wrong redis connection name'));
        } else {
            $this->_conCurrent = $name;
            $this->_connect = $this->getConnection(true, $db);
            \Yii::$app->session->set('RedisManager_conCurrent', $this->_conCurrent);
            \Yii::$app->session->set('RedisManager_dbCurrent', $this->_dbCurrent);
            return $this->_connect;
        }
    }

    /**
     *
     */
    public function restoreFromSession(){
         $this->_conCurrent=\Yii::$app->session->get('RedisManager_conCurrent', $this->defRedis);
         $this->_dbCurrent=\Yii::$app->session->get('RedisManager_dbCurrent', 0);
         $this->_pattern=\Yii::$app->session->get('RedisManager_pattern', null);
    }

    /**
     * @param $pattern
     */
    public function setPattern($pattern){
        $this->_pattern=$pattern;
        \Yii::$app->session->set('RedisManager_pattern', $pattern);
    }

    /**
     * @return mixed
     */
    public function searchKeys(){
        $keys=$this->_rconn->keys($this->_pattern);
        return $keys;
    }

    /**
     * @return array - formatted info about redis connection
     **/
    public function dbInfo(){
        $info=$this->_connect->info('all');
        $info=explode("\r\n", $info);
        $infoex=[];
        $section='Undefined';
        foreach($info as $line){
            if(strpos($line, '#')!==false){
                $section=trim(str_replace('#','',$line));
            }elseif(strpos($line, ':')!==false){
                list($key, $val)=explode(':',$line);
                $infoex[$section][trim($key)]=trim($val);
            }
        }
        return $infoex;
    }

    /**
     * @return array
     * @throws InvalidConfigException
     */
    public function totalDbCount(){
        if(!$this->_totalDbCount){
            $cached=\Yii::$app->session->get('RedisManager_totalDbItem');
            if(is_array($cached)){
                $this->_totalDbCount=$cached;
            }else{
                foreach($this->connectionList() as $item){
                    $cn = \Yii::createObject($this->connections[$item]);
                    $this->_totalDbCount[$item] = $cn->executeCommand('CONFIG',['GET', 'databases'])[1];
                    $cn->close();
                }
                \Yii::$app->session->set('RedisManager_totalDbItem',$this->_totalDbCount);
            }
        }
        return $this->_totalDbCount;
    }

    /**
     *
     */
    public function registerTranslations()
    {
        \Yii::setAlias('@redisman_messages', __DIR__ . '/messages');
        \Yii::$app->i18n->translations['insolita/modules/redisman/*'] = [
            'class' => 'yii\i18n\PhpMessageSource',
            'basePath' => '@redisman_messages',
            'fileMap' => [
                'insolita/modules/redisman/redisman' => 'redisman.php'
            ],
        ];
    }

    /**
     * @param int $type
     *
     * @return bool
     */
    public static function keyTyper($type){
        if(isset(self::$types[$type])){
            return self::t(self::$types[$type]);
        }else{
            return false;
        }
    }

    public function getKeyType($key){
        $type=$this->_connect->type($key);
        return self::keyTyper($type);
    }
    /**
     * @param $key
     *
     * @return bool
     */
    public function getKeyVal($key){
        $type=$this->_connect->type($key);
        if($type==\Redis::REDIS_STRING){
            return $this->_connect->get($key);
        }elseif($type==\Redis::REDIS_HASH){
            return $this->_connect->hgetall($key);
        }elseif($type==\Redis::REDIS_ZSET){
            return $this->_connect->zrevrange($key,0,-1);
        }elseif($type==\Redis::REDIS_SET){
            return $this->_connect->smembers($key);
        }elseif($type==\Redis::REDIS_LIST){
            return $this->_connect->lrange($key,0,-1);
        }else{
            return false;
        }
    }

    public function addKey($type,$key,$value){
        if($type==\Redis::REDIS_STRING){
            $this->_connect->set($key,$value);
        }elseif($type==\Redis::REDIS_LIST){
            if(is_string($value)){
                $this->_connect->rpush($key,$value);
            }elseif(is_array($value)){
                foreach($value as $item){
                    if(is_string($item)){
                        $this->_connect->rpush($key,$item);
                    }
                }
            }

        }elseif($type==\Redis::REDIS_SET){
            $this->_connect->sadd($key,$value);
        }elseif($type==\Redis::REDIS_ZSET){
            $this->_connect->zadd($key,$value);
        }elseif($type==\Redis::REDIS_HASH){

            $this->_connect->hmset($key,$value);
        }
    }
    /*public function keyDataProvider($group='*'){
        if(($rediskeys=Yii::app()->cache->get('rediskeys_'.$group))==false){
            $rediskeys=array();
            $group=($group!='*')?$group.'*':'*';
            $keys=$this->getAllKeys($group);
            if(!empty($keys) && is_array($keys)){
                natcasesort($keys);
                foreach($keys as $key){
                    $rediskeys[]=array('id'=>$key,'type'=>$this->getType($key),'ttl'=>$this->getTtl($key));
                }
            }
            Yii::app()->cache->set('rediskeys',serialize($rediskeys),7200);
        }else{
            $rediskeys=unserialize($rediskeys);
        }
        $dp=new CArrayDataProvider($rediskeys,array('id'=>'id',
                'pagination'=>array('pageSize'=>300)));
        return $dp;
    }*/

    /**
     * @param       $message
     * @param array $params
     * @param null  $language
     *
     * @return string
     */
    public static function t($message, $params = [], $language = null)
    {
        //return \Yii::t('redisman', $message, $params, $language);
        return \Yii::t('insolita/modules/redisman/redisman', $message, $params, $language);
    }

} 