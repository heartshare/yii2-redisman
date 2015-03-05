<?php
use insolita\redisman\Redisman;
use yii\helpers\Html;
use Zelenin\yii\SemanticUI\widgets\DetailView;

/**
 * @var \yii\web\View                                    $this
 * @var \insolita\redisman\controllers\DefaultController $context
 * @var \insolita\redisman\Redisman                      $module
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
                        'key' => urlencode($model->key),
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
        <div class="sub header "><i class="icon privacy"></i><?= Html::encode($model->key) ?></div>
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
                                'attribute' => 'ttl', 'format' => 'raw',
                                'value' => $model->ttl .
                                    '<br/><form action="' . \yii\helpers\Url::to(['/redisman/default/persist']) . '" method="post">
                                    <div class="ui action mini input">
  <input placeholder="' . Redisman::t('redisman', 'Set TTl (-1 for persist)') . '" type="text" name="RedisItem[ttl]">
  <input type="hidden" name="RedisItem[key]" value="' . $model->key . '">
  <button class="ui blue icon button">
    <i class="save icon"></i>
  </button><input type="hidden" name="' . Yii::$app->getRequest()->csrfParam . '" value="' . Yii::$app->getRequest()
                                        ->getCsrfToken() . '"></form></div>'
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
                <?php if ($model->type == Redisman::REDIS_STRING): ?>
                    <?= $this->render('form_string', ['model' => $model]) ?>
                <?php elseif($model->type == Redisman::REDIS_SET || $model->type == Redisman::REDIS_LIST):?>
                     <?=$this->render('form_list', ['model' => $model])?>
                <?php else:?>
                    <?=$this->render('form_hash', ['model' => $model])?>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>