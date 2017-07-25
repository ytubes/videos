<?php
namespace ytubes\videos\models;

use Yii;

/**
 * This is the model class for table "videos_stats".
 *
 * @property integer $category_id
 * @property integer $image_id
 * @property integer $video_id
 * @property integer $best_image
 * @property string $published_at
 * @property integer $duration
 * @property integer $shows
 * @property integer $clicks
 * @property double $ctr
 *
 * @property Video $video
 * @property Category $category
 * @property Image $image
 */
class RotationStats extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'videos_stats';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['category_id', 'image_id', 'video_id'], 'required'],
            [['category_id', 'image_id', 'video_id', 'best_image', 'duration', 'current_shows', 'current_clicks'], 'integer'],
            [['published_at'], 'safe'],
            [['ctr'], 'number'],
            [['video_id'], 'exist', 'skipOnError' => true, 'targetClass' => Video::className(), 'targetAttribute' => ['video_id' => 'video_id']],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::className(), 'targetAttribute' => ['category_id' => 'category_id']],
            [['image_id'], 'exist', 'skipOnError' => true, 'targetClass' => Image::className(), 'targetAttribute' => ['image_id' => 'image_id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'category_id' => 'Category ID',
            'image_id' => 'Image ID',
            'video_id' => 'Video ID',
            'best_image' => 'Best Image',
            'published_at' => 'Published At',
            'duration' => 'Duration',
            'total_shows' => 'Shows',
            'total_clicks' => 'Clicks',
            'ctr' => 'Ctr',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVideo()
    {
        return $this->hasOne(Video::className(), ['video_id' => 'video_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['category_id' => 'category_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategories()
    {
        return $this->hasMany(Category::className(), ['category_id' => 'category_id'])
				->viaTable(RotationStats::tableName(), ['video_id' => 'video_id'], function ($query) {
			        /* @var $query \yii\db\ActiveQuery */

			    	$query->select(['video_id', 'category_id']);
		        });
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImage()
    {
        return $this->hasOne(Image::className(), ['image_id' => 'image_id']);
    }
}
