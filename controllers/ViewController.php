<?php
namespace ytubes\videos\controllers;

use Yii;
use yii\di\Instance;
use yii\base\Event;
use yii\base\ViewContextInterface;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Request;
use yii\web\Response;
use yii\web\Session;

use ytubes\videos\Module;
use ytubes\videos\models\Video;
use ytubes\videos\models\RotationStats;
use ytubes\videos\models\finders\VideoFinder;

use ytubes\components\filters\QueryParamsFilter;
use ytubes\components\Visitor;
use ytubes\events\VisitorEvent;

/**
 * ViewController implements the CRUD actions for Videos model.
 */
class ViewController extends Controller implements ViewContextInterface
{
	public $request = 'request';
	public $response = 'response';
	public $session = 'session';

    public function init()
    {
        parent::init();

        $this->request = Instance::ensure($this->request, Request::class);
        $this->response = Instance::ensure($this->response, Response::class);
        $this->session = Instance::ensure($this->session, Session::class);
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
            		'index' => ['slug'],
            	],
            ],
        ];
    }

	public function getViewPath()
	{
	    return Yii::getAlias('@frontend/views/videos');
	}
    /**
     * Displays a single Videos model.
     * @param integer $id
     * @return mixed
     */
    public function actionIndex($slug)
    {
        $data['slug'] = $slug;
        $data['route'] = '/' . $this->getRoute();

        $videoFinder = new VideoFinder();
        $data['video'] = $videoFinder->findBySlug($slug);

        $settings = Yii::$app->settings->getAll();
        $settings['videos'] = Module::getInstance()->settings->getAll();

        if (!Visitor::isCrawler()) { // Оформить как евент
            Video::updateAllCounters(['views' => 1], ['video_id' => $data['video']['video_id']]);

	        $this->session->open();

            if ($this->session->isActive) {
                if (!empty($this->session['prev_location']) && $this->session['prev_location']['route'] === 'videos/category/index') {
                    RotationStats::updateAllCounters(['current_clicks' => 1], ['image_id' => $data['video']['image']['image_id'], 'category_id' => $this->session['prev_location']['category_id']]);
                    $this->session->remove('prev_location');
                }
            }

			Event::on(self::class, self::EVENT_AFTER_ACTION, [VisitorEvent::class, 'onClick']);
        }

        if ($data['video']['template'] !== '') {
        	$template = $data['video']['template'];
        } else {
        	$template = 'view';
        }

        return $this->render($template, [
            'data' => $data,
            'settings' => $settings
        ]);
    }
}
