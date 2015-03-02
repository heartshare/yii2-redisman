<?php
use insolita\redisman\RedismanModule;

/**
 * @var \yii\web\View $this
 * @var \insolita\redisman\controllers\DefaultController $context
 * @var \insolita\redisman\RedismanModule $module
 * @var \insolita\redisman\models\RedisItem $model
 */
$module = $this->context->module;
$this->title = $module->getCurrentName();
?>

<div class="ui green pointed segment">
    <h1 class="ui header">
        <div class="sub header "><i class="icon plus circle"></i><?= RedismanModule::t(
                'redisman', 'Add key - {0}', $model->type
            ) ?></div>
    </h1>
    <div class="ui two column grid">
        <div class="column">
            <div class="ui raised segment">
                <a class="ui ribbon teal label"><?= RedismanModule::t('redisman', 'Fill form') ?></a>
                <span><?= RedismanModule::t('redisman', 'Fields with * required') ?></span>
                <?php $form = new \Zelenin\yii\SemanticUI\widgets\ActiveForm(
                    [
                        'action' => ['/redisman/module/create']
                    ]
                )?>
                <div class="one">
                    <?php echo $form->field($model, 'key')->textInput([]); ?>
                </div>
                <div class="one">
                    <?php
                    if($data->type==RedismanModule::REDIS_STRING){
                        echo $form->field($model, 'value')->widget(
                            \lav45\aceEditor\AceEditorWidget::className(), [

                                'mode' => 'text',
                                'fontSize' => 15,
                                'height' => 200,

                            ]
                        );
                    }else{
                        echo $form->field($model, 'formatvalue')->widget(
                            \lav45\aceEditor\AceEditorWidget::className(), [
                                'mode' => 'json',
                                'fontSize' => 15,
                                'height' => 200,
                            ]
                        );
                    }
                    ?>
                </div>
                <div class="one">
                    <?php echo $form->field($model, 'ttl')->textInput(['class' => 'small']); ?>
                </div>
                <br/>

                <div class="one">
                    <button class="ui blue icon button submit"><i class="save icon"></i><?= Yii::t('app', 'Save') ?>
                    </button>
                </div>
                <?php \Zelenin\yii\SemanticUI\widgets\ActiveForm::end() ?>
            </div>
        </div>
        <div class="column">
            <div class="ui segment">
                <a class="ui right ribbon blue label"><?= RedismanModule::t('redisman', 'Operations log') ?></a>

                <p>

                </p>

            </div>
        </div>
    </div>
</div>