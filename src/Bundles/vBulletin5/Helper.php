<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Bundles\vBulletin5;

use s9e\TextFormatter\Parser\Tag;

class Helper
{
	/**
	* Filter code tags to handle [html] and [php] BBCodes seamlessly
	*
	* Will interpret [html] and [php] as [code=html] and [code=php]
	*
	* @param  Tag    $tag
	* @param  string $tagText
	* @return void
	*/
	public static function filterCodeTag(Tag $tag, string $tagText): void
	{
		if (preg_match('/^\\[(html|php)\\]$/i', $tagText, $m))
		{
			$tag->setAttribute('lang', $m[1]);
		}
	}
}