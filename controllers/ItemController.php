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
use yii\filters\VerbFilter;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Class ItemController
 *
 * @package insolita\redisman\controllers
 */
class ItemController extends Controller
{
    /**
     * @var \insolita\redisman\Redisman $module
     */
    public $module;


    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'move' => ['post'],
                    'delete' => ['post'],
                    'persist' => ['post'],
                    'update' => ['post'],
                    'append' => ['post'],
                ],
            ]
        ];
    }


    /**
     * @param $type
     *
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionCreate($type)
    {
        if (!in_array($type, array_keys(Redisman::$types))) {
            throw new NotFoundHttpException(Redisman::t('redisman', 'Unsupported type'));
        }
        $model = new RedisItem();
        $model->type = $type;
        $model->scenario = 'create';
        $lastlog = \Yii::$app->session->get('RedisManager_createlog', '');
        $lastlog = explode('[~lastlog~]', $lastlog);
        if (\Yii::$app->request->isPost) {
            if ($model->load(\Yii::$app->request->post()) && $model->validate()) {
                $model->on(
                    RedisItem::EVENT_AFTER_CHANGE, function ($event) use ($lastlog) {
                        array_unshift($lastlog, $event->command);
                        \Yii::$app->session->set('RedisManager_createlog', implode('[~lastlog~]', $lastlog));
                    }
                );
                $model->create();
                \Yii::$app->session->setFlash('success', Redisman::t('redisman', 'Key created!'));
            } else {
                \Yii::$app->session->setFlash('error', Html::errorSummary($model, ['encode' => true]));
            }
            return $this->redirect(['create', 'type' => $type]);
        }
        return $this->render('create', compact('model', 'lastlog'));

    }

    /**
     * @return \yii\web\Response
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionUpdate($key)
    {
        $model = RedisItem::find(urldecode($key))->findValue();
        $model->scenario = 'update';
        if ($model->load(\Yii::$app->request->post()) && $model->validate()) {
            $model->update();
        } else {
            \Yii::$app->session->setFlash('error', Html::errorSummary($model, ['encode' => true]));
        }

        return $this->redirect(['view', 'key' => $key]);
    }


    /**
     * @param $key
     *
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionAppend($key)
    {
        $model = RedisItem::find(urldecode($key))->findValue();
        $model->scenario = 'append';
        if ($model->load(\Yii::$app->request->post()) && $model->validate()) {
            $model->append();
        } else {
            \Yii::$app->session->setFlash('error', Html::errorSummary($model, ['encode' => true]));
        }

        return $this->redirect(['view', 'key' => $key]);
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

    /**
     * @return \yii\web\Response
     */
    public function actionRemfield()
    {
        $model = new RedisItem();
        $model->scenario = 'remfield';
        if ($model->load(\Yii::$app->request->get()) && $model->validate()) {
            $model->remfield();
        }
        $model->findValue();
        return ($model->type) ?
            $this->renderAjax('form_' . $model->type, compact('model'))
            : $this->redirect(['view', 'key' => urldecode($model->key)]);
    }

    /**
     * @return \yii\web\Response
     */
    public function actionDelete()
    {
        $model = new RedisItem();
        $model->scenario = 'delete';
        if ($model->load(\Yii::$app->request->post()) && $model->validate()) {
            $model->delete();
        } else {
            \Yii::$app->session->setFlash('error', Html::errorSummary($model, ['encode' => true]));
        }

        return $this->redirect(Url::to(['/redisman/default/show']));
    }

    /**
     * @return \yii\web\Response
     */
    public function actionPersist()
    {
        $model = new RedisItem();
        $model->scenario = 'persist';
        if ($model->load(\Yii::$app->request->post()) && $model->validate()) {
            $model->persist();
        } else {
            \Yii::$app->session->setFlash('error', Html::errorSummary($model, ['encode' => true]));
        }

        return $this->redirect(Url::to(['view', 'key' => urlencode($model->key)]));
    }

    /**
     * @return \yii\web\Response
     */
    public function actionMove()
    {
        $model = new RedisItem();
        $model->scenario = 'move';
        if ($model->load(\Yii::$app->request->post()) && $model->validate()) {
            $model->move();
            \Yii::$app->session->setFlash(
                'success', Redisman::t(
                    'redisman', 'Key moved from Db№ {from} to {to}',
                    ['from' => $this->module->getCurrentDb(), 'to' => $model->db]
                )
            );
        } else {
            \Yii::$app->session->setFlash('error', Html::errorSummary($model, ['encode' => true]));
        }
        return $this->redirect(Url::to(['/redisman/default/show']));

    }
} 