<?php
/**
 * @var View   $this
 * @var string $content
 */
use app\assets\AppAsset;
use insolita\redisman\RedismanModule;
use yii\helpers\Html;
use yii\web\Controller;
use yii\web\View;
use Zelenin\yii\SemanticUI\collections\Menu;

AppAsset::register($this);
/** @var Controller $controller */
$controller = $this->context;
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
            <?= \insolita\redisman\widgets\Alert::widget([]) ?>
            <div class="ui stacked segment">
                <?php echo Menu::widget(
                    [
                        'pointing'=>true,
                        'items' => [
                            ['label' => RedismanModule::t('Info'), 'url' => ['/redisman/default/index']],
                            ['label' => RedismanModule::t('List'), 'url' => ['/redisman/default/show']],
                            [
                                'label' => RedismanModule::t('Add'),
                                'items' => [
                                    ['label' => RedismanModule::keyTyper(RedismanModule::REDIS_STRING),
                                    'url' => ['/redisman/default/add', 'type'=>RedismanModule::REDIS_STRING]],
                                    ['label' => RedismanModule::keyTyper(RedismanModule::REDIS_LIST),
                                        'url' => ['/redisman/default/add', 'type'=>RedismanModule::REDIS_LIST]],
                                    ['label' => RedismanModule::keyTyper(RedismanModule::REDIS_HASH),
                                        'url' => ['/redisman/default/add', 'type'=>RedismanModule::REDIS_HASH]],
                                    ['label' => RedismanModule::keyTyper(RedismanModule::REDIS_SET),
                                        'url' => ['/redisman/default/add', 'type'=>RedismanModule::REDIS_SET]],
                                    ['label' => RedismanModule::keyTyper(RedismanModule::REDIS_ZSET),
                                        'url' => ['/redisman/default/add', 'type'=>RedismanModule::REDIS_ZSET]],
                                 ]
                            ],
                        ]
                    ]
                )?>
                <?= $content ?>
            </div>
        </div>
    </div>
    <?php $this->endBody(); ?>
    </body>
    </html>
<?php
$this->endPage();