<?php
namespace ytubes\videos\models;

use Yii;

/**
 * This is the model class for table "videos_images".
 *
 * @property integer $image_id
 * @property integer $video_id
 * @property string $filehash
 * @property integer $position
 * @property string $filepath
 * @property string $source_url
 * @property integer $status
 * @property string $created_at
 *
 * @property Video $video
 * @property RotationStats[] $rotationStats
 */
class Image extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'videos_images';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['video_id', 'position', 'status'], 'integer'],
            [['created_at'], 'safe'],
            [['filehash'], 'string', 'max' => 32],
            [['filepath', 'source_url'], 'string', 'max' => 255],
            [['video_id'], 'exist', 'skipOnError' => true, 'targetClass' => Video::class, 'targetAttribute' => ['video_id' => 'video_id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'image_id' => 'Image ID',
            'video_id' => 'Video ID',
            'filehash' => 'Filehash',
            'position' => 'Position',
            'filepath' => 'Filepath',
            'source_url' => 'Source Url',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVideo()
    {
        return $this->hasOne(Video::class, ['video_id' => 'video_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRotationStats()
    {
        return $this->hasMany(RotationStats::class, ['image_id' => 'image_id']);
    }
}
