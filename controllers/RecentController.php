<?php
namespace ytubes\videos\controllers;

use Yii;
use yii\di\Instance;
use yii\web\Controller;
use yii\data\Pagination;
use yii\base\Event;
use yii\base\ViewContextInterface;
use ytubes\videos\models\finders\VideoFinder;
use ytubes\components\filters\QueryParamsFilter;
use ytubes\components\Visitor;

/**
 * VideoController implements the CRUD actions for Videos model.
 */
class RecentController extends Controller implements ViewContextInterface
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
        $data['page'] = (int) $page;
        $data['route'] = '/' . $this->getRoute();

        $finder = new VideoFinder();
        $data['videos'] = $finder->getNewVideos($page);
        $data['total_items'] = $finder->totalCount();

        $pagination = new Pagination([
            'totalCount' => $data['total_items'],
            'defaultPageSize' => (int) Yii::$app->getModule('videos')->settings->get('items_per_page', 20),
            'route' => $data['route'],
            'forcePageParam' => false,
        ]);

        $settings = Yii::$app->settings->getAll();
        $settings['videos'] = Yii::$app->getModule('videos')->settings->getAll();

        if (!Visitor::isCrawler()) {
        	Event::on(self::className(), self::EVENT_AFTER_ACTION, ['ytubes\events\VisitorEvent', 'onView']);
        }

        return $this->render('recent', [
            'data' => $data,
            'settings' => $settings,
            'pagination' => $pagination,
        ]);
    }
}
