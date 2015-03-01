<?php
use \insolita\redisman\RedismanModule;
use Zelenin\yii\SemanticUI\widgets\GridView;
use Zelenin\yii\SemanticUI\modules\Dropdown;
use \yii\helpers\Html;
/**
 * @var \yii\web\View $this
 * @var \insolita\redisman\controllers\DefaultController $context
 * @var \insolita\redisman\RedismanModule $module
 * @var array $info
 */
$module=$this->context->module;
$this->title=$module->getCurrentName();
$dblist=$module->dbList();

?>

<div class="ui blue segment">
    <h1 class="ui header"> <?=$module->getCurrentName()?> </h1>

    <?php
    echo GridView::widget([
            'dataProvider'=>$dataProvider,
            'columns'=>[
                'key','type','size','ttl',
                [
                    'class'=>'\yii\grid\ActionColumn',
                    'template'=>'{view} {update} {delete}',
                    'buttons' => [
                        'view' => function ($url, $model) {
                            return  Html::a(
                                '<i class="icon circular  inverted eye purple"></i>',
                                \yii\helpers\Url::to(['view', 'key' => urlencode($model['key'])]),
                                ['data-pjax' => 0, 'data-modaler'=>true, 'title'=>Yii::t('app','View')]
                            );
                        },
                        'update' => function ($url, $model) {
                            return  Html::a(
                                '<i class="icon circular inverted pencil teal"></i>',
                                \yii\helpers\Url::to(['update', 'key' =>  urlencode($model['key'])]),
                                ['data-pjax' => 0, 'title'=>Yii::t('app','Update')]
                            );
                        },
                        'delete' => function ($url, $model) {
                            return  Html::a(
                                '<i class="icon circular small inverted trash red"></i>',
                                    \yii\helpers\Url::to(['delete', 'key' => urlencode($model['key'])]),
                                    [
                                        'data-pjax' => 0,
                                        'data-confirm' => 'Подтвердите действие', 'data-method' => 'post'
                                        , 'title'=>Yii::t('app','Delete')
                                    ]
                                );
                        },

                    ]
                ]
            ]
        ])?>
</div>