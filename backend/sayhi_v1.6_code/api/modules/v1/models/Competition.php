<?php
namespace api\modules\v1\models;

use api\modules\v1\models\CompetitionUser;
use api\modules\v1\models\CompetitionPosition;
use api\modules\v1\models\Post;
use api\modules\v1\models\CompetitionExampleImage;
use Yii;


class Competition extends \yii\db\ActiveRecord
{
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 9;
    const STATUS_COMPLETED = 9;
    public $ImageFile;
    public $competition_id;


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'competition';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'id', 'start_date', 'end_date', 'award_type', 'coin', 'winner_id', 'joining_fee', 'created_at', 'created_by', 'updated_at', 'updated_by','competition_id'], 'integer'],
            [['price'], 'number'],
            [['title', 'image'], 'string', 'max' => 100],
            [['competition_id' ], 'required','on'=>'join'],

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

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->created_by = Yii::$app->user->identity->id;

        } else {
            $this->updated_at = time();
            $this->updated_by = Yii::$app->user->identity->id;

        }

        return parent::beforeSave($insert);
    }

    public function fields()
    {
        $fields = parent::fields();
        $fields[] = 'imageUrl';
        $fields['expampleImages'] = (function ($model) {
            $imageArr = [];
            foreach ($model->expampleImages as $img) {
                $imageArr[] = $img->imageUrl;
            }
            return $imageArr;
        });
        $fields['is_joined'] = (function($model){
            return (@$model->isJoined) ? 1: 0;
        });
       
       // $fields[] = 'competitionImage';
        return $fields;
    }

    public function extraFields()
    {
        return ['myPost','competitionPosition','winnerPost','post','competitionUser'];
    }

    public function getImageUrl()
    {
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_COMPETITION,$this->image);
        //return Yii::$app->params['pathUploadCompetition'] . "/" . $this->image;
    }

    /**
     * RELEATION START
     */
    public function getExpampleImages()
    {
        return $this->hasMany(CompetitionExampleImage::className(), ['competition_id' => 'id']);

    }



    public function getCompetitionUser()
    {
        return $this->hasMany(CompetitionUser::className(), ['competition_id' => 'id']);

    }


    public function getPost()
    {
        return $this->hasMany(Post::className(), ['competition_id' => 'id']);

    }

    /* winner post */
    public function getWinnerPost()
    {
        return $this->hasMany(Post::className(), ['id' => 'winner_id']);

    }

    public function getMyPost()
    {
        $userId                 = @Yii::$app->user->identity->id;
        return $this->hasMany(Post::className(), ['competition_id' => 'id'])->onCondition(['post.user_id'=>$userId]);

    }

    public function getIsJoined()
    {
        return $this->hasOne(CompetitionUser::className(), ['competition_id' => 'id'])->andOnCondition(['competition_user.user_id' => @Yii::$app->user->identity->id]);
        
        
    }

    public function getCompetitionPosition()
    {
        return $this->hasMany(CompetitionPosition::className(), ['competition_id' => 'id'])->orderBy(['competition_winner_position.id' => SORT_ASC]);

    }




}
