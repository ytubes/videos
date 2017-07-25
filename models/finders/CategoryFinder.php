<?php
namespace ytubes\videos\models\finders;

use yii\base\Model;

use ytubes\videos\models\Video;
use ytubes\videos\models\Category;
use ytubes\videos\models\RotationStats;

/**
 * CategoryFinder представляет собой импровизированное репо для категорий.
 */
class CategoryFinder extends Model
{
	protected $categoriesIndexedById;
	protected $categoriesIndexedBySlug;
	protected $categoriesIdsNamesArray;

	public function __construct($config = [])
	{
		parent::__construct($config);
	}

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

	public static function find()
	{
		return Category::find();
	}

	/*Используется*/
	public function findBy($where, $order = [])
	{
		return self::find()
			->where($where)
			->orderBy($order)
			->asArray()
			->all();
	}
	/**
	 * Возвращает все активные категории
	 * @param string|array $order сортировка категорий.
	 * @return array
	 */
	public static function getActiveCategories($order = [])
	{
		return self::find()
			->select([
				'category_id',
				'slug',
				'image',
				'meta_title',
				'meta_description',
				'title',
				'h1',
				'description',
				'seotext',
				'param1',
				'param2',
				'param3',
				'items_count',
				'on_index',
			])
			//->where(['status' => 10])
			->orderBy($order)
			->asArray()
			->all();
	}

	/*Используется*/
	public static function findById($id)
	{
		return self::find()
			->where(['category_id' => $id/*, 'status' => 10*/])
			->asArray()
			->one();
	}

	/*Используется*/
	public static function findBySlug($slug)
	{
		return self::find()
			->where(['slug' => $slug/*, 'status' => 10*/])
			->asArray()
			->one();
	}

	public function getCategoriesIndexedById()
	{
		if (null === $this->categoriesIndexedById) {
			$this->categoriesIndexedById = self::find()
				->indexBy('category_id')
				->all();
		}

		return $this->categoriesIndexedById;
	}

	public function getCategoryBySlug($slug)
	{
		$this->getCategoriesIndexedBySlug();

		if (isset($this->categoriesIndexedBySlug[$slug])) {
			return $this->categoriesIndexedBySlug[$slug];
		}

		return null;
	}

	public function getCategoriesIndexedBySlug()
	{
		if (null === $this->categoriesIndexedBySlug) {
			$this->categoriesIndexedBySlug = self::find()
				->indexBy('slug')
				->all();
		}

		return $this->categoriesIndexedBySlug;
	}

	public function getCategoryNameById($id)
	{
		$this->getCategoriesIdsNamesArray();

		if (isset($this->categoriesIdsNamesArray[$id])) {
			return $this->categoriesIdsNamesArray[$id];
		}

		return null;
	}

	public function getCategoriesIdsNamesArray()
	{
		if (null === $this->categoriesIdsNamesArray) {
			$categories = self::find()
				->select(['category_id', 'title'])
				->indexBy('category_id')
				->asArray()
				->all();

			if (!empty($categories)) {
				$this->categoriesIdsNamesArray = array_column($categories, 'title', 'category_id');
			} else {
				$this->categoriesIdsNamesArray = [];
			}
		}

		return $this->categoriesIdsNamesArray;
	}
}
