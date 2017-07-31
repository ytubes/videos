<?php
namespace ytubes\videos\widgets;

use Yii;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;

use ytubes\videos\models\finders\RelatedFinder;

class RelatedVideos extends \yii\base\Widget
{
	private $cacheKey = 'widget:related_videos:';
	/**
	 * @var string путь к темплейту виджета
	 */
	public $template = __DIR__ . '/views/related_videos.php';
	/**
	 * @var integer $video_id
	 */
	public $video_id;
	/**
	 * @var array Коллекция массивов категорий.
	 */
	public $items = [];

	/**
	 * Initializes the widget
	 */
	public function init() {
		parent::init();

		if (empty($this->video_id)) {
			throw new InvalidConfigException('Виджет требует определенного video_id');

			return;
		}
	}

	/**
	 * Runs the widget
	 *
	 * @return string|void
	 */
	public function run() {

		$videos = $this->getItems();

		if (empty($videos)) {
			return;
		}

		return $this->renderFile($this->template, [
        	'data' => [
        		'videos' => $videos,
            ],
		]);
	}

	/**
	 * Получает "похожие" видео.
	 *
	 * @return array
	 */
    private function getItems()
    {
    	$cacheKey = $this->buildCacheKey();

    	$items = Yii::$app->cache->get($cacheKey);

    	if (false === $items) {
    		$relatedFinder = new RelatedFinder;
    		$items = $relatedFinder->getVideos($this->video_id);

    		if (!empty($items)) {
    			Yii::$app->cache->set($cacheKey, $items, 300);
    		}
    	}

    	return $items;
    }

    private function buildCacheKey()
    {
    	return $this->cacheKey . $this->video_id;
    }
}
