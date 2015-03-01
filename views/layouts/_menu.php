<?php
use insolita\redisman\RedismanModule;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var \insolita\redisman\controllers\DefaultController $context
 * @var \insolita\redisman\RedismanModule $module
 */
$module = $this->context->module;
$dbselect = $module->dbList()
?>

<div class="ui vertical menu pointing fluid">
    <div class="header item">
       <i class="icon configure"></i> <?= RedismanModule::t('Connection Settings') ?>
    </div>
    <div class="text item">
        <?= $this->render('_connectform') ?>
    </div>
    <div class="header item">
        <i class="icon search"></i><?= RedismanModule::t('Search Key') ?>
    </div>
    <div class="text item">
        <?= $this->render('_searchform') ?>
    </div>
    <div class="header item">
        <i class="icon database"></i> <?= RedismanModule::t('Db Operation') ?>
    </div>
         <?= Html::a('<i class="icon save"></i> '.RedismanModule::t('Save database'), ['/redisman/default/savedb'], ['class'=>'active green item']) ?>
          <?= Html::a(
              '<i class="icon trash outline"></i> '.RedismanModule::t('Flush database'), ['/redisman/default/flushdb'],
            ['data-method' => 'post', 'data-confirm' => RedismanModule::t('You really want to do it?'),'class'=>'active red item']
        ) ?>
 </div>