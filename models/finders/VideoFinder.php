<?php
namespace ytubes\videos\models\finders;

use Yii;
use yii\web\NotFoundHttpException;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\data\Sort;

use ytubes\videos\models\Video;
use ytubes\videos\models\VideoStatus;
use ytubes\videos\models\Category;
use ytubes\videos\models\RotationStats;
use ytubes\videos\models\VideosRelatedMap;

/**
 * VideosFinder represents the model behind the search form about `ytubes\videos\models\Video`.
 */
class VideoFinder extends Model
{
    public $slug;
    public $page;

    private $totalItems;

    private $sort;

    const ITEMS_PER_PAGE = 20;
    const TEST_ITEMS_PERCENT = 0;
    const TEST_ITEMS_START = 3;
    const RELATED_NUMBER = 12;

    const TEST_IMAGE = 0;
    const TESTED_IMAGE = 1;

    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['slug'], 'string'],
            ['page', 'integer', 'min' => 1],
            ['page', 'default', 'value' => 1],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }


    /**
     * Получает ролики постранично в разделе "Новое", отсортированные по дате.
     */
    public static function findBySlug($slug)
    {
        $video = Video::find()
            ->with(['images' => function ($imageQuery) {
                $imageQuery->select(['image_id', 'video_id', 'filepath', 'source_url'])
                    ->indexBy('image_id');
            }])
            ->with(['categories' => function ($categoryQuery) {
                $categoryQuery->select(['category_id', 'title', 'slug', 'h1'])
                	->where(['enabled' => 1]);
            }])
            ->where(['slug' => $slug, 'status' => VideoStatus::PUBLISH])
            ->asArray()
            ->one();

            // постер для видео
        if (null !== $video && !empty($video['images'][$video['image_id']])) {
            $video['image'] = $video['images'][$video['image_id']];
        }

        return $video;
    }

    public function getRelatedById($id)
    {
            //SELECT `v`.* FROM `videos_related_map` AS `r` LEFT JOIN `videos` AS `v` ON `v`.`video_id` = `r`.`related_id` WHERE `r`.`video_id`=10
        $videos = Video::find()
            ->select('{{v}}.*')
            ->from(['r' => VideosRelatedMap::tableName()])
            ->leftJoin(['v' => Video::tableName()], '{{v}}.{{video_id}}={{r}}.{{related_id}}')
            ->with(['categories' => function ($categoryQuery) {
                $categoryQuery->select(['category_id', 'title', 'slug', 'h1'])
                	->where(['enabled' => 1]);
            }])
            ->with(['image' => function ($imageQuery) {
                $imageQuery->select(['image_id', 'video_id', 'filepath', 'source_url']);
            }])
            ->where(['`r`.`video_id`' => (int) $id, 'status' => VideoStatus::PUBLISH])
            ->limit((int) Yii::$app->getModule('videos')->settings->get('related_number', self::RELATED_NUMBER))
            ->asArray()
            ->all();

        return $videos;
    }

    /**
     * Получает ролики постранично в разделе "Новое", отсортированные по дате.
     */
    public function getNewVideos($page)
    {
        $videoQuery = Video::find()
            ->with(['categories' => function ($categoryQuery) {
                $categoryQuery->select(['category_id', 'title', 'slug', 'h1'])
                	->where(['enabled' => 1]);
            }])
            ->with(['image' => function ($imageQuery) {
                $imageQuery->select(['image_id', 'video_id', 'filepath', 'source_url']);
            }])
            ->where(['status' => VideoStatus::PUBLISH])
            ->asArray();

        $provider = new ActiveDataProvider([
            'query' => $videoQuery,
            'pagination' => [
                'pageSize' => (int) Yii::$app->getModule('videos')->settings->get('items_per_page', self::ITEMS_PER_PAGE),
            ],
            'sort' => [
                'defaultOrder' => [
                    'published_at' => SORT_DESC,
                ]
            ],
        ]);

        $this->totalItems = $provider->getTotalCount();

        return $provider->getModels();
    }

    /**
     * Получает ролики для категории.
     */
    public function getVideosFromCategory(array $category, $page = 1)
    {
        $videos = [];

        $totalTestedItems = $this->countVideosFromCategory($category['category_id'], self::TESTED_IMAGE);
        $totalTestItems = $this->countVideosFromCategory($category['category_id'], self::TEST_IMAGE);
        $this->totalItems = $totalTestedItems + $totalTestItems;//$this->countTotalItems($category->category_id);

        if ($this->totalItems <= 0) {
            return [];
        }

        $items_per_page = (int) Yii::$app->getModule('videos')->settings->get('items_per_page', self::ITEMS_PER_PAGE);
        $test_items_percent = (int) Yii::$app->getModule('videos')->settings->get('test_items_percent', self::TEST_ITEMS_PERCENT);

            // Проверим, является ли текущая страница валидной.
        $totalPages = ceil($this->totalItems / $items_per_page);
        if ($page > $totalPages) {
            throw new NotFoundHttpException();
        }

        $tested_per_page = ceil(((100 - $test_items_percent) / 100) * $items_per_page);
        $test_per_page = floor(($test_items_percent / 100) * $items_per_page);

            // Если ли вообще у нас на странице тестовые ролики.
        if ($totalTestItems === 0 || $test_per_page === 0) {
            $tested_per_page = $items_per_page;
            $test_per_page = 0;
        }

        if ($totalTestItems > 0 && $totalTestItems < $test_per_page) {
            if ($page == 1) {
                $test_per_page = $totalTestItems;
                $tested_per_page = $items_per_page - $test_per_page;
            } else {
                $test_per_page = 0;
                $tested_per_page = $items_per_page;
            }
        }

            // Высчитаем смещение и получим завершившие тест тумбы
        $testedOffset = ($page - 1) * $tested_per_page;
        $testedItems = $this->getVideosFromStats($tested_per_page, $testedOffset, $category['category_id'], self::TESTED_IMAGE);
        $actuallyTestedImagesNumber = count($testedItems);

            // Если тестовые ролики есть, найдем их и запишем в массив видео.
        if ($totalTestItems > 0 && $test_per_page > 0) {
            $testOffset = ($page - 1) * $test_per_page;

                // Если на странице нехватает завершивших тест, то доберем больше тестовых.
            if ($actuallyTestedImagesNumber < $tested_per_page) {
                    // если завершивших тест вообще нет, увеличим смещение тестовых.
                if ($actuallyTestedImagesNumber === 0 && $testOffset > 0) {
                    $a = floor($totalTestedItems / $tested_per_page);
                    $b = $totalTestedItems - ($tested_per_page * $a);
                    $testOffset += ($items_per_page - ($test_per_page + $b));
                }

                    // Доберем тестируемые.
                $test_per_page = $items_per_page - $actuallyTestedImagesNumber;
            }

            $testItems = $this->getVideosFromStats($test_per_page, $testOffset, $category['category_id'], self::TEST_IMAGE);

            $testedNum = count($testedItems);
            $testNum = count($testItems);

            $testItemStart = (int) Yii::$app->getModule('videos')->settings->get('test_items_start', self::TEST_ITEMS_START);

                // перемешаем тестовые и не тестовые местами
            if (count($testedItems) >= $testItemStart && $testNum > 0) {

                $totalItemsOnPage = $testedNum + $testNum;
                    // Вычислим места, в которых будут стоять тестовые тумбы.
                $filledArray = range(0, $totalItemsOnPage - 1);
                array_splice($filledArray, 0, $testItemStart);
                $randKeys = (array) array_rand($filledArray, $testNum);
                $testPlacePositions = array_values(array_intersect_key($filledArray, array_flip($randKeys)));

                $testPlaceIterator = new \ArrayIterator($testPlacePositions);
                //$testPlaceIterator->rewind();
                /*$testIterator = new \ArrayIterator($testItems);
                $testedIterator = new \ArrayIterator($testedItems);

                for ($i = 0; $i < $totalItemsOnPage; $i++) {
                    if ($i === $testPlaceIterator->current()) {
                        //if ($testIterator->current() != false) {
                            $videos[$testIterator->key()] = $testIterator->current();
                        //}
                        $testIterator->next();
                        $testPlaceIterator->next();
                    } else {
                        //if ($testedIterator->current() != false) {
                            $videos[$testedIterator->key()] = $testedIterator->current();
                        //}
                        $testedIterator->next();
                    }
                }*/


                for ($i = 0; $i < $totalItemsOnPage; $i++) {
                    if ($i === $testPlaceIterator->current()) {
                        $video = array_shift($testItems);

                        $testPlaceIterator->next();
                    } else {
                        $video = array_shift($testedItems);
                    }

                        // нормализуем массив видео.
                    $videos[$video['image_id']] = $video['video'];
                    $videos[$video['image_id']]['image'] = $video['image'];
                    $videos[$video['image_id']]['categories'] = $video['categories'];
                }


            } else {
                $items = $testedItems + $testItems;
                foreach ($items as $item) {
                    $videos[$item['image_id']] = $item['video'];
                    $videos[$item['image_id']]['image'] = $item['image'];
                    $videos[$item['image_id']]['categories'] = $item['categories'];
                }
            }

        } else {
            //$videos = $testedItems;
            foreach ($testedItems as $item) {
                $videos[$item['image_id']] = $item['video'];
                $videos[$item['image_id']]['image'] = $item['image'];
                $videos[$item['image_id']]['categories'] = $item['categories'];
            }
        }

        return $videos;
    }

    private function countTotalItems($category_id)
    {
        $count = RotationStats::find()
            ->joinWith('video')
            ->andWhere([
                'category_id' => $category_id,
                'best_image' => 1,
                'status' => VideoStatus::PUBLISH,
            ])
            ->count();

        $this->totalItems = $count;

        return $count;
    }

    private function countVideosFromCategory($category_id, $tested = null)
    {
        $counter = RotationStats::find()
            ->joinWith('video')
            ->andWhere([
                'category_id' => $category_id,
                'best_image' => 1,
                //'tested_image' => $tested,
                //'status' => VideoStatus::PUBLISH,
            ]);
         if ($tested !== null) {
             $counter->andWhere(['tested_image' => $tested]);
         }

         $count = $counter
             ->andWhere(['status' => VideoStatus::PUBLISH])
            ->count();

        return (int) $count;
    }

    private function getVideosFromStats($items_per_page, $offset, $category_id, $tested = null)
    {
        return RotationStats::find()
            ->joinWith('video')
            ->with(['image' => function ($imageQuery) {
                $imageQuery->select(['image_id', 'video_id', 'filepath', 'source_url']);
            }])
            ->with(['categories' => function ($categoryQuery) {
                $categoryQuery->select(['category_id', 'title', 'slug', 'h1'])
                	->where(['enabled' => 1]);
            }])
            ->andWhere([
                'category_id' =>  $category_id,
                'best_image' => 1,
                'tested_image' => $tested,
                'status' => VideoStatus::PUBLISH,
            ])
            ->limit($items_per_page)
            ->offset($offset)
            ->orderBy($this->getSort()->getOrders())
            //->indexBy('image_id')
            ->asArray()
            ->all();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Video::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'video_id' => $this->video_id,
            'image_id' => $this->image_id,
            'user_id' => $this->user_id,
            'orientation' => $this->orientation,
            'duration' => $this->duration,
            'on_index' => $this->on_index,
            'likes' => $this->likes,
            'dislikes' => $this->dislikes,
            'comments_num' => $this->comments_num,
            'views' => $this->views,
            'status' => $this->status,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'slug', $this->slug])
            ->andFilterWhere(['like', 'title', $this->title])
            ->andFilterWhere(['like', 'description', $this->description])
            ->andFilterWhere(['like', 'short_description', $this->short_description])
            ->andFilterWhere(['like', 'video_url', $this->video_url])
            ->andFilterWhere(['like', 'embed', $this->embed]);

        return $dataProvider;
    }

    public function getSort()
    {
        if (null === $this->sort) {
            $this->sort = new Sort([
                'attributes' => [
                    'popular' => [
                        //'asc' => ['ctr' => SORT_ASC],
                        'desc' => ['ctr' => SORT_DESC],
                        'default' => SORT_DESC,
                        'label' => 'popular',
                    ],
                    'new' => [
                        'asc' => ['published_at' => SORT_DESC],
                        //'desc' => ['published_at' => SORT_DESC],
                        'default' => SORT_DESC,
                        'label' => 'date',
                    ],
                ],
                'defaultOrder' => [
                    'popular' => SORT_DESC,
                ],
            ]);
        }

        return $this->sort;
    }

    /**
     * Возвращает количество постов последнего запроса.
     *
     * @return int
     */
    public function totalCount()
    {
        if ($this->totalItems !== null)
            return (int) $this->totalItems;
        else
            return 0;
    }
}
