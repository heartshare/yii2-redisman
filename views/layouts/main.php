<?php
/**
 * @var View $this
 * @var string $content
 */
use insolita\redisman\RedismanModule;
use yii\helpers\Html;
use yii\web\Controller;
use yii\web\View;
use Zelenin\yii\SemanticUI\collections\Menu;

insolita\redisman\assets\AppAsset::register($this);
/** @var Controller $controller */
$controller = $this->context;
$module=$controller->module;
$this->beginPage();
?>
    <!doctype html>
    <html>
    <head>
        <?php
        echo Html::csrfMetaTags();
        echo Html::tag('title', Html::encode($this->title)) . "\n";
        $this->registerMetaTag(['charset' => Yii::$app->charset]);
        $this->registerMetaTag(['name' => 'description', 'content' => '']);
        $this->registerMetaTag(['http-equiv' => 'X-UA-Compatible', 'content' => 'IE=edge,chrome=1']);
        $this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width,initial-scale=1']);
        $this->registerMetaTag(['name' => 'SKYPE_TOOLBAR', 'content' => 'SKYPE_TOOLBAR_PARSER_COMPATIBLE']);
        $this->registerLinkTag(['rel' => 'shortcut icon', 'href' => '/favicon.ico']);
        $this->head();
        ?>
    </head>
    <body>
    <?php $this->beginBody(); ?>
    <?= $controller->renderPartial('@redisman/views/layouts/_topmenu') ?>
    <div class="ui centered padded stackable grid">
        <div class="three wide column"><?= $controller->renderPartial('@redisman/views/layouts/_menu') ?></div>
        <div class="thirteen wide column" id="content">

            <div class="ui stacked segment">
                <h1 class="ui header">
                    <i class="wifi icon"></i>
                    <div class="content">
                        <?=$module->getCurrentName()?>
                        <div class="sub header"><?=RedismanModule::t('redisman','Current Connection')?></div>
                    </div>
                </h1>

                <?php echo Menu::widget(
                    [
                        'pointing' => true,
                        'encodeLabels' => false,
                        'items' => [
                            [
                                'label' => '<i class="info circle icon blue"></i>' . RedismanModule::t(
                                        'redisman', 'Info'
                                    ), 'url' => ['/redisman/default/index'],'options'=>['class'=>'blue item']
                            ],
                            [
                                'label' => '<i class="privacy circle icon orange"></i>' . RedismanModule::t(
                                        'redisman', 'List Keys'
                                    ), 'url' => ['/redisman/default/show'],'options'=>['class'=>'orange item']
                            ],
                            [
                                'label' => '<i class="add circle icon green"></i>' . RedismanModule::t(
                                        'redisman', 'Add'
                                    ),'options'=>['class'=>'green item'],
                                'items' => [
                                    [
                                        'label' => RedismanModule::keyTyper(RedismanModule::REDIS_STRING),
                                        'url' => ['/redisman/default/add', 'type' => RedismanModule::REDIS_STRING]
                                    ],
                                    [
                                        'label' => RedismanModule::keyTyper(RedismanModule::REDIS_LIST),
                                        'url' => ['/redisman/default/add', 'type' => RedismanModule::REDIS_LIST]
                                    ],
                                    [
                                        'label' => RedismanModule::keyTyper(RedismanModule::REDIS_HASH),
                                        'url' => ['/redisman/default/add', 'type' => RedismanModule::REDIS_HASH]
                                    ],
                                    [
                                        'label' => RedismanModule::keyTyper(RedismanModule::REDIS_SET),
                                        'url' => ['/redisman/default/add', 'type' => RedismanModule::REDIS_SET]
                                    ],
                                    [
                                        'label' => RedismanModule::keyTyper(RedismanModule::REDIS_ZSET),
                                        'url' => ['/redisman/default/add', 'type' => RedismanModule::REDIS_ZSET]
                                    ],
                                ]
                            ],
                        ]
                    ]
                )?>

                <?= \insolita\redisman\widgets\Alert::widget([]) ?>
                <?= $content ?>
            </div>
        </div>
    </div>
    <?php $this->endBody(); ?>
    </body>
    </html>
<?php
$this->endPage();