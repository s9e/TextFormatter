<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown\Parser;

use s9e\TextFormatter\Parser\Tag;

trait LinkAttributesSetter
{
	/**
	* Set a URL or IMG tag's attributes
	*
	* @param  Tag    $tag      URL or IMG tag
	* @param  string $linkInfo Link's info: an URL optionally followed by spaces and a title
	* @param  string $attrName Name of the URL attribute
	* @return void
	*/
	protected function setLinkAttributes(Tag $tag, $linkInfo, $attrName)
	{
		$url   = trim($linkInfo);
		$title = '';
		$pos   = strpos($url, ' ');
		if ($pos !== false)
		{
			$title = substr(trim(substr($url, $pos)), 1, -1);
			$url   = substr($url, 0, $pos);
		}
		if (preg_match('/^<.+>$/', $url))
		{
			$url = str_replace('\\>', '>', substr($url, 1, -1));
		}

		$tag->setAttribute($attrName, $this->text->decode($url));
		if ($title > '')
		{
			$tag->setAttribute('title', $this->text->decode($title));
		}
	}
}