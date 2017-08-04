<?php
namespace ytubes\videos\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "videos".
 *
 * @property integer $video_id
 * @property integer $image_id
 * @property integer $user_id
 * @property string $slug
 * @property string $title
 * @property string $description
 * @property string $short_description
 * @property integer $orientation
 * @property integer $duration
 * @property string $video_url
 * @property string $embed
 * @property integer $on_index
 * @property integer $likes
 * @property integer $dislikes
 * @property integer $comments_num
 * @property integer $views
 * @property string $template
 * @property integer $status
 * @property string $published_at
 * @property string $created_at
 * @property string $updated_at
 *
 * @property VideosCategoriesMap[] $videosCategoriesMaps
 * @property Category[] $categories
 * @property Image[] $images
 * @property RotationStats[] $rotationStats
 */
class Video extends ActiveRecord
{
	use SlugGenerator;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'videos';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
        	[['title'], 'required'],
        	[['slug', 'title'], 'string', 'max' => 120],
        	[['video_id'], 'integer'],
            [['image_id', 'user_id', 'orientation', 'duration', 'on_index', 'likes', 'dislikes', 'comments_num', 'views', 'status'], 'integer'],
            [['description'], 'string'],
            [['published_at', 'created_at', 'updated_at'], 'safe'],
            [['short_description', 'video_url', 'source_url', 'embed', 'template'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'video_id' => 'Video ID',
            'image_id' => 'Image ID',
            'user_id' => 'User ID',
            'slug' => 'Slug',
            'title' => 'Title',
            'description' => 'Description',
            'short_description' => 'Short Description',
            'orientation' => 'Orientation',
            'duration' => 'Duration',
            'video_url' => 'Video Url',
            'source_url' => 'Source Url',
            'embed' => 'Embed',
            'on_index' => 'On Index',
            'likes' => 'Likes',
            'dislikes' => 'Dislikes',
            'comments_num' => 'Comments Count',
            'views' => 'Views',
            'template' => 'Template',
            'status' => 'Status',
            'published_at' => 'Published At',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
    /**
     * @return boolean
     */
	public function hasCategories()
	{
		return !empty($this->categories);
	}
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategories()
    {
        return $this->hasMany(Category::class, ['category_id' => 'category_id'])
        	->viaTable(VideosCategoriesMap::tableName(), ['video_id' => 'video_id']);
    }
    /**
     * @return boolean
     */
	public function hasImage()
	{
		return ($this->image instanceof Image);
	}
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImage()
    {
        return $this->hasOne(Image::class, ['image_id' => 'image_id']);
    }
    /**
     * @return boolean
     */
	public function hasImages()
	{
		return !empty($this->images);
	}
    /**
     * @return \yii\db\ActiveQuery[]
     */
    public function getImages()
    {
        return $this->hasMany(Image::class, ['video_id' => 'video_id']);
    }

    /**
     * @return \yii\db\ActiveQuery[]
     */
    public function getRotationStats()
    {
        return $this->hasMany(RotationStats::class, ['video_id' => 'video_id']);
    }
}
