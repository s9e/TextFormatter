<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\TemplatePlaceholder;

class Templateset extends NormalizedCollection
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
	* @param  string|TemplatePlaceholder $template Original template
	* @return mixed                                Normalized template
	*/
	public function normalizeValue($template)
	{
		if ($template instanceof TemplatePlaceholder)
		{
			if ($template->allowsUnsafeMarkup())
			{
				throw new InvalidArgumentException('Cannot add unsafe template');
			}

			return $template;
		}

		return TemplateHelper::normalize($template, $this->tag);
	}

	/**
	* Set a template without checking it for unsafe markup
	*
	* @param  string                     $predicate Template's predicate
	* @param  string|TemplatePlaceholder $template  Template's content
	* @return void
	*/
	public function setUnsafe($predicate, $template)
	{
		$predicate = $this->normalizeKey($predicate);

		if ($template instanceof TemplatePlaceholder)
		{
			$template->disableTemplateChecking();
		}
		else
		{
			$template  = TemplateHelper::normalizeUnsafe($template, $this->tag);
		}

		$this->items[$predicate] = $template;
	}
}