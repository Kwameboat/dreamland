<?php
namespace common\models;

use Yii;

class PromotionalAdCategory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'promotional_ad_category';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'category_id', 'promotional_ad_id','created_at'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category_id' => Yii::t('app', 'Category'),
             'promotional_ad_id' => Yii::t('app', 'Promotional ad'),
        ];
    }

    public function updatePromotionalAdCategory($promotional_ad_id, $category_ids)
    {
        $this->deleteAll(['promotional_ad_id' => $promotional_ad_id]);
        $values = [];
      
        if ($category_ids) {
            foreach ($category_ids as $category_id) {
                $dataInner['category_id'] = $category_id;
                $dataInner['promotional_ad_id'] = $promotional_ad_id;
                $values[] = $dataInner;
            }
        }
        if (count($values) > 0) {

            Yii::$app->db
                ->createCommand()
                ->batchInsert('promotional_ad_category', ['category_id','promotional_ad_id'], $values)
                ->execute();
        }
    }

    public function getPromotionalAdCategory($promotional_ad_id)
    {
        return $this->find()->where(['promotional_ad_id' => $promotional_ad_id])->all();

    }

    public function getPromotionalAdCategoryIds($promotional_ad_id)
    {

        $results = $this->getPromotionalAdCategory($promotional_ad_id);
        $ids = [];
        foreach ($results as $result) {
            $ids[] = $result->category_id;

        }
        return $ids;

    }

}
