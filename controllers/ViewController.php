<?php
namespace ytubes\videos\controllers;

use Yii;
use yii\di\Instance;
use yii\base\Event;
use yii\base\ViewContextInterface;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

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
	const EVENT_BEFORE_VIEW_SHOW = 'beforeViewShow';
	const EVENT_AFTER_VIEW_SHOW = 'afterViewShow';
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
        $this->trigger(self::EVENT_BEFORE_VIEW_SHOW);

        $data['slug'] = $slug;
        $data['route'] = '/' . $this->getRoute();

        $videoFinder = new VideoFinder();
        $data['video'] = $videoFinder->findBySlug($slug);

        $settings = Yii::$app->settings->getAll();
        $settings['videos'] = Module::getInstance()->settings->getAll();

        if ($data['video']['template'] !== '') {
        	$template = $data['video']['template'];
        } else {
        	$template = 'view';
        }

        Event::on(self::class, self::EVENT_AFTER_VIEW_SHOW, [\ytubes\videos\events\UpdateCountersEvent::class, 'onClickVideo'], $data);

        $this->trigger(self::EVENT_AFTER_VIEW_SHOW);

        return $this->render($template, [
            'data' => $data,
            'settings' => $settings
        ]);
    }
}
