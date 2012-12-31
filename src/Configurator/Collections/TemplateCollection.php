<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\Template;

class TemplateCollection extends NormalizedCollection
{
	/**
	* @var Tag Tag used by TemplateChecker to assess the safeness of attributes used in templates
	*/
	protected $tag;

	/**
	* Constructor
	*
	* @param Tag $tag Tag that these templates belong to
	*/
	public function __construct(Tag $tag)
	{
		$this->tag = $tag;
	}

	/**
	* Normalize a template for storage
	*
	* @param  mixed    $template Either a string, a callback or an instance of Template
	* @return Template           An instance of Template
	*/
	public function normalizeValue($template)
	{
		// Create an instance of Template if it's not one
		if (!($template instanceof Template))
		{
			// Normalize the template if it's a string
			if (is_string($template))
			{
				$template = TemplateHelper::normalize($template, $this->tag);
			}

			$template = new Template($template);
		}

		return $template;
	}
}