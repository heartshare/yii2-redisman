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
?>
<?php $form = \Zelenin\yii\SemanticUI\widgets\ActiveForm::begin(
    [
        'id' => 'login-form', 'options' => ['class' => 'ui form attached fluid'],
        'enableClientValidation'=>true,
        'method'=>'post',
        'action'=>\yii\helpers\Url::to(['/redisman/default/show'])
    ]
); ?>
<?= $form->errorSummary($model) ?>
    <div class="one">
        <?= $form->field($model, 'pattern')->textInput()->hint(RedismanModule::t('support redis patterns (*,?,[var])'))?>
    </div>
    <div class="one">

        <?= $form->field($model, 'type')->checkboxList([
                'string'=>RedismanModule::t('string'),
                'hash'=>RedismanModule::t('hash'),
                'list'=>RedismanModule::t('list'),
                'set'=>RedismanModule::t('set'),
                'zset'=>RedismanModule::t('zset')

            ])?>
    </div>
    <div class="one">
        <?= $form->field($model, 'perpage')->dropDownList([15=>15,30=>30,50=>50,100=>100,200=>200,500=>500])?>
    </div>

<?= Elements::button(
    '<i class="find icon"></i>' . Yii::t('app', 'Search'), ['class' => 'teal tiny', 'type' => 'submit','tag'=>'button']
) ?>
<?php ActiveForm::end(); ?>