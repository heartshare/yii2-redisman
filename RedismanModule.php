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
        $this->initI18N();

    }
    public function initI18N()
    {
        \Yii::setAlias('@redisman', dirname(__FILE__));
        if (empty($this->i18n)) {
            $this->i18n = [
                'class' => 'yii\i18n\PhpMessageSource',
                'basePath' => '@redisman/messages',
                'forceTranslation' => false
            ];
        }
        \Yii::$app->i18n->translations['redisman'] = $this->i18n;
    }
} 