<?php
use Zelenin\yii\SemanticUI\widgets\ActiveForm;
use Zelenin\yii\SemanticUI\Elements;
/**
 * @var \yii\web\View $this
 * @var \insolita\redisman\controllers\DefaultController $context
 * @var \insolita\redisman\RedismanModule $module
 */
$module=$this->context->module;

$model=new \insolita\redisman\models\ConnectionForm();
$model->connection=$module->getCurrentConn();
$model->db=$module->getCurrentDb();
?>
<?php $form = \Zelenin\yii\SemanticUI\widgets\ActiveForm::begin(
    [
        'id' => 'login-form', 'options' => ['class' => 'ui form attached fluid'],
        'enableClientValidation'=>true,
        'method'=>'post',
        'action'=>\yii\helpers\Url::to(['/redisman/default/switch'])
    ]
); ?>
<?= $form->errorSummary($model) ?>
    <div class="one">
        <?= $form->field($model, 'connection')->dropDownList($module->connectionList(),['id'=>'currentcon'])?>
    </div>
    <div class="one">

        <?= $form->field($model, 'db')->dropDownList($module->dbList(),['id'=>'currentdb'])?>
    </div>
<?= Elements::button(
    '<i class="sign in icon"></i>' . Yii::t('app', 'Login'), ['class' => 'green tiny', 'type' => 'submit','tag'=>'button']
) ?>
<?php ActiveForm::end(); ?>