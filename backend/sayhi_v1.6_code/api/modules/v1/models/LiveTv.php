<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\LiveTvSubscriber;
use api\modules\v1\models\LiveTvViewer;
use api\modules\v1\models\LiveTvFavorite;
use api\modules\v1\models\LiveTvCategory;
use api\modules\v1\models\TvShow;
use common\models\TvShowEpisode;

class LiveTv extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

   
    public $imageFile;
    public $transaction_id;
   

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'live_tv';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'id','category_id','priority','is_live','is_paid','paid_coin'], 'integer'],
            [['name','tv_url'], 'string'],
            [['id' ], 'required','on'=>['subscribe','stopViewing','addFavorite','removeFavorite']],
            [['transaction_id'], 'safe'],
            
            
            
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            
        ];
    }
    
    public function fields()
    {
        
        $fields = parent::fields();
        unset($fields['parent_id'],$fields['status'],$fields['priority'],$fields['leval']);
        $fields[] = 'imageUrl';
        //$fields[] = 'categoryName';

        $fields['categoryName'] = (function($model){
            return @$model->category->name;
           // return (@$model->isReported) ? 1: 0;
        });
        $fields['is_subscribed'] = (function($model){
            

            return (@$model->isSubscribe) ? 1: 0;
        });
        $fields['is_favorite'] = (function($model){
            return (@$model->isFavorite) ? 1: 0;
        });
        return $fields;
    }


    public function extraFields()
    {
        return ['subCategory','currentViewer','tvShow'];
    }
   
 

    public function getCategory(){

        return $this->hasOne(LiveTvCategory::className(), ['id' => 'category_id']);

    }

    public function getCurrentViewer(){

        return (int)$this->hasMany(LiveTvViewer::className(), ['live_tv_id' => 'id'])->count();

    }

    public function getTvShow(){
        
        return $this->hasMany(TvShow::className(), ['tv_channel_id' => 'id'])->andWhere(['and', 'status', TvShow::STATUS_ACTIVE])->limit(10);

    }

    // public function getTvShowEpisode(){
    //     echo "hello";
    //     // $response = $this->hasMany(TvShowEpisode::className(), ['tv_show_id' => 'id'])->andWhere(['and', 'status', TvShowEpisode::STATUS_ACTIVE])->all();
    //     // print_r($response);
    //     exit("kdjk");

    // }


    public function getImageUrl()
    {
        if($this->image){
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_LIVE_TV,$this->image);
           
        }else{
            return '';
        }
        
    }

    public function getIsSubscribe()
    {
        return $this->hasOne(LiveTvSubscriber::className(), ['live_tv_id'=>'id'])->andOnCondition(['live_tv_subscriber.user_id' => Yii::$app->user->identity->id]);
        
    }

    public function getIsFavorite()
    {
        return $this->hasOne(LiveTvFavorite::className(), ['live_tv_id'=>'id'])->andOnCondition(['live_tv_favorite.user_id' => Yii::$app->user->identity->id]);
        
    }
    

    public function getLiveTvSubscriber()
    {
        return $this->hasMany(LiveTvSubscriber::className(), ['live_tv_id'=>'id']);
        
    }

    public function getLiveTvMyFavorite()
    {
        return $this->hasMany(LiveTvFavorite::className(), ['live_tv_id'=>'id']);
        
    }

    

}
