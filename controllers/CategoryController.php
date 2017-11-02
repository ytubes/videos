<?php
namespace ytubes\videos\controllers;

use Yii;
use yii\di\Instance;
use yii\base\Event;
use yii\base\ViewContextInterface;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

use yii\data\Pagination;

use ytubes\videos\Module;
use ytubes\videos\models\Category;
use ytubes\videos\models\RotationStats;
use ytubes\videos\models\finders\VideoFinder;
use ytubes\videos\models\finders\CategoryFinder;

use ytubes\components\filters\QueryParamsFilter;
use ytubes\components\Visitor;
use ytubes\events\VisitorEvent;
use yii\filters\VerbFilter;

/**
 * CategoryController implements the CRUD actions for Videos model.
 */
class CategoryController extends Controller implements ViewContextInterface
{
	const EVENT_BEFORE_CATEGORY_SHOW = 'beforeCategoryShow';
	const EVENT_AFTER_CATEGORY_SHOW = 'afterCategoryShow';

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'queryParams' => [
            	'class' => QueryParamsFilter::class,
            	'actions' => [
            		'index' => ['slug', 'sort', 'page'],
            		'list-all' => ['sort'],
            	],
            ],
        ];
    }

	public function getViewPath()
	{
	    return Yii::getAlias('@frontend/views/videos');
	}
    /**
     * Lists categorized Videos models.
     * @return mixed
     */
    public function actionIndex($slug, $page = 1, $sort = '')
    {
        $this->trigger(self::EVENT_BEFORE_CATEGORY_SHOW);

        $data['slug'] = $slug;
        $data['sort'] = $sort;
        $data['page'] = (int) $page;
        $data['route'] = '/' . $this->getRoute();

        	// Ищем категорию
        $data['category'] = CategoryFinder::findBySlug($slug);//$categriesRepository->findBySlug($slug);

		if (empty($data['category'])) {
			throw new NotFoundHttpException('The requested page does not exist.');
		}

        $videoFinder = new VideoFinder();
        $data['videos'] = $videoFinder->getVideosFromCategory($data['category'], $page);

        $pagination = new Pagination([
            'totalCount' => $videoFinder->totalCount(),
            'defaultPageSize' => Module::getInstance()->settings->get('items_per_page', 20),
            'pageSize' => Module::getInstance()->settings->get('items_per_page', 20),
            'route' => $data['route'],
            'forcePageParam' => false,
        ]);

        $settings = Yii::$app->settings->getAll();
        $settings['videos'] = Module::getInstance()->settings->getAll();

        Event::on(self::class, self::EVENT_AFTER_CATEGORY_SHOW, [\ytubes\videos\events\UpdateCountersEvent::class, 'onShowThumbs'], $data);

        $this->trigger(self::EVENT_AFTER_CATEGORY_SHOW);

        return $this->render('category_videos', [
            'data' => $data,
            'settings' => $settings,
            'pagination' => $pagination,
        ]);
    }
    /**
     * List all categories
     * @return mixed
     */
    public function actionListAll ($sort = '')
    {
        return $this->render('categories_list', [
            //'data' => $data,
            //'settings' => $settings,
            //'pagination' => $pagination,
        ]);
    }
}
