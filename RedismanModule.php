<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 17.08.14
 * Time: 11:19
 */

namespace insolita\redisman;


use yii\base\Module;

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
     * @var int $grouplistCacheDuration - Время кеширования списка груп
     */
    public $grouplistCacheDuration=3600;

     /**
     * @var array the the internalization configuration for this module
     */
    public $i18n = [];

    public function init()
    {
        parent::init();

        $this->registerTranslations();

    }
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
    public static function t($message, $params = [], $language = null)
    {
        //return \Yii::t('redisman', $message, $params, $language);
        return \Yii::t('insolita/modules/redisman/redisman', $message, $params, $language);
    }

} 