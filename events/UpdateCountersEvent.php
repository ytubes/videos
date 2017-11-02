<?php
namespace ytubes\videos\events;

use Yii;
use yii\web\Request;
use ytubes\videos\models\Video;
use ytubes\videos\models\Category;
use ytubes\videos\models\RotationStats;

class UpdateCountersEvent
{
    public static $data;

    public static function onShowThumbs($event)
    {
        $crawlerDetect = Yii::$container->get('crawler.detect');

        if ($crawlerDetect->isCrawler()) {
        	return;
        }

        if (empty($event->data['videos'])) {
        	return;
        }

        $image_ids = array_keys($event->data['videos']);

        RotationStats::updateAllCounters(['current_shows' => 1], ['image_id' => $image_ids, 'category_id' => $event->data['category']['category_id']]);

        /*Yii::$app->session->open();

        if (Yii::$app->session->isActive) {
            Yii::$app->session['prev_location'] = [
                'route' => $this->getRoute(),
                'category_id' => $data['category']['category_id'],
            ];
        }*/
    }

    public static function onClickVideo($event)
    {
        $crawlerDetect = Yii::$container->get('crawler.detect');

        if ($crawlerDetect->isCrawler()) {
        	return;
        }

        // Аадейт счетчика просмотров видео
        $video_id = isset($event->data['video']['video_id']) ? $event->data['video']['video_id'] : 0;
        $image_id = isset($event->data['video']['image']['image_id']) ? $event->data['video']['image']['image_id'] : 0;

        Video::updateAllCounters(['views' => 1], ['video_id' => $video_id]);

        // Анализируем рефер
        $request = new Request([
        	'pathInfo' => parse_url(Yii::$app->request->getReferrer(), PHP_URL_PATH),
        ]);

		$route = Yii::$app->urlManager->parseRequest($request);

        // Определим, был ли клик со страницы категории.
        if ($route[0] === 'videos/category/index' && isset($route[1]['slug'])) {
            $category = Category::findBySlug($route[1]['slug']);

            if ($category instanceof Category) {
            	RotationStats::updateAllCounters(['current_clicks' => 1], ['category_id' => $category->category_id, 'video_id' => $video_id, 'image_id' => $image_id]);
            }
        }

        /*if (!empty($session['prev_location']) && $session['prev_location']['route'] === 'videos/category/index') {
            $category_id = $session['prev_location']['category_id'];

            RotationStats::updateAllCounters(['current_clicks' => 1], ['category_id' => $category_id, 'video_id' => $video_id, 'image_id' => $image_id]);

            //$session->remove('prev_location');
        }*/
    }
}
