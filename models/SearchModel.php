<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 28.02.15
 * Time: 22:59
 */

namespace insolita\redisman\models;


use insolita\redisman\RedismanModule;
use yii\base\Model;

/**
 * @property string  $pattern
 * @property array   $type
 * @property integer $perpage
 **/
class SearchModel extends Model
{
    public $pattern;
    public $type;
    public $perpage;

    /**
     * @var \insolita\redisman\RedismanModule $module
     **/
    private $module;

    public function init()
    {
        parent::init();
        $this->module = \Yii::$app->getModule('redisman');
    }

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['type'], 'default', 'value' => ['string', 'set', 'hash', 'zset', 'list']],
            [['pattern'], 'default', 'value' => '*:*'],
            [['perpage'], 'default', 'value' => 20],
            [['pattern'], 'trim'],
            [['type'], 'typeValidatior'],
            [
                'pattern', 'filter', 'filter'=>function ($val) {
                return "'" . addcslashes(str_replace("'", "\\'", $val), "\000\n\r\\\032") . "'";
            }
            ],
            ['pattern', 'string', 'min' => 1, 'max' => 300],
            ['perpage', 'integer', 'min' => 10, 'max' => 1000],
        ];
    }

    public function typeValidatior($attribute, $params)
    {
        if (!is_array($this->$attribute) or count($this->$attribute) > 5) {
            $this->addError($attribute, RedismanModule::t('Wrong  redis type - it must be array'));
            return false;
        } else {
            $val_rt = ['string', 'set', 'hash', 'zset', 'list'];
            foreach ($this->$attribute as $t) {
                if (!is_string($t) || !in_array($t, $val_rt)) {
                    $this->addError($attribute, RedismanModule::t('Wrong redis type'));
                    return false;
                }
            }
        }
        return true;
    }


    public function attributeLabels()
    {
        return [
            'pattern' => RedismanModule::t('Search pattern'),
            'type' => RedismanModule::t('Type'),
            'perpage' => RedismanModule::t('Per page'),
        ];
    }

    public function search()
    {

    }

    public function storeFilter(){
        if($this->validate()){
            \Yii::$app->session->set('RedisManager_searchModel', $this->getAttributes());
        }else{
            return $this->getErrors();
        }
    }

    public function restoreFilter(){
        if($data=\Yii::$app->session->get('RedisManager_searchModel',null)){
            $this->setAttributes($data);
        }else{
            $this->setAttributes([]);
            $this->validate();
        }
    }

    public  function typeCondBuiler()
    {
        $typecond = "";
        if (count($this->type) == 5) {
            $typecond .= "1==1";
        } else {
            $first = true;
            foreach ($this->type as $t) {
                if ($first) {
                    $typecond .= 'tp == "' . $t . '"';
                } else {
                    $typecond .= ' or tp == "' . $t . '"';
                }
                $first = false;
            }
        }
        return $typecond;
    }

    protected function scriptBuilder()
    {

    }
} 