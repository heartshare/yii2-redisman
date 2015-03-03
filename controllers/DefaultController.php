<?php
namespace insolita\redisman\controllers;


use insolita\redisman\models\ConnectionForm;
use insolita\redisman\models\RedisItem;
use insolita\redisman\models\RedisModel;
use insolita\redisman\RedismanModule;
use yii\filters\VerbFilter;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\VarDumper;

/**
 * Class DefaultController
 *
 * @package insolita\redisman\controllers
 */
class DefaultController extends \yii\web\Controller
{
    /**
     * @var \insolita\redisman\RedismanModule $module
     */
    public $module;
    /**
     * @var \yii\redis\Connection $_conn
     */
    private $_conn = null;

    /**
     *
     */
    public function init()
    {
        parent::init();
        $this->_conn = $this->module->getConnection();
    }

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
                    'switch' => ['post'],
                    'flushdb' => ['post'],
                    'search' => ['post'],
                    'dbload' => ['post'],
                    'move' => ['post'],
                ],
            ]
        ];
    }

    /**
     * @return string
     */
    public function actionIndex()
    {
        $info = $this->module->dbInfo();
        return $this->render('index', ['info' => $info]);
    }

    /**
     * @return string
     */
    public function actionShow()
    {
        $model = new RedisModel();
        $model->restoreFilter();
        $dataProvider = $model->search(\Yii::$app->request->getQueryParams());
        return $this->render('show', ['model' => $model, 'dataProvider' => $dataProvider]);
    }

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
     * @param $key
     *
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionUpdate($key)
    {
        $model = new RedisItem();
        $key = urldecode($key);
        $info = $model->find($key);

        return $this->render('update');

    }

    /**
     * @param $key
     *
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionView($key)
    {
        $model = new RedisItem();
        $key = urldecode($key);
        $data = $model->find($key);
        if($data->type==RedismanModule::REDIS_STRING){
            $view='view_string';
        }else{
            $view='view';
        }
        return $this->render($view, compact('key', 'data'));
    }

    /**
     * @param $key
     *
     * @return \yii\web\Response
     */
    public function actionDelete($key)
    {

        $key = urldecode($key);
        $this->_conn->executeCommand('DEL', [$key]);
        return $this->redirect(Url::to(['/redisman/default/show']));
    }

    /**
     * @return \yii\web\Response
     */
    public function actionPersist()
    {
        $model=new RedisItem();
        $model->scenario='persist';
        if($model->validate()){
            $model->persist();
        }else{
            \Yii::$app->session->setFlash('error',Html::errorSummary($model->getErrors()));
        }

        return $this->redirect(Url::to(['/redisman/default/view', 'key'=>urlencode($model->key)]));
    }

    /**
     * @param $key
     * @param $db
     *
     * @return \yii\web\Response
     */
    public function actionMove($key, $db)
    {
        $key = urldecode($key);
        $model=new RedisItem();
        $model->scenario='move';
        $model->setAttributes($key,$db);
        if($model->validate()){
            $model->move();
            \Yii::$app->session->setFlash(
                'success', RedismanModule::t(
                    'redisman', 'Key moved from Db№ {from} to {to}',
                    ['from' => $this->module->getCurrentDb(), 'to' => $model->db]
                )
            );
        }else{
            \Yii::$app->session->setFlash('error',Html::errorSummary($model->getErrors()));
        }
        return $this->redirect(['show']);

     }

    /**
     *
     */
    public function actionBulk()
    {
        //@TODO:$this
    }

    /**
     * @return \yii\web\Response
     * @throws \yii\base\ErrorException
     */
    public function actionSwitch()
    {
        $model = new ConnectionForm();
        if ($model->load(\Yii::$app->request->post()) && $model->validate()) {
            \Yii::info(VarDumper::dumpAsString($model->getAttributes()));
            $this->module->setConnection($model->connection, $model->db);
            RedisModel::resetFilter();
            \Yii::$app->session->setFlash(
                'success', RedismanModule::t('redisman', 'Switched to') . $this->module->getCurrentName()
            );
        } else {
            \Yii::$app->session->setFlash('error', Html::errorSummary($model));
        }

        return $this->redirect(['index']);

    }

    /**
     * @return \yii\web\Response
     */
    public function actionSearch()
    {
        $model = new RedisModel();
        if ($model->load(\Yii::$app->request->post()) && $model->storeFilter()) {
            \Yii::$app->session->setFlash('success', RedismanModule::t('redisman', 'Search query updated!'));
            return $this->redirect(['show']);
        } else {
            \Yii::$app->session->setFlash('error', Html::errorSummary($model));
            return $this->redirect(['index']);
        }
    }

    /**
     * @return \yii\web\Response
     */
    public function actionSavedb()
    {
        $this->_conn->executeCommand('BGSAVE');
        if (\Yii::$app->request->isAjax) {
            echo 'ok';
        } else {
            \Yii::$app->session->setFlash(
                'success', RedismanModule::t('redisman', 'database saving run in background')
            );
            return $this->redirect(['index']);
        }
    }

    /**
     * @return \yii\web\Response
     */
    public function actionFlushdb()
    {
        $this->_conn->executeCommand('FLUSHDB');
        if (\Yii::$app->request->isAjax) {
            echo 'ok';
        } else {
            \Yii::$app->session->setFlash('success', RedismanModule::t('redisman', 'Clearind Database'));
            return $this->redirect(['index']);
        }
    }

    /**
     * @return bool|string
     */
    public function actionDbload()
    {
        $connect = \Yii::$app->request->post('connection');
        $totalDb = $this->module->totalDbCount();
        if (isset($totalDb[$connect])) {
            $dblist = '';
            for ($i = 0; $i < $totalDb[$connect]; $i++) {
                $dblist .= Html::tag('div', 'Db №' . $i, ['data-value' => $i, 'class' => 'item']);
            }
            return $dblist;
        } else {
            return false;
        }
    }

    /**
     *
     */
    public function actionResetAppCache()
    {
        //@TODO:$this
    }

} 