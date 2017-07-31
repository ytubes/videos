<?php
namespace ytubes\videos\widgets;

use Yii;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;

use ytubes\videos\models\finders\CategoryFinder;

class Categories extends \yii\base\Widget
{
	private $cacheKey = 'widget:categories:';
    /**
     * @var int Идентификатор текущей активной категории;
     */
    public $active_id = null;
	/**
	 * @var string путь к темплейту виджета
	 */
	public $template = __DIR__ . '/views/categories.php';
	/**
	 * @var array|string сортировка элементов
	 * Можно использовать следующие параметры:
     * - category_id: integer, идентификатор категории
	 * - title: string, название
	 * - position: integer, порядковый номер при ручной сортировке
     * - ctr: float, рассчитаный цтр по кликабельности категории.
	 */
	public $order = 'title';
	/**
	 * @var array Коллекция массивов категорий.
	 */
	public $items = [];

	/**
	 * Initializes the widget
	 */
	public function init() {
		parent::init();

		if (!in_array($this->order, ['category_id', 'title', 'position', 'ctr'])) {
			$this->order = 'title';
		}

		if ($this->active_id !== null) {
			$this->active_id = (string) $this->active_id;
		}
	}

	/**
	 * Runs the widget
	 *
	 * @return string|void
	 */
	public function run() {

		$categories = $this->getItems();

		if (empty($categories)) {
			return;
		}

		return $this->renderFile($this->template, [
        	'data' => [
        		'categories' => $categories,
            	'active_id' => $this->active_id,
            ],
		]);
	}

    private function getItems()
    {
    	$cacheKey = $this->buildCacheKey();

    	$items = Yii::$app->cache->get($cacheKey);

    	if (false === $items) {
    		$items = CategoryFinder::getActiveCategories($this->order);

    		if (!empty($items)) {
    			Yii::$app->cache->set($cacheKey, $items, 300);
    		}
    	}

    	return $items;
    }

    private function buildCacheKey()
    {
    	return $this->cacheKey . $this->order;
    }
}
