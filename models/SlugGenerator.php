<?php
namespace ytubes\videos\models;

use URLify;

trait SlugGenerator
{
	/**
	 * Генерирует slug исходя из title. Также присоединяет численный суффикс, если слаг не уникален.
	 *
	 * @param string $title
	 * @return string
	 */
	public function generateSlug($title)
	{
		$slug = URLify::filter($title);

		if (!$slug)
			$slug = 'default-slug';

			// если слаг существует, добавляем к нему суффикс, до тех пор пока не станет уникальным.
		if ($this->checkExistsSlug($slug)) {
			for ($suffix = 1; $this->checkExistsSlug($new_slug = $slug . '-' . $suffix); $suffix++ ) {}
			$slug = $new_slug;
		}

		$this->slug = $slug;
	}
	/**
	 * Проверяет существует ли слаг.
	 *
	 * @param string $slug
	 * @return bool
	 */
	private function checkExistsSlug($slug)
	{
		return (boolean) self::find()
			->where(['slug' => $slug])
			->exists();
	}
}