<?php

namespace backend\controllers;

use app\models\User;
use backend\models\CreatorForm;
use backend\models\CreatorSearch;
use common\helpers\DreamlandCreatorApproval;
use common\models\DreamlandAudience;
use common\models\Payment;
use common\models\Post;
use common\models\UserLiveHistory;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class ContentCreatorController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                    'ban' => ['POST'],
                    'unban' => ['POST'],
                    'approve' => ['POST'],
                    'reject' => ['POST'],
                    'update-status' => ['POST'],
                    'demote' => ['POST'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [[
                    'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::USER),
                    'roles' => ['@'],
                ]],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new CreatorSearch();
        $searchModel->filter = (string) Yii::$app->request->get('filter', 'all');
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'filter' => $searchModel->filter,
        ]);
    }

    public function actionCreate()
    {
        $model = new CreatorForm();
        $model->scenario = 'create';
        $model->status = User::STATUS_ACTIVE;

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            (new User())->checkPageAccess();
            $model->prepareForInsert();
            $this->handleImageUpload($model);

            if ($model->save(false)) {
                Yii::$app->session->setFlash('success', 'Content creator account created.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
            Yii::$app->session->setFlash('error', 'Could not create creator account.');
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findCreatorForm($id);
        $model->scenario = 'update';
        $preStatus = $model->status;

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            (new User())->checkPageAccess();
            $this->handleImageUpload($model);
            $model->applyCreatorIdentity();

            if ($preStatus != $model->status) {
                $model->auth_key = null;
                $model->is_chat_user_online = 0;
            }

            if ($model->save(false)) {
                Yii::$app->session->setFlash('success', 'Creator profile updated.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', ['model' => $model]);
    }

    public function actionView($id)
    {
        $model = $this->findCreator($id);

        $reelsProvider = new ActiveDataProvider([
            'query' => Post::find()
                ->where(['user_id' => $model->id, 'type' => Post::TYPE_REEL])
                ->andWhere(['<>', 'status', Post::STATUS_DELETED])
                ->orderBy(['id' => SORT_DESC]),
            'pagination' => ['pageSize' => 8],
        ]);

        $liveProvider = new ActiveDataProvider([
            'query' => UserLiveHistory::find()
                ->where(['user_id' => $model->id])
                ->orderBy(['id' => SORT_DESC]),
            'pagination' => ['pageSize' => 8],
        ]);

        $stats = [
            'reels' => (int) Post::find()
                ->where(['user_id' => $model->id, 'type' => Post::TYPE_REEL])
                ->andWhere(['<>', 'status', Post::STATUS_DELETED])
                ->count(),
            'live' => (int) UserLiveHistory::find()->where(['user_id' => $model->id])->count(),
            'credits' => (int) $model->available_coin,
        ];

        return $this->render('view', [
            'model' => $model,
            'reelsProvider' => $reelsProvider,
            'liveProvider' => $liveProvider,
            'stats' => $stats,
        ]);
    }

    public function actionUpdateCredits($id)
    {
        $model = $this->findCreator($id);
        $model->scenario = 'updateCoin';
        $paymentModel = new Payment();

        if ($model->load(Yii::$app->request->post())) {
            (new User())->checkPageAccess();
            $updateCoin = (int) $model->update_coin;
            $transactionType = Payment::TRANSACTION_TYPE_CREDIT;
            $historyCoin = abs($updateCoin);

            if ($updateCoin <= 0) {
                $transactionType = Payment::TRANSACTION_TYPE_DEBIT;
            }
            if ($historyCoin === 0) {
                Yii::$app->session->setFlash('error', 'Amount cannot be zero.');
                return $this->redirect(['update-credits', 'id' => $id]);
            }

            $paymentModel->type = Payment::TYPE_COIN;
            $paymentModel->user_id = $model->id;
            $paymentModel->transaction_type = $transactionType;
            $paymentModel->payment_type = Payment::PAYMENT_TYPE_ADMIN_UPDATE;
            $paymentModel->coin = $historyCoin;
            $paymentModel->payment_mode = Payment::PAYMENT_MODE_WALLET;
            $paymentModel->created_at = time();

            if ($paymentModel->save()) {
                $model->available_coin = (int) $model->available_coin + $updateCoin;
                $model->updated_at = time();
                if ($model->save(false)) {
                    Yii::$app->session->setFlash('success', 'Creator credits updated.');
                    return $this->redirect(['view', 'id' => $id]);
                }
            }
        }

        return $this->render('update-credits', ['model' => $model]);
    }

    public function actionBan($id)
    {
        return $this->setCreatorStatus((int) $id, User::STATUS_INACTIVE, 'Creator suspended (banned).');
    }

    public function actionUnban($id)
    {
        return $this->setCreatorStatus((int) $id, User::STATUS_ACTIVE, 'Creator reactivated.');
    }

    public function actionApprove($id)
    {
        return $this->setCreatorApproval((int) $id, true, 'Creator approved — upload, record, and go live are now unlocked in the PWA.');
    }

    public function actionReject($id)
    {
        return $this->setCreatorApproval((int) $id, false, 'Creator application rejected — they can still sign in but cannot publish.');
    }

    public function actionUpdateStatus($id)
    {
        $model = $this->findCreator($id);
        $status = (int) Yii::$app->request->post('status', User::STATUS_ACTIVE);
        if (!in_array($status, [User::STATUS_ACTIVE, User::STATUS_INACTIVE, User::STATUS_PENDING], true)) {
            Yii::$app->session->setFlash('error', 'Invalid status.');
            return $this->redirect(['view', 'id' => $id]);
        }

        return $this->setCreatorStatus((int) $id, $status, 'Creator status updated.');
    }

    public function actionDemote($id)
    {
        $model = $this->findCreator($id);
        (new User())->checkPageAccess();

        DreamlandCreatorApproval::demoteToViewer($model);
        $model->auth_key = null;
        $model->save(false);

        Yii::$app->session->setFlash('success', 'Creator demoted to general user (viewer).');
        return $this->redirect(['index']);
    }

    public function actionDelete($id)
    {
        $model = $this->findCreator($id);
        (new User())->checkPageAccess();

        $model->status = User::STATUS_DELETED;
        $model->auth_key = null;
        $model->is_chat_user_online = 0;
        $model->save(false);

        Yii::$app->session->setFlash('success', 'Creator account deleted.');
        return $this->redirect(['index']);
    }

    protected function setCreatorStatus(int $id, int $status, string $message)
    {
        $model = $this->findCreator($id);
        (new User())->checkPageAccess();

        $model->status = $status;
        if ($status === User::STATUS_PENDING && DreamlandCreatorApproval::hasCreatorStatusColumn()) {
            DreamlandCreatorApproval::markPending($model);
        }
        if ($status !== User::STATUS_ACTIVE) {
            $model->auth_key = null;
            $model->is_chat_user_online = 0;
        }
        $model->save(false);

        Yii::$app->session->setFlash('success', $message);
        return $this->redirect(['view', 'id' => $id]);
    }

    protected function setCreatorApproval(int $id, bool $approved, string $message)
    {
        $model = $this->findCreator($id);
        (new User())->checkPageAccess();

        $model->status = User::STATUS_ACTIVE;
        DreamlandCreatorApproval::applyCreatorIdentity($model);
        if ($approved) {
            DreamlandCreatorApproval::approve($model);
        } else {
            DreamlandCreatorApproval::reject($model);
        }
        $model->save(false);

        Yii::$app->session->setFlash('success', $message);
        return $this->redirect(['view', 'id' => $id]);
    }

    protected function handleImageUpload(CreatorForm $model): void
    {
        $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
        if (!$model->imageFile) {
            return;
        }

        $type = Yii::$app->fileUpload::TYPE_USER;
        $files = Yii::$app->fileUpload->uploadFile($model->imageFile, $type, false);
        if (!empty($files[0]['file'])) {
            $model->image = $files[0]['file'];
        }
    }

    protected function findCreator($id): User
    {
        $model = DreamlandAudience::creatorQuery()->andWhere(['id' => (int) $id])->one();
        if (!$model) {
            throw new NotFoundHttpException('Creator not found.');
        }
        return $model;
    }

    protected function findCreatorForm($id): CreatorForm
    {
        $model = CreatorForm::find()
            ->where(['id' => (int) $id])
            ->andWhere(['<>', 'status', User::STATUS_DELETED])
            ->one();

        if (!$model || !DreamlandAudience::creatorQuery()->andWhere(['id' => $model->id])->exists()) {
            throw new NotFoundHttpException('Creator not found.');
        }

        return $model;
    }
}
