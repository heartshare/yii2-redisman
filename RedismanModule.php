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
class RedismanModule extends Module {
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'insolita\redisman\controllers';
    /**
     * @var string $groupDelimiter - разделитель ключа по группам
     */
    public $groupDelimiter=':';
    /**
     * @var int $grouplistCacheDuration - Время кеширования списка групп
     */
    public $grouplistCacheDuration=3600;

    /**
     * @var array $redises - array of available redis connections
    **/

    public $redises;

    /**
     * @var string $defRedis - key of default redis connection (from array $redises)
     **/

    public $defRedis=null;

     /**
     * @var array the the internalization configuration for this module
     */
    public $i18n = [];

    /**
     * @var \yii\redis\Connection $_connect current redis connect
    **/
    private $_connect=null;

    /**
     * @var string $_current current redis connection key
     **/
    private $_current=null;

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        $this->registerTranslations();

        if(empty($this->redises)){
            throw new InvalidConfigException(self::t('Wrong module configuration! Please set array of available redis connections'));
        }

        if(empty($this->defRedis) or !in_array($this->defRedis, $this->connectionList())){
            throw new InvalidConfigException(self::t('Wrong module configuration! Wrong configuration defRedis param'));
        }

    }

    /**
     * @return array
     */
    public function connectionList(){
        return array_keys($this->redises);
    }

    public function getConnection(){

        if(!$this->_connect){
            if(!$this->_current){
                $this->_current=$this->defRedis;
            }

        }
        return $this->_connect;
    }

    public function setConnection($name){
        if(!isset($this->redises[$name])){
            throw new ErrorException(self::t('Wrong redis connection name'));
        }else{
            $this->_current=$name;
            $this->_connect=$this->getConnection();
        }
    }

    /**
     *
     */
    public function registerTranslations()
    {
       \Yii::setAlias('@redisman_messages',__DIR__.'/messages');
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