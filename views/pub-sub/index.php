<?php
use insolita\redisman\Redisman;

/**
 * @var \yii\web\View                                    $this
 * @var \insolita\redisman\controllers\DefaultController $context
 * @var \insolita\redisman\Redisman                      $module
 * @var \insolita\redisman\models\RedisItem              $model
 * @var string                           $lastlog
 */
$module = $this->context->module;
$this->title = $module->getCurrentName();
?>

<div class="ui green pointed segment">
    <h1 class="ui header">
        <div class="sub header "><i class="icon plus circle"></i>
            <?= Redisman::t('redisman', 'Publish-Subscribe')?>
        </div>
    </h1>
    <div class="ui two column grid">
        <div class="column">
            <?php if($subsmodel):?>
            <div class="ui raised segment">
                <a class="ui ribbon teal label"><?= Redisman::t('redisman', 'Subscribe')?></a>
                 <?php
                $form = \Zelenin\yii\SemanticUI\widgets\ActiveForm::begin(
                    [
                        'action' => ['/redisman/pub-sub/subscribe']
                    ]
                );?>
                <div class="one">
                    <?php echo $form->field($subsmodel, 'channel')->textInput([]); ?>
                    <br/>
                </div>
                <br/>
                <div class="one">
                    <button class="ui blue icon button submit"><i class="save icon"></i><?= Redisman::t('redisman', 'Subscribe') ?>
                    </button>
                </div>
                <?php \Zelenin\yii\SemanticUI\widgets\ActiveForm::end();?>
            </div>
            <?php endif?>
            <div class="ui raised segment">
                <a class="ui ribbon teal label"><?= Redisman::t('redisman', 'Publish')?></a>
                 <?php
                $form = \Zelenin\yii\SemanticUI\widgets\ActiveForm::begin(
                    [
                        'action' => ['/redisman/pub-sub/publish']
                    ]
                );?>
                <div class="one">
                    <?php echo $form->field($pubmodel, 'channel')->textInput([]); ?>
                    <br/>
                </div>
                <div class="one">
                    <?php echo $form->field($pubmodel, 'message')->textInput([]); ?>
                    <br/>
                </div>
                <br/>
                <div class="one">
                    <button class="ui blue icon button submit"><i class="save icon"></i><?= Redisman::t('redisman', 'Publish') ?>
                    </button>
                </div>
                <?php \Zelenin\yii\SemanticUI\widgets\ActiveForm::end();?>
            </div>
        </div>
        <div class="column">
            <div class="ui segment">
                <a class="ui right ribbon blue label"><?= Redisman::t('redisman', 'Subscription log') ?></a>
                <div style="min-height: 250px;max-height: 600px" class="ui bulleted divided list">
                    <?= \dizews\pushStream\PushStreamWidget::widget([
                            'channels'=>'subs'
                        ]); ?>

                </div>
            </div>
        </div>
    </div>
</div>