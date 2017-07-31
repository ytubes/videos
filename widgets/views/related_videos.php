<?php

use yii\helpers\Url;
use yii\helpers\Html;

?>

<div class="thumbs">
	<?php foreach ($data['videos'] as $video): ?>
		<?php $videoTitle = Html::encode($video['title']);?>

		<div class="item">
			<div class="inner">
				<a href="<?= Url::to(['/videos/view/index', 'slug' => $video['slug']]) ?>" title="<?= $videoTitle ?>" class="thumb-link">
					<em class="counter"><?= ltrim(gmdate('H:i:s', $video['duration']), '0:') ?></em>
					<img src="http://img.24fastload.com/t<?= $video['image']['filepath'] ?>" class="content-list__img" alt="<?= $videoTitle ?>" itemprop="image">
					<span class="thumb-desc"><?= $videoTitle ?></span>
				</a>
			</div>
		</div>
	<?php endforeach; ?>
</div>