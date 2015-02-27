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


    /**
     * @var string $groupDelimiter - разделитель ключа по группам
     */
    public $groupDelimiter = ':';
    /**
     * @var int $grouplistCacheDuration - Время кеширования списка групп
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
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        $this->registerTranslations();

        if (empty($this->connections)) {
            throw new InvalidConfigException(
                self::t('Wrong module configuration! Please set array of available redis connections')
            );
        }

        if (empty($this->defRedis) or !in_array($this->defRedis, $this->connectionList())) {
            throw new InvalidConfigException(self::t('Wrong module configuration! Wrong configuration defRedis param'));
        }

    }

    /**
     * @return array
     */
    public function connectionList()
    {
        return array_keys($this->connections);
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
     * @return \yii\redis\Connection
     **/
    public function getConnection($force = false, $db = null)
    {

        if (!$this->_connect || $force) {
            if (!$this->_conCurrent) {
                $this->_conCurrent = $this->defRedis;
            }
            $this->_connect = \Yii::createObject($this->connections[$this->_conCurrent]);
            $this->_dbCount = $this->_connect->executeCommand('CONFIG',['GET', 'databases']);
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
            return $this->_connect;
        }
    }

    /**
     * @return array - formatted info about redis connection
     **/
    public function dbInfo(){
        $info=$this->_connect->info();

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