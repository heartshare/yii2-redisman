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
use yii\caching\TagDependency;

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
     * @var string $groupDelimiter - разделитель ключа по группам
     */
    public $groupDelimiter = ':';
    /**
     * @var int|string $grouplistCacheDuration - Время кеширования списка групп (можно указать session для кеширования в сессии)
     */
    public $grouplistCacheDuration = 3600;

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
        $this->getConnection();
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
            $this->_connect = $this->getConnection(true, (int)$db);
            \Yii::$app->session->set('RedisManager_conCurrent', $this->_conCurrent);
            \Yii::$app->session->set('RedisManager_dbCurrent', $this->_dbCurrent);
            return $this->_connect;
        }
    }

    public function restoreFromSession(){
         $this->_conCurrent=\Yii::$app->session->get('RedisManager_conCurrent', $this->defRedis);
         $this->_dbCurrent=\Yii::$app->session->get('RedisManager_dbCurrent', 0);
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