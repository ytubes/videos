<?php
namespace ytubes\videos\controllers;

use Yii;
use yii\di\Instance;
use yii\base\Event;
use yii\base\ViewContextInterface;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

use yii\data\Pagination;

use ytubes\videos\models\Category;
use ytubes\videos\models\RotationStats;
use ytubes\videos\models\finders\VideoFinder;
use ytubes\videos\models\finders\CategoryFinder;

use ytubes\components\filters\QueryParamsFilter;
use ytubes\components\Visitor;



use yii\filters\VerbFilter;

/**
 * CategoryController implements the CRUD actions for Videos model.
 */
class CategoryController extends Controller implements ViewContextInterface
{
	public $request = 'request';
	public $response = 'response';

    public function init()
    {
        parent::init();

        $this->request = Instance::ensure($this->request, \yii\web\Request::className());
        $this->response = Instance::ensure($this->response, \yii\web\Response::className());
    }

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
        $data['slug'] = $slug;
        $data['sort'] = $sort;
        $data['page'] = (int) $page;
        $data['route'] = '/' . $this->getRoute();

        	// Ищем категорию
        //$categriesRepository = new CategoriesRepository();
        $data['category'] = CategoryFinder::findBySlug($slug);//$categriesRepository->findBySlug($slug);

		if (empty($data['category'])) {
			throw new NotFoundHttpException('The requested page does not exist.');
		}

        $videoFinder = new VideoFinder();
        $data['videos'] = $videoFinder->getVideosFromCategory($data['category'], $page);

        $pagination = new Pagination([
            'totalCount' => $videoFinder->totalCount(),
            'defaultPageSize' => (int) Yii::$app->getModule('videos')->settings->get('items_per_page', 20),
            'route' => $data['route'],
            'forcePageParam' => false,
        ]);

        $settings = Yii::$app->settings->getAll();
        $settings['videos'] = Yii::$app->getModule('videos')->settings->getAll();

        if (!Visitor::isCrawler()) {
            $image_ids = array_keys($data['videos']);

            RotationStats::updateAllCounters(['current_shows' => 1], ['image_id' => $image_ids, 'category_id' => $data['category']['category_id']]);

	        $session = Yii::$app->get('session');
	        $session->open();

	        if ($session->isActive) {
	            $session['prev_location'] = [
	                'route' => $this->getRoute(),
	                'category_id' => $data['category']['category_id'],
	            ];
	        }

	        Event::on(self::className(), self::EVENT_AFTER_ACTION, ['ytubes\events\VisitorEvent', 'onView']);
        }

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
