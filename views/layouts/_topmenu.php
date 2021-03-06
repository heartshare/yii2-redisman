<div class="ui centered padded stackable grid">
    <?= \Zelenin\yii\SemanticUI\collections\Menu::widget(
        [
            'topAttached' => true,
            'fluid' => true,
            'inverted' => true,
            'options'=>['class'=>'centered '],
            'items' => [
                [
                    'url' => ['/'],
                    'label' => \insolita\redisman\Redisman::t('redisman','Main')
                ],
                [
                    'url' => ['/site/about'],
                    'label' => \insolita\redisman\Redisman::t('redisman','About')
                ],
                [
                    'url' => ['/site/logout'],
                    'options'=>['data-method'=>'post'],
                    'label' => \insolita\redisman\Redisman::t('redisman','Logout'),
                    'visible'=>!Yii::$app->user->isGuest
                ],

            ],

        ]
    );
    ?>
</div>