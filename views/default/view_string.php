<?php
use insolita\redisman\Redisman;
use yii\helpers\Html;
use Zelenin\yii\SemanticUI\widgets\DetailView;

/**
 * @var \yii\web\View                                    $this
 * @var \insolita\redisman\controllers\DefaultController $context
 * @var \insolita\redisman\Redisman                $module
 * @var string                                           $key
 * @var \insolita\redisman\models\RedisItem              $model
 */
$module = $this->context->module;
$this->title = $module->getCurrentName();
$dblist = $module->dbList();
$items = [];
foreach ($dblist as $db => $dbalias) {
    if ($db != $module->getCurrentDb()) {
        $items[] = Html::tag(
            'div',
            Html::a(
                $dbalias, \yii\helpers\Url::to(
                    [
                        '/redisman/default/move',
                        'key' => urlencode($key),
                        'db' => $db
                    ]
                ), [
                    'data-method' => 'post', 'data-confirm' => Redisman::t(
                        'redisman', 'O`RLY? Current action move this key in other redis-base!'
                    )
                ]
            ), ['class' => 'item']
        );

    }
}
$items = Html::tag('div', implode('', $items), ['class' => 'menu']);
?>

<div class="ui teal pointed segment">
    <h1 class="ui header">
        <div class="sub header "><i class="icon privacy"></i><?= Html::encode($key) ?></div>
    </h1>
    <div class="ui two column grid">
        <div class="column">
            <div class="ui raised segment">
                <a class="ui ribbon teal label"><?= Redisman::keyTyper($model->type) ?></a>
                <span><?= Redisman::t('redisman', 'Key Information') ?></span>
                <?php echo DetailView::widget(
                    [
                        'model' => $model,
                        'attributes' => [
                            'size',
                            [
                                'attribute' =>'ttl','format'=>'raw',
                                'value'=>$model->ttl.
                                    '<br/><form action="'.\yii\helpers\Url::to(['/redisman/default/persist']).'" method="post">
                                    <div class="ui action mini input">
  <input placeholder="'.Redisman::t('redisman','Set TTl (-1 for persist)').'" type="text" name="RedisItem[ttl]">
  <input type="hidden" name="RedisItem[key]" value="'.$key.'">
  <button class="ui blue icon button">
    <i class="save icon"></i>
  </button></form></div>'
                            ],
                            'refcount', 'idletime',
                            [
                                'attribute' => 'db', 'format' => 'raw',
                                'value' => Html::tag(
                                    'div', Html::tag(
                                        'div',
                                        ' <i class="dropdown icon"></i>' . Redisman::t('redisman', 'Move To')
                                        . $items, ['class' => 'ui simple dropdown item']
                                    ),
                                    ['class' => 'ui compact menu']
                                )
                            ],
                            'encoding'
                        ]
                    ]
                )?>
            </div>
        </div>
        <div class="column">
            <div class="ui segment">
                <a class="ui right ribbon blue label"><?= Redisman::t('redisman', 'Value') ?></a>

                <p>
                    <?php $form = \Zelenin\yii\SemanticUI\widgets\ActiveForm::begin(
                        [
                            'action' => ['/redisman/default/update']
                        ]
                    )?>
                    <input type="hidden" name="RedisItem[key]" value="<?=$key?>">
                <div class="one">
                    <?php
                        echo $form->field($model, 'value')->widget(
                            \lav45\aceEditor\AceEditorWidget::className(), [

                                'mode' => 'text',
                                'fontSize' => 15,
                                'height' => 200,

                            ]
                        );
                     ?>
                </div>
                <div class="one">
                    <button class="ui blue icon button submit"><i class="save icon"></i><?= Yii::t('app', 'Replace') ?>
                    </button>
                </div>
                <?php \Zelenin\yii\SemanticUI\widgets\ActiveForm::end() ?>
                </p>
            </div>
        </div>
    </div>
</div>