<?php
namespace ytubes\videos\models\finders;

use Yii;
use yii\helpers\ArrayHelper;
use ytubes\videos\Module;
use ytubes\videos\models\Video;
use ytubes\videos\models\VideosRelatedMap;
use ytubes\videos\models\RotationStats;

/**
 * CategoryFinder представляет собой импровизированное репо для категорий.
 */
class RelatedFinder
{
	private $requiredRelatedNum;

	const RELATED_NUMBER = 12;

	public function __construct()
	{
		$this->requiredRelatedNum = (int) Module::getInstance()->settings->get('related_number', self::RELATED_NUMBER);
	}

	public function getFromTable($video_id)
	{
			//SELECT `v`.* FROM `videos_related_map` AS `r` LEFT JOIN `videos` AS `v` ON `v`.`video_id` = `r`.`related_id` WHERE `r`.`video_id`=10
		$videos = Video::find()
			->select('{{v}}.*')
			->from(['r' => VideosRelatedMap::tableName()])
			->leftJoin(['v' => Video::tableName()], '{{v}}.{{video_id}}={{r}}.{{related_id}}')
			->with(['categories' => function ($categoryQuery) {
				$categoryQuery->select(['category_id', 'title', 'slug', 'h1']);
			}])
			->with(['image' => function ($imageQuery) {
				$imageQuery->select(['image_id', 'video_id', 'filepath', 'source_url']);
			}])
			->where(['`r`.`video_id`' => (int) $video_id, 'status' => 10])
			->limit($this->requiredRelatedNum)
			->asArray()
			->all();

		return $videos;
	}

	public function getVideos($video_id)
	{
		$related = $this->getFromTable($video_id);

		$relatedNum = count($related);
		if (empty($related) || $relatedNum < $this->requiredRelatedNum) {
			$this->findAndSaveRelatedIds($video_id);
			$related = $this->getFromTable($video_id);
		}

		return $related;
	}

	public function findAndSaveRelatedIds($video_id)
	{
		$video = Video::find()
			->where(['video_id' => $video_id])
			->with('categories')
			->one();

		if (!$video instanceof Video) {
			return;
		}

		//$searchString = trim($video->title . ' ' . $video->description . ' ' . $video->short_description);
		$searchString = trim($video->title);

		$relatedModels = Video::find()
			->select(['`v`.`video_id`', 'MATCH (`title`) AGAINST (:query) AS `title_relevance`', 'MATCH (`title`, `description`, `short_description`) AGAINST (:query) AS `relevance`'])
			->from ([Video::tableName() . ' v']);

		if (!empty($video->categories)) {
				// выборка всех идентификаторов категорий поста.
			$categoriesIds = ArrayHelper::getColumn($video->categories, 'category_id');
			$relatedModels->join('RIGHT JOIN', RotationStats::tableName() . ' cim', '{{cim}}.{{category_id}} IN (' . (implode(',', $categoriesIds)) . ') AND {{cim}}.{{video_id}}={{v}}.{{video_id}}');
		}

		$relatedVideos = $relatedModels
			->where('MATCH (`title`, `description`, `short_description`) AGAINST (:query) AND `v`.`video_id`!=:video_id AND `v`.`status`=10', [':query'=> $searchString, ':video_id' => $video->video_id])
    		->groupBy('`v`.`video_id`')
    		->orderBy(['title_relevance' => SORT_DESC, 'relevance' => SORT_DESC])
    		->limit($this->requiredRelatedNum)
    		->all();

		if (is_array($relatedVideos)) {
			$related = [];

			foreach ($relatedVideos as $relatedVideo) {
				$related[] = [$video->video_id, $relatedVideo->video_id];
			}
				// Удалим старое.
			Yii::$app->db->createCommand()
				->delete(VideosRelatedMap::tableName(), "`video_id`={$video->video_id}")
				->execute();
				// вставим новое
			Yii::$app->db->createCommand()
				->batchInsert(VideosRelatedMap::tableName(), ['video_id', 'related_id'], $related)
				->execute();
		}
	}
}