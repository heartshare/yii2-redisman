<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 07.03.15
 * Time: 11:35
 */
namespace insolita\redisman\events;
use yii\base\Event;
class ModifyEvent extends Event{

    public $operation;
    public $key;
    public $db;
    public $connection;
    public $command='';
} 