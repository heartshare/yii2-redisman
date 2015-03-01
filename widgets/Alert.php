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

/**
 * Class Alert
 *
 * @package insolita\redisman\widgets
 */
class Alert extends Widget
{

    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_DANGER = 'danger';
    const TYPE_INFO = 'info';

    /**
     * @var array
     */
    private $_classes;
    /**
     * @var array
     */
    private $_keyparts;
    /**
     * @var array
     */
    private $_icons;
    /**
     * @var array
     */
    private $_titles;


    /**
     * @var boolean - Encode flash messages?*
     */
    public $encode = true;

    /**
     * @var boolean - Wrap message in <b> tag?*
     */
    public $bold = true;

    /**
     * @var string
     */
    public $successIcon = '<i class="icon smile"></i>';
    /**
     * @var string
     */
    public $errorIcon = '<i class="icon frown"></i>';
    /**
     * @var string
     */
    public $warningIcon = '<i class="icon warning sign"></i>';
    /**
     * @var string
     */
    public $infoIcon = '<i class="icon info sign"></i>';

    /**
     * @var string
     */
    public $successTitle = 'Success!';
    /**
     * @var string
     */
    public $errorTitle = 'Error!';
    /**
     * @var string
     */
    public $warningTitle = 'Warning!';
    /**
     * @var string
     */
    public $infoTitle = 'Attention!';


    /**
     *
     */
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

    /**
     * @return string
     */
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

            Html::addCssClass($this->options, 'ui icon message ' . $this->_classes[$fk]);
            $mess = !$this->encode ? $mess : Html::encode($mess);
            $msg .= Html::tag(
                'div',
                $this->_icons[$fk]
                . Html::tag(
                    'div',
                    Html::tag('div', $this->_titles[$fk], ['class' => 'header']) . ' '
                    . (!$this->bold ? $mess : Html::tag('b', $mess)), ['class' => 'content']
                )

                , $this->options
            );
        }
        return $msg;
    }
} 