<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Autoimage;

use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Configurator extends ConfiguratorBase
{
	/**
	* @var string Name of attribute that stores the image's URL
	*/
	protected $attrName = 'src';

	/**
	* @var string
	*/
	protected $quickMatch = '://';

	/**
	* @var string
	*/
	protected $regexp = '#\\bhttps?://[-.\\w]+/(?:[-+.:/\\w]|%[0-9a-f]{2}|\\(\\w+\\))+\\.(?:gif|jpe?g|png|svgz?|webp)(?!\\S)#i';

	/**
	* @var string Name of the tag used to represent images
	*/
	protected $tagName = 'IMG';

	/**
	* Creates the tag used by this plugin
	*
	* @return void
	*/
	protected function setUp()
	{
		if (isset($this->configurator->tags[$this->tagName]))
		{
			return;
		}

		// Create a tag
		$tag = $this->configurator->tags->add($this->tagName);

		// Add an attribute using the default url filter
		$filter = $this->configurator->attributeFilters->get('#url');
		$tag->attributes->add($this->attrName)->filterChain->append($filter);

		// Set the default template
		$tag->template = '<img src="{@' . $this->attrName . '}"/>';
	}
}