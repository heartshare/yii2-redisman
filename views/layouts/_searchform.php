<?php
use Zelenin\yii\SemanticUI\widgets\ActiveForm;
use Zelenin\yii\SemanticUI\Elements;
use \insolita\redisman\RedismanModule;
/**
 * @var \yii\web\View $this
 * @var \insolita\redisman\controllers\DefaultController $context
 * @var \insolita\redisman\RedismanModule $module
 */
$module=$this->context->module;

$model=new \insolita\redisman\models\SearchModel();
$model->restoreFilter();
?>
<?php $form = \Zelenin\yii\SemanticUI\widgets\ActiveForm::begin(
    [
        'id' => 'login-form', 'options' => ['class' => 'ui form attached fluid'],
        'enableClientValidation'=>true,
        'method'=>'post',
        'action'=>\yii\helpers\Url::to(['/redisman/default/search'])
    ]
); ?>
<?= $form->errorSummary($model) ?>
    <div class="one">
        <?= $form->field($model, 'pattern')->textInput()->hint(RedismanModule::t('redisman','support redis patterns (*,?,[var])'))?>
    </div>
    <div class="one">

        <?= $form->field($model, 'type')->checkboxList([
                'string'=>RedismanModule::t('redisman','string'),
                'hash'=>RedismanModule::t('redisman','hash'),
                'list'=>RedismanModule::t('redisman','list'),
                'set'=>RedismanModule::t('redisman','set'),
                'zset'=>RedismanModule::t('redisman','zset')

            ])?>
    </div>
    <div class="one">
        <?= $form->field($model, 'perpage')->dropDownList([20=>20,30=>30,50=>50,100=>100,200=>200,500=>500])?>
    </div>
    <div class="one">
        <?= $form->field($model, 'encache')->checkbox([])?>
    </div><br/>
<div class="one right aligned">
<?= Elements::button(
    '<i class="find icon"></i>' . Yii::t('app', 'Search'), ['class' => 'teal circular right ui aligned', 'type' => 'submit','tag'=>'button']
) ?></div>
<?php ActiveForm::end(); ?>