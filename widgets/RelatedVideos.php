<?php
namespace ytubes\videos\widgets;

use Yii;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;
use yii\base\Widget;

use ytubes\videos\Module;
use ytubes\videos\models\finders\RelatedFinder;

class RelatedVideos extends Widget
{
    private $cacheKey = 'videos:widgets:related_videos:';
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
    public $items;
    /**
     * @var array диапазон показа релейтедов
     * Пример: 'range' => [1, 5],
     */
    public $range;

    public $enable;

    /**
     * Initializes the widget
     */
    public function init() {
        parent::init();

        if (empty($this->video_id)) {
            throw new InvalidConfigException('Виджет требует определенного video_id');

            return;
        }
        if (is_null($this->enable)) {
            $this->enable = Module::getInstance()->settings->get('related_enable', true);
        }
    }

    /**
     * Runs the widget
     *
     * @return string|void
     */
    public function run() {

        if (!$this->enable) {
            return;
        }

        $videos = $this->getItems();

        if (empty($videos)) {
            return;
        }

        if (is_array($this->range)) {
            $rangeStart = ($this->range[0] > 0) ? $this->range[0] - 1 : 0 ;
            $rangeEnd = (!isset($this->range[1])) ? 1 : $this->range[1] ;
            $videos = array_slice($videos, $rangeStart, $rangeEnd);
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
        if (!is_null($this->items)) {
            return $this->items;
        }

        $cacheKey = $this->buildCacheKey();

        $this->items = Yii::$app->cache->get($cacheKey);

        if (false === $this->items) {
            $relatedFinder = new RelatedFinder;
            $this->items = $relatedFinder->getVideos($this->video_id);

            if (!empty($this->items)) {
                Yii::$app->cache->set($cacheKey, $this->items, 300);
            }
        }

        return $this->items;
    }

    private function buildCacheKey()
    {
        return $this->cacheKey . $this->video_id;
    }
}
