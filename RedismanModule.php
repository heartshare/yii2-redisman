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
    public function init(){

    }
} 