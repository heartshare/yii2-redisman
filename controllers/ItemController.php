<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 07.03.15
 * Time: 6:58
 */

namespace insolita\redisman\controllers;


use insolita\redisman\models\RedisItem;
use insolita\redisman\Redisman;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Controller;

class ItemController extends Controller{
    /**
     * @param $type
     *
     * @return string
     */
    public function actionCreate($type)
    {
        return $this->render('create');

    }

    /**
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionUpdate()
    {
        $key=\Yii::$app->request->post('key',null);
        $model = RedisItem::find(urldecode($key))->findValue();
        $model->scenario='update';
        if($model->load(\Yii::$app->request->post()) && $model->validate()){
            $model->save();
        }

        return $this->redirect(['view','key'=>urlencode($key)]);
    }

    /**
     * @param $key
     *
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionView($key)
    {
        $model = RedisItem::find(urldecode($key))->findValue();

        return $this->render('view', compact('model'));
    }

    /**
     * @param $key
     *
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionQuick($key)
    {
        $model = RedisItem::find(urldecode($key))->findValue();

        return $this->renderAjax('_quick', compact('model'));
    }

    public function actionRemfield(){

    }

    /**
     * @return \yii\web\Response
     */
    public function actionDelete()
    {
        $model=new RedisItem();
        $model->scenario='delete';
        if($model->load(\Yii::$app->request->post())){
            $model->delete();
        }else{
            \Yii::$app->session->setFlash('error',Html::errorSummary($model->getErrors()));
        }

        return $this->redirect(Url::to(['/redisman/default/show']));
    }

    /**
     * @return \yii\web\Response
     */
    public function actionPersist()
    {
        $model=new RedisItem();
        $model->scenario='persist';
        if($model->load(\Yii::$app->request->post()) && $model->validate()){
            $model->persist();
        }else{
            \Yii::$app->session->setFlash('error',Html::errorSummary($model->getErrors()));
        }

        return $this->redirect(Url::to(['/redisman/default/view', 'key'=>urlencode($model->key)]));
    }

    /**
     * @return \yii\web\Response
     */
    public function actionMove()
    {
        $model=new RedisItem();
        $model->scenario='move';
        if($model->load(\Yii::$app->request->post()) && $model->validate()){
            $model->move();
            \Yii::$app->session->setFlash(
                'success', Redisman::t(
                    'redisman', 'Key moved from Db№ {from} to {to}',
                    ['from' => $this->module->getCurrentDb(), 'to' => $model->db]
                )
            );
        }else{
            \Yii::$app->session->setFlash('error',Html::errorSummary($model->getErrors()));
        }
        return $this->redirect(['show']);

    }
} 