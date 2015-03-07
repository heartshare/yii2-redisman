<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 17.08.14
 * Time: 11:19
 */

namespace insolita\redisman;


use insolita\redisman\components\NativeConnection;
use yii\base\ErrorException;
use yii\base\InvalidConfigException;
use yii\base\ModelEvent;
use yii\base\Module;

/**
 * Class Redisman
 *
 * @package insolita\redisman
 */
class Redisman extends Module
{
    const BEFORE_FLUSHBD='beforeFlushDB';


    const REDIS_STRING = 'string';
    const REDIS_LIST = 'list';
    const REDIS_HASH = 'hash';
    const REDIS_SET = 'set';
    const REDIS_ZSET = 'zset';
    const REDIS_NONE = 'none';

    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'insolita\redisman\controllers';

    /**
     * @var array the the internalization configuration for this module
     */
    public $i18n = [];

    public $layout = 'main';


    /**
     * @var array $connections - array of available redis connections
     **/

    public $connections;

    /**
     * @var string $defRedis - key of default redis connection (from array $connections)
     **/

    public $defRedis = null;

    /**
     * @var string $defPattern - default search pattern
     **/

    public $defPattern = '*:*';


    /**
     * @var int $queryCacheDuration - duration of search query cache (only if enable cache option in search interface)
     **/

    public $queryCacheDuration = 600;

    /**
     * @var string $searchMethod - May be SCAN or KEYS(not recommended for large Database)
     **/
    public $searchMethod = 'SCAN';

    /**
     * @var boolean $greedySearch - if true - all search results will be loaded, else - only for current page (but still  all keys will be scanned for correct pagination)
     **/
    public $greedySearch = false;

    /**
     * @var \yii\redis\Connection $_connect current redis connection
     **/
    private $_connect = null;

    /**
     * @var int $_dbCurrent selected database
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
    private $_totalDbCount = [];

    /**
     * @var array $_pattern - search pattern
     **/
    private $_pattern = null;


    /**
     * @var array
     */
    public static $types
        = array(
            'string' => 'REDIS_STRING',
            'set' => 'REDIS_SET',
            'list' => 'REDIS_LIST',
            'zset' => 'REDIS_ZSET',
            'hash' => 'REDIS_HASH'
        );

    /**
     * @var array
     */
    public static $convtypes
        = array(
            0 => 'none',
            1 => 'string',
            2 => 'set',
            3 => 'list',
            4 => 'zset',
            5 => 'hash'
        );

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
                self::t('redisman', 'Wrong module configuration! Please set array of available redis connections')
            );
        }

        if (empty($this->defRedis) or !in_array($this->defRedis, $this->connectionList())) {
            throw new InvalidConfigException(
                self::t('redisman', 'Wrong module configuration! Wrong configuration defRedis param')
            );
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
        $k = array_keys($this->connections);
        return array_combine($k, $k);
    }

    /**
     * @return array
     */
    public function dbList()
    {
        $dblist = [];
        for ($i = 0; $i < $this->_dbCount; $i++) {
            $dblist[$i] = 'Db №' . $i;
        }
        return $dblist;
    }

    /**
     * @return int
     */
    public function getDbCount()
    {
        return $this->_dbCount;
    }

    /**
     * @return int
     */
    public function getCurrentDb()
    {
        return $this->_dbCurrent;
    }

    /**
     * @return string
     */
    public function getCurrentConn()
    {
        return $this->_conCurrent;
    }

    /**
     * @return string
     */
    public function getCurrentName()
    {
        return ucfirst($this->_conCurrent) . ' db#' . $this->_dbCurrent . ' [' . $this->_connect->hostname . ']';
    }


    /**
     * @param bool $force
     * @param null $db
     *
     * @return object|\yii\redis\Connection
     * @throws InvalidConfigException
     */
    public function getConnection($force = false, $db = null)
    {

        if (!$this->_connect || $force) {
            if (!$this->_conCurrent) {
                $this->_conCurrent = $this->defRedis;
            }
            $this->_connect = \Yii::createObject($this->connections[$this->_conCurrent]);
            $this->_dbCount = $this->configGetDatabases();
            if (!is_null($db) && $db <= $this->_dbCount && $db != $this->_connect->database) {
                $this->executeCommand('SELECT', [$db]);
                //$this->_connect->select($db);
                $this->_dbCurrent = $db;
            } else {
                $this->_dbCurrent = $this->_connect->database;
            }

        }
        return $this->_connect;
    }


    /**
     * @param $name
     * @param $db
     *
     * @return \yii\redis\Connection
     * @throws ErrorException
     */
    public function setConnection($name, $db = null)
    {
        if (!isset($this->connections[$name])) {
            throw new ErrorException(self::t('redisman', 'Wrong redis connection name'));
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
    public function restoreFromSession()
    {
        $this->_conCurrent = \Yii::$app->session->get('RedisManager_conCurrent', $this->defRedis);
        $this->_dbCurrent = \Yii::$app->session->get('RedisManager_dbCurrent', 0);
        $this->_pattern = \Yii::$app->session->get('RedisManager_pattern', null);
    }

    /**
     * @param $pattern
     */
    public function setPattern($pattern)
    {
        $this->_pattern = $pattern;
        \Yii::$app->session->set('RedisManager_pattern', $pattern);
    }


    /**
     * @return array - formatted info about redis connection
     **/
    public function dbInfo()
    {
        $infoex = [];
        $section = 'Undefined';

        if ($this->_connect instanceof NativeConnection) {
            $sects = [
                'server', 'clients', 'memory', 'persistence', 'stats', 'cpu', 'commandstats', 'clusters', 'keyspace'
            ];
            foreach ($sects as $sect) {
                $info = $this->_connect->executeCommand('INFO', [strtoupper($sect)]);
                foreach ($info as $k => $v) {
                    $infoex[$sect][$k] = trim($v);
                }
            }


        } else {
            $info = $this->_connect->executeCommand('INFO', ['all']);
            $info = explode("\r\n", $info);
            foreach ($info as $line) {
                if (strpos($line, '#') !== false) {
                    $section = trim(str_replace('#', '', $line));
                } elseif (strpos($line, ':') !== false) {
                    list($key, $val) = explode(':', $line);
                    $infoex[$section][trim($key)] = trim($val);
                }
            }
        }


        return $infoex;
    }

    /**
     * @param $key
     *
     * @return string
     */
    public function type($key)
    {
        $type = $this->_connect->executeCommand('TYPE', [$key]);
        return is_string($type) ? $type : self::$convtypes[$type];
    }

    /**
     *
     */
    public function dbSave()
    {
        $this->_connect->executeCommand('BGSAVE');
    }

    /**
     *
     */
    public function dbFlush()
    {
        $event=new ModelEvent();
        $event->data=['db'=>$this->getCurrentDb(),'connectionName'=>$this->getCurrentConn()];
        if($this->trigger(self::BEFORE_FLUSHBD, $event)){
            $this->_connect->executeCommand('FLUSHDB');
        }
    }

    /**
     * @param       $command
     * @param array $params
     *
     * @return array|bool|null|string
     */
    public function executeCommand($command, $params = [])
    {
        if ($command == 'EVAL' && $this->_connect instanceof NativeConnection) {
            return $this->_connect->evaluate($params[0], [], 0);
        }
        return $this->_connect->executeCommand($command, $params);
    }

    /**
     * @return mixed
     */
    public function configGetDatabases()
    {
        if ($this->_connect instanceof NativeConnection) {
            return $this->_connect->executeCommand('CONFIG', ['GET', 'databases'])['databases'];
        } else {
            return $this->_connect->executeCommand('CONFIG', ['GET', 'databases'])[1];
        }
    }

    /**
     * @return array
     * @throws InvalidConfigException
     */
    public function totalDbCount()
    {
        if (!$this->_totalDbCount) {
            $cached = \Yii::$app->session->get('RedisManager_totalDbItem');
            if (is_array($cached)) {
                $this->_totalDbCount = $cached;
            } else {
                foreach ($this->connectionList() as $item) {
                    $cn = \Yii::createObject($this->connections[$item]);
                    if ($cn instanceof NativeConnection) {
                        $this->_totalDbCount[$item] = $cn->executeCommand('CONFIG', ['GET', 'databases']);
                    } else {
                        $this->_totalDbCount[$item] = $cn->executeCommand('CONFIG', ['GET', 'databases'])[1];
                    }
                    $cn->close();
                }
                \Yii::$app->session->set('RedisManager_totalDbItem', $this->_totalDbCount);
            }
        }
        return $this->_totalDbCount;
    }

    /**
     *
     */
    public function registerTranslations()
    {
        \Yii::setAlias('@redisman_messages', __DIR__ . '/messages/');
        \Yii::$app->i18n->translations['insolita/redisman/*'] = [
            'class' => 'yii\i18n\PhpMessageSource',
            'basePath' => \Yii::getAlias('@redisman_messages'),
            'sourceLanguage' => 'en',
            'fileMap' => [
                'insolita/redisman/redisman' => 'redisman.php',
                'insolita/redisman/app' => 'app.php',
            ],
        ];
    }

    /**
     * @param int $type
     *
     * @return string
     */
    public static function keyTyper($type)
    {
        if (isset(self::$types[$type])) {
            return self::t('redisman', self::$types[$type]);
        } else {
            return 'undefined';
        }
    }

    /**
     *
     * @param $key
     *
     * @return bool
     */
    public function i18nType($key)
    {
        return self::keyTyper($this->type($key));
    }


    /**
     * @param       $message
     * @param array $params
     * @param null  $language
     *
     * @return string
     */
    public static function t($category, $message, $params = [], $language = null)
    {
        //return \Yii::t('redisman', $message, $params, $language);
        return \Yii::t('insolita/redisman/' . $category, $message, $params, $language);
    }

    /**
     * @param $str
     *
     * @return string
     */
    public static function quoteValue($str)
    {
        if (!is_string($str) && !is_int($str)) {
            return $str;
        }
        $str = addcslashes($str, "\000\n\r\032");
        $squotes = substr_count($str, "'");
        $mquotes = substr_count($str, '"');
        $dslashes = substr_count($str, '\\\\');
        $sslahes = substr_count($str, '\\');
        if ($sslahes / 2 !== $dslashes) {
            $str = str_replace('\\\\', '{~dslash~}', $str);
            $str = str_replace('\\', '\\\\', $str);
            $str = str_replace('{~dslash~}', '\\\\\\\\', $str);
        }

        if ($mquotes && !$squotes) {
            return ($mquotes % 2 == 0) ? "'" . $str . "'" : "'" . str_replace('"', '\"', $str) . "'";
        } elseif (!$mquotes && $squotes) {
            return ($squotes % 2 == 0) ? '"' . $str . '"' : '"' . str_replace("'", "\'", $str) . '"';
        } elseif (!$squotes && !$mquotes) {
            return "'" . $str . "'";
        } else {
            $str = str_replace('"', '\"', $str);
            $str = str_replace("'", "\'", $str);
            return "'" . $str . "'";
        }

    }

} 