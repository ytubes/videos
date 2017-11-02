<?php
namespace ytubes\videos\controllers;

use Yii;
use yii\di\Instance;
use yii\web\Controller;
use yii\data\Pagination;
use yii\base\Event;
use yii\base\ViewContextInterface;
use ytubes\videos\Module;
use ytubes\videos\models\finders\VideoFinder;
use ytubes\components\filters\QueryParamsFilter;
use ytubes\components\Visitor;
use ytubes\events\VisitorEvent;

/**
 * RecentController implements the CRUD actions for Videos model.
 */
class RecentController extends Controller implements ViewContextInterface
{
	const EVENT_BEFORE_RECENT_SHOW = 'beforeRecentShow';
	const EVENT_AFTER_RECENT_SHOW = 'afterRecentShow';

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'queryParams' => [
                'class' => QueryParamsFilter::class,
                'actions' => [
                    'index' => ['page'],
                    'category' => ['slug', 'sort', 'page'],
                    'view' => ['slug'],
                ],
            ],
        ];
    }

    public function getViewPath()
    {
        return Yii::getAlias('@frontend/views/videos');
    }
    /**
     * Lists all Videos models.
     * @return mixed
     */
    public function actionIndex($page = 1)
    {
        $this->trigger(self::EVENT_BEFORE_RECENT_SHOW);

        $data['page'] = (int) $page;
        $data['route'] = '/' . $this->getRoute();

        $finder = new VideoFinder();
        $data['videos'] = $finder->getNewVideos($page);
        $data['total_items'] = $finder->totalCount();

        $pagination = new Pagination([
            'totalCount' => $data['total_items'],
            'defaultPageSize' => Module::getInstance()->settings->get('items_per_page', 20),
            'pageSize' => Module::getInstance()->settings->get('items_per_page', 20),
            'route' => $data['route'],
            'forcePageParam' => false,
        ]);

        $settings = Yii::$app->settings->getAll();
        $settings['videos'] = Module::getInstance()->settings->getAll();

        $this->trigger(self::EVENT_AFTER_RECENT_SHOW);

        return $this->render('recent', [
            'data' => $data,
            'settings' => $settings,
            'pagination' => $pagination,
        ]);
    }
}
