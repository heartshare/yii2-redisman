<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 28.02.15
 * Time: 10:25
 */

namespace insolita\redisman\models;


use insolita\redisman\Redisman;
use yii\base\Model;

/**
 * Class SubscribeForm
 *
 * @package insolita\redisman\models
 */
class SubscribeForm extends Model
{
    public $channel;

    /**
     * @var string $connection
     */
    private  $connection;
    /**
     * @var integer $db
     */
    private $db;

    /**
     * @var \insolita\redisman\Redisman $module
     **/
    private $module;

    /**
     * @inherit
     */
    public function init()
    {
        parent::init();
        $this->module = \Yii::$app->getModule('redisman');
    }

    /**
     * @inherit
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['channel'], 'required']
        ];
    }

    /**
     * @inherit
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'channel' => Redisman::t('redisman', 'Channel'),
        ];
    }



} 