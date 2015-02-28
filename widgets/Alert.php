<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 28.02.15
 * Time: 12:54
 */

namespace insolita\redisman\widgets;


use yii\helpers\Html;
use Zelenin\yii\SemanticUI\Widget;

class Alert extends Widget
{
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_DANGER = 'danger';
    const TYPE_INFO = 'info';

    private $_classes;
    private $_keyparts;
    private $_icons;
    private $_titles;

    /**@var boolean - Show close button for alert* */
    public $closable = true;
    /**@var boolean - Encode flash messages?* */
    public $encode = true;
    /**@var boolean - Wrap message in <b> tag?* */
    public $bold = true;

    public $successIcon = '<i class="icon smile"></i>';
    public $errorIcon = '<i class="icon frown"></i>';
    public $warningIcon = '<i class="icon warning sign"></i>';
    public $infoIcon = '<i class="icon info sign"></i>';

    public $successTitle = 'Успешно!';
    public $errorTitle = 'Ошибка!';
    public $warningTitle = 'Внимание!';
    public $infoTitle = 'К сведению!';


    public function init()
    {
        $this->_classes = ['success' => 'green', 'error' => 'red', 'info' => 'blue', 'warning' => 'orange'];
        $this->_keyparts = array_keys($this->_classes);
        $this->_icons = [
            'success' => $this->successIcon,
            'error' => $this->errorIcon,
            'info' => $this->infoIcon,
            'warning' => $this->warningIcon
        ];

        $this->_titles = [
            'success' => $this->successTitle,
            'error' => $this->errorTitle,
            'info' => $this->infoTitle,
            'warning' => $this->warningTitle
        ];

    }

    public function run()
    {
        $allflash = \Yii::$app->session->getAllFlashes();
        $msg = '';
        foreach ($allflash as $key => $mess) {
            $fk = 'info';
            foreach ($this->_keyparts as $kp) {
                if (strpos($key, $kp) !== false) {
                    $fk = $kp;
                    break;
                }
            }

            Html::addCssClass($this->options, 'ui message');
            $mess = !$this->encode ? $mess : Html::encode($mess);
            $msg .= Html::tag(
                'div',
                $this->_icons[$fk]
                . (!$this->closable ? ''
                    : '<i class="close icon"></i>')
                . Html::tag(
                    'div',
                    ($this->_titles[$fk] ? Html::tag('div', $this->_titles[$fk], ['class' => 'header']) : '') . ' '
                    . (!$this->bold ? $mess : Html::tag('b', $mess)), ['class' => 'content']
                )

                , $this->options
            );
        }
        return $msg;
    }
} 