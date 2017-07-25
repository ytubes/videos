<?php

use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Шаблон отвечает за вывод блока категорий категорий внизу страницы.
 */

	// Если категория пустая, просто не будем рендерить шаблон
if (empty($data['categories']))
	return;

?>

<h2 class="title">Категории</h2>
<div class="box">
	<ul>
		<?php foreach ($data['categories'] as $category): ?>
			<?php $categoryTitle = Html::encode($category['title'])?>
			<li><?= Html::a($categoryTitle, ['/video/category/index', 'slug' => $category['slug']], [
					'title' => $categoryTitle,
					'class' => ($category['category_id'] === $data['active_id']) ? 'active' : null,
				]) ?></li>
		<?php endforeach ?>
	</ul>
</div>
