<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 14.03.15
 * Time: 0:01
 */

namespace insolita\redisman\controllers;


use insolita\redisman\components\PhpredisConnection;
use insolita\redisman\Redisman;
use yii\base\DynamicModel;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Html;
use yii\web\Controller;
use yii\web\Response;

class PubSubController extends Controller
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
                'class' => AccessControl::className(),
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
                    'publish' => ['post']
                ],
            ]
        ];
    }


    public function actionIndex()
    {
        $subscripts = \Yii::$app->session->get('RedisManager_subs' . $this->module->getCurrentConn(), null);
        if (!$this->module->getConnection() instanceof PhpredisConnection) {
            \Yii::$app->session->setFlash(
                'warning', Redisman::t(
                    'redisman', '
            Subscription for connection via yii\redis\Connection not supported!
            Use PhpredisConnection
            '
                )
            );
            $subsmodel = null;
        }elseif(!\Yii::$app->has('consoleRunner') || !\Yii::$app->has('pusher')){
            \Yii::$app->session->setFlash(
                'warning', Redisman::t(
                    'redisman', '
              For Subscription functional you must configure components  consoleRunner and pusher
            '
                )
            );
            $subsmodel = null;
        } else {
            $subsmodel = new DynamicModel(['channel']);
            $subsmodel->attributeLabels(['channel' => Redisman::t('redisman', 'Channel')]);
        }

        $pubmodel = new DynamicModel(['channel', 'message']);
        $pubmodel->attributeLabels(
            ['channel' => Redisman::t('redisman', 'Channel'), 'message' => Redisman::t('redisman', 'Message')]
        );
        return $this->render('index', compact('subsmodel', 'pubmodel', 'subscripts'));
    }

    public function actionSubscribe()
    {
        $subsmodel = new DynamicModel(['channel']);
        $subsmodel->attributeLabels(['channel' => Redisman::t('redisman', 'Channel')]);
        $subsmodel->addRule('channel', 'required')
            ->addRule('channel', 'string')
            ->addRule('channel', 'match', ['not' => true, 'pattern' => ['a-zA-Z0-9:\*\[\]']]);
        if ($subsmodel->load(\Yii::$app->request->post()) && $subsmodel->validate()) {
            \Yii::$app->session->set('RedisManager_subs' . $this->module->getCurrentConn(), null);
            \Yii::$app->consoleRunner->run('subs/subscribe '.$subsmodel->channel);
            \Yii::$app->session->setFlash('success', Redisman::t('redisman', 'Subscribed!'), false);
        } else {
            \Yii::$app->session->setFlash(
                'error', Redisman::t('redisman', Html::errorSummary($subsmodel, ['encode' => false])), false
            );
        }
        return $this->redirect(['index']);
    }

    public function actionUnSubscribe()
    {
        $subsmodel = new DynamicModel(['channel']);
        $subsmodel->attributeLabels(['channel' => Redisman::t('redisman', 'Channel')]);
        $subsmodel->addRule('channel', 'required')
            ->addRule('channel', 'string')
            ->addRule('channel', 'match', ['not' => true, 'pattern' => ['a-zA-Z0-9:\*\[\]']]);
        if ($subsmodel->load(\Yii::$app->request->post()) && $subsmodel->validate()) {
            \Yii::$app->session->setFlash('success', Redisman::t('redisman', 'Unsubscribed!'), false);
        } else {
            \Yii::$app->session->setFlash(
                'error', Redisman::t('redisman', Html::errorSummary($subsmodel, ['encode' => false])), false
            );
        }
        return $this->redirect(['index']);
    }

    public function actionPublish()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $pubmodel = new DynamicModel(['channel', 'message']);
        $pubmodel->attributeLabels(
            ['channel' => Redisman::t('redisman', 'Channel'), 'message' => Redisman::t('redisman', 'Message')]
        );
        $pubmodel->addRule(['channel', 'message'], 'required')
            ->addRule(['channel', 'message'], 'string')
            ->addRule('channel', 'match', ['not' => true, 'pattern' => ['a-zA-Z0-9:\*\[\]']]);
        if ($pubmodel->load(\Yii::$app->request->post()) && $pubmodel->validate()) {
            $this->module->executeCommand('PUBLISH', [$pubmodel->channel, Html::encode($pubmodel->message)]);
            return ['error' => false];
        } else {
            return ['error' => Html::errorSummary($pubmodel, ['encode' => false])];
        }


    }
} 