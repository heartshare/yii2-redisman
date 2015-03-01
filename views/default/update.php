<?php
use yii\helpers\Html;
use Zelenin\yii\SemanticUI\widgets\ActiveForm;

/**
 * @var \yii\web\View $this
 * @var \insolita\redisman\controllers\DefaultController $context
 * @var \insolita\redisman\RedismanModule $module
 * @var string $key
 * @var array $data
 */
$module=$this->context->module;
$this->title=$module->getCurrentName();
 ?>
<div class="ui blue segment">
    <h1 class="ui header"> <?=$module->getCurrentName()?>
        <div class="sub header"><?=Yii::t('app', 'Update').' '.Html::encode($key)?></div>
    </h1>

    <?php
    echo \yii\helpers\VarDumper::dumpAsString($data);
    ?>
</div>