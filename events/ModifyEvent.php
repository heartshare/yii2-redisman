<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 07.03.15
 * Time: 11:35
 */
namespace insolita\redisman\events;
use yii\base\Event;

/**
 * Class ModifyEvent
 *
 * @package insolita\redisman\events
 */
class ModifyEvent extends Event{

    /**
     * @var string
     */
    public $operation;
    /**
     * @var string
     */
    public $key;
    /**
     * @var int
     */
    public $db;
    /**
     * @var string ConnectionName
     */
    public $connection;
    /**
     * @var string log commands
     */
    public $command='';
} 