<?php
namespace insolita\redisman\controllers;


use insolita\redisman\models\ConnectionForm;
use insolita\redisman\models\RedisItem;
use insolita\redisman\models\SearchModel;
use insolita\redisman\RedismanModule;
use yii\filters\VerbFilter;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\VarDumper;

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

    public function init()
    {
        parent::init();
        $this->_conn = $this->module->getConnection();
    }

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

    public function actionIndex()
    {
        $info = $this->module->dbInfo();
        return $this->render('index', ['info' => $info]);
    }

    public function actionShow()
    {
        $model = new SearchModel();
        $model->restoreFilter();
        $dataProvider = $model->search(\Yii::$app->request->getQueryParams());
        return $this->render('show', ['model' => $model, 'dataProvider' => $dataProvider]);
    }

    public function actionCreate($type)
    {
        return $this->render('create');

    }

    public function actionUpdate($key)
    {
        $model = new RedisItem();
        $key = urldecode($key);
        $info = $model->find($key);

        return $this->render('update');

    }

    public function actionView($key)
    {
        $model = new RedisItem();
        $key = urldecode($key);
        $data = $model->find($key);
        return $this->render('view', compact('key', 'data'));
    }

    public function actionDelete($key)
    {

        $key = urldecode($key);
        $this->_conn->executeCommand('DEL', [$key]);
        return $this->redirect(Url::to(['/redisman/default/show']));
    }

    public function actionMove($key, $db)
    {
        $key = urldecode($key);
        if ($db !== $this->module->getCurrentDb()) {
            $this->_conn->executeCommand('MOVE', [$key, (int)$db]);
            \Yii::$app->session->setFlash(
                'success', RedismanModule::t(
                    'redisman', 'Key moved from Db№ {from} to {to}', ['from'=>$this->module->getCurrentDb(), 'to'=>(int)$db]));
        } else {
            \Yii::$app->session->setFlash('error', RedismanModule::t('redisman', 'Bad idea - try move in itself'));
        }

        return $this->redirect(['show']);
    }

    public function actionBulk()
    {
        //@TODO:$this
    }

    public function actionSwitch()
    {
        $model = new ConnectionForm();
        if ($model->load(\Yii::$app->request->post()) && $model->validate()) {
            \Yii::info(VarDumper::dumpAsString($model->getAttributes()));
            $this->module->setConnection($model->connection, $model->db);
            SearchModel::resetFilter();
            \Yii::$app->session->setFlash(
                'success', RedismanModule::t('redisman', 'Switched to') . $this->module->getCurrentName()
            );
        } else {
            \Yii::$app->session->setFlash('error', Html::errorSummary($model));
        }

        return $this->redirect(['index']);

    }

    public function actionSearch()
    {
        $model = new SearchModel();
        if ($model->load(\Yii::$app->request->post()) && $model->storeFilter()) {
            \Yii::$app->session->setFlash('success', RedismanModule::t('redisman', 'Search query updated!'));
            return $this->redirect(['show']);
        } else {
            \Yii::$app->session->setFlash('error', Html::errorSummary($model));
            return $this->redirect(['index']);
        }
    }

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

    public function actionResetAppCache()
    {
        //@TODO:$this
    }

} 