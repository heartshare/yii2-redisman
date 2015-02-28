<div class="ui centered padded stackable grid">
    <?= \Zelenin\yii\SemanticUI\collections\Menu::widget(
        [
            'topAttached' => true,
            'fluid' => true,
            'inverted' => true,
            'options'=>['class'=>'centered'],
            'items' => [
                [
                    'url' => ['/'],
                    'label' => \insolita\redisman\RedismanModule::t('Main')
                ],
                [
                    'url' => ['/site/about'],
                    'label' => \insolita\redisman\RedismanModule::t('About')
                ],
                [
                    'url' => ['/site/logout'],
                    'options'=>['data-method'=>'post'],
                    'label' => \insolita\redisman\RedismanModule::t('Logout'),
                    'visible'=>!Yii::$app->user->isGuest
                ],

            ],

        ]
    );
    ?>
</div>