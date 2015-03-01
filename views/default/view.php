<?php
use insolita\redisman\RedismanModule;
use trntv\aceeditor\AceEditor;
use yii\helpers\Html;
use Zelenin\yii\SemanticUI\collections\Menu;
use Zelenin\yii\SemanticUI\widgets\DetailView;

/**
 * @var \yii\web\View                                    $this
 * @var \insolita\redisman\controllers\DefaultController $context
 * @var \insolita\redisman\RedismanModule                $module
 * @var string                                           $key
 * @var \insolita\redisman\models\RedisItem              $data
 */
$module = $this->context->module;
$this->title = $module->getCurrentName();
$dblist = $module->dbList();
$items = [];
 foreach ($dblist as $db => $dbalias) {
    if ($db != $module->getCurrentDb()) {
        $items[] =Html::tag('div',
            Html::a($dbalias, \yii\helpers\Url::to(
                [
                    '/redisman/default/move',
                    'key' => urlencode($key),
                    'db' => $db
                ]
            )), ['class'=>'item']);

    }
}
$items=Html::tag('div',implode('',$items),['class'=>'menu']);
?>

<div class="ui blue segment">
    <h1 class="ui header"> <?= $module->getCurrentName() ?>
        <div class="sub header"><?= Html::encode($key) ?></div>
    </h1>
    <div class="ui two column grid">
        <div class="column">
            <div class="ui raised segment">
                <a class="ui ribbon teal label"><?= RedismanModule::keyTyper($data->type) ?></a>
                <span><?= RedismanModule::t('Key Information') ?></span>
                <?php echo DetailView::widget(
                    [
                        'model' => $data,
                        'attributes' => [
                            'size', 'ttl',
                            'refcount', 'idletime',
                            [
                                'attribute' => 'db', 'format' => 'raw',
                                'value' => Html::tag('div', Html::tag('div',' <i class="dropdown icon"></i>Move to'.$items, ['class' => 'ui simple dropdown item']),
                                    ['class' => 'ui compact menu'])
                            ],
                            'encoding', ['attribute' => 'value']
                        ]
                    ]
                )?>
            </div>
        </div>
        <div class="column">
            <div class="ui segment">
                <a class="ui right ribbon blue label"><?= RedismanModule::t('Value') ?></a>

                <p>
                    <?php $form = \Zelenin\yii\SemanticUI\widgets\ActiveForm::begin(
                        [
                            'action' => ['/redisman/default/update', 'key' => urlencode($key)]
                        ]
                    )?>
                    <?php echo AceEditor::widget(
                        [
                            'model' => $data,
                            'attribute' => 'value',
                            'mode' => 'json',
                            'containerOptions' => [
                                'style' => 'height:200px;min-height:150px',
                            ]
                        ]
                    )?>
                    <?php echo $form->field($data, 'newttl')->textInput(['class' => 'small']); ?>
                    <button class="ui blue icon button submit"><i class="save icon"></i><?= Yii::t('app', 'Save') ?>
                    </button>
                    <?php \Zelenin\yii\SemanticUI\widgets\ActiveForm::end() ?>
                </p>

            </div>
        </div>
    </div>
</div>