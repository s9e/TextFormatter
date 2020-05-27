<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown\Parser;

use s9e\TextFormatter\Parser\Tag;

class Slugger
{
	public static function getJS(): string
	{
		return file_get_contents(__DIR__ . '/Slugger.js');
	}

	public static function setTagSlug(Tag $tag, string $innerText): void
	{
		$slug = strtolower($innerText);
		$slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
		$slug = trim($slug, '-');
		if ($slug !== '')
		{
			$tag->setAttribute('slug', $slug);
		}
	}
}