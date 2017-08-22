<?php
namespace ytubes\videos\controllers;

use Yii;
use yii\di\Instance;
use yii\base\ViewContextInterface;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Request;
use yii\web\Response;
use yii\web\Session;
use yii\filters\VerbFilter;

use ytubes\videos\Module;
use ytubes\videos\models\Category;
use ytubes\videos\models\RotationStats;

use ytubes\components\filters\QueryParamsFilter;
use ytubes\components\Visitor;
use ytubes\events\VisitorEvent;

/**
 * CategoryController implements the CRUD actions for Videos model.
 */
class AjaxController extends Controller implements ViewContextInterface
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
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'video-click' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Увеличивает клик в стате ротации.
     * @return mixed
     */
    public function actionVideoClick()
    {
        if (Visitor::isCrawler()) {
            return;
        }

        $category_id = $this->request->post('category_id', null);
        $video_id = $this->request->post('video_id', null);
        $image_id = $this->request->post('image_id', null);

        if (null !== $category_id && null !== $video_id && null !== $image_id) {
            $sql = '
                UPDATE `' . RotationStats::tableName() . '`
                SET `current_clicks`=`current_clicks`+1
                WHERE `category_id` = :category_id AND `video_id` = :video_id AND `image_id` = :image_id
            ';
            Yii::$app->db->createCommand($sql)
                //->update(RotationStats::tableName(), ['current_clicks' => 'current_clicks+1'], 'category_id=:category_id AND video_id=:video_id AND image_id=:image_id')
                ->bindParam(':category_id', $category_id)
                ->bindParam(':video_id', $video_id)
                ->bindParam(':image_id', $image_id)
                ->execute();
        }
    }
}
