<?php
namespace ytubes\videos\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "videos_categories".
 *
 * @property integer $category_id
 * @property integer $position
 * @property string $slug
 * @property string $image
 * @property string $meta_title
 * @property string $meta_description
 * @property string $title
 * @property string $h1
 * @property string $description
 * @property string $seotext
 * @property string $param1
 * @property string $param2
 * @property string $param3
 * @property integer $videos_num
 * @property integer $on_index
 * @property integer $shows
 * @property integer $clicks
 * @property double $ctr
 * @property integer $reset_clicks_period
 * @property string $created_at
 * @property string $updated_at
 *
 * @property VideosCategoriesMap[] $videosCategoriesMap
 * @property Video[] $videos
 * @property RotationStats[] $rotationStats
 */
class Category extends ActiveRecord
{
	use SlugGenerator;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'videos_categories';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['title', 'h1', 'image'], 'string', 'max' => 255],
            [['meta_description'], 'string', 'max' => 250],
            [['slug', 'meta_title'], 'string', 'max' => 255],
            [['slug'], 'unique'],

            [['position', 'videos_num', 'shows', 'clicks', 'reset_clicks_period'], 'integer'],
            [['on_index',], 'boolean'],
            ['on_index', 'default', 'value' => true],
            [['description', 'seotext', 'param1', 'param2', 'param3'], 'string'],
            [['ctr'], 'number'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'category_id' => Yii::t('videos', 'Category ID'),
            'position' => Yii::t('videos', 'Position'),
            'slug' => Yii::t('videos', 'Slug'),
            'image' => Yii::t('videos', 'Image'),
            'meta_title' => Yii::t('videos', 'Meta Title'),
            'meta_description' => Yii::t('videos', 'Meta Description'),
            'title' => Yii::t('videos', 'Title'),
            'h1' => Yii::t('videos', 'H1'),
            'description' => Yii::t('videos', 'Description'),
            'seotext' => Yii::t('videos', 'Seotext'),
            'param1' => Yii::t('videos', 'Param1'),
            'param2' => Yii::t('videos', 'Param2'),
            'param3' => Yii::t('videos', 'Param3'),
            'videos_num' => Yii::t('videos', 'Items Count'),
            'on_index' => Yii::t('videos', 'On Index'),
            'shows' => Yii::t('videos', 'Shows'),
            'clicks' => Yii::t('videos', 'Clicks'),
            'ctr' => Yii::t('videos', 'Ctr'),
            'reset_clicks_period' => Yii::t('videos', 'Reset Clicks Period'),
            'created_at' => Yii::t('videos', 'Created At'),
            'updated_at' => Yii::t('videos', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    /*public function getVideos()
    {
        return $this->hasMany(Video::className(), ['video_id' => 'video_id'])->viaTable('videos_categories_map1', ['category_id' => 'category_id']);
    }*/

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRotationStats()
    {
        return $this->hasMany(RotationStats::className(), ['category_id' => 'category_id']);
    }
}
