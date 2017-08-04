<?php
namespace ytubes\videos\models\finders;

use ytubes\videos\models\Video;
use ytubes\videos\models\Category;
use ytubes\videos\models\RotationStats;

/**
 * CategoryFinder представляет собой импровизированное репо для категорий.
 */
class CategoryFinder
{
    protected $categoriesIndexedById;
    protected $categoriesIndexedBySlug;
    protected $categoriesIdsNamesArray;

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
     *
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
                'videos_num',
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
}
