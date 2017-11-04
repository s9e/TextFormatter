<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license'); The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use ArrayAccess;
use Iterator;
use s9e\TextFormatter\Configurator\Collections\TemplateNormalizationList;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;

/**
* @method mixed   add(mixed $value, null $void)
* @method mixed   append(mixed $value)
* @method array   asConfig()
* @method void    clear()
* @method bool    contains(mixed $value)
* @method integer count()
* @method mixed   current()
* @method void    delete(string $key)
* @method bool    exists(string $key)
* @method mixed   get(string $key)
* @method mixed   indexOf(mixed $value)
* @method mixed   insert(integer $offset, mixed $value)
* @method integer|string key()
* @method mixed   next()
* @method integer normalizeKey(mixed $key)
* @method TemplateNormalization normalizeValue(mixed $value)
* @method bool    offsetExists(string|integer $offset)
* @method mixed   offsetGet(string|integer $offset)
* @method void    offsetSet(mixed $offset, mixed $value)
* @method void    offsetUnset(string|integer $offset)
* @method string  onDuplicate(string|null $action)
* @method mixed   prepend(mixed $value)
* @method integer remove(mixed $value)
* @method void    rewind()
* @method mixed   set(string $key, mixed $value)
* @method bool    valid()
*/
class TemplateNormalizer implements ArrayAccess, Iterator
{
	use CollectionProxy;

	/**
	* @var TemplateNormalizationList Collection of TemplateNormalization instances
	*/
	protected $collection;

	/**
	* @var integer Maximum number of iterations over a given template
	*/
	protected $maxIterations = 5;

	/**
	* Constructor
	*
	* Will load the default normalization rules
	*/
	public function __construct()
	{
		$this->collection = new TemplateNormalizationList;

		$this->collection->append('PreserveSingleSpaces');
		$this->collection->append('RemoveComments');
		$this->collection->append('RemoveInterElementWhitespace');
		$this->collection->append('FixUnescapedCurlyBracesInHtmlAttributes');
		$this->collection->append('UninlineAttributes');
		$this->collection->append('FoldArithmeticConstants');
		$this->collection->append('FoldConstantXPathExpressions');
		$this->collection->append('InlineCDATA');
		$this->collection->append('InlineElements');
		$this->collection->append('InlineTextElements');
		$this->collection->append('InlineXPathLiterals');
		$this->collection->append('MinifyXPathExpressions');
		$this->collection->append('NormalizeAttributeNames');
		$this->collection->append('NormalizeElementNames');
		$this->collection->append('NormalizeUrls');
		$this->collection->append('OptimizeConditionalAttributes');
		$this->collection->append('OptimizeConditionalValueOf');
		$this->collection->append('OptimizeChoose');
		$this->collection->append('InlineAttributes');
		$this->collection->append('InlineInferredValues');
		$this->collection->append('SetRelNoreferrerOnTargetedLinks');
	}

	/**
	* Normalize a tag's templates
	*
	* @param  Tag  $tag Tag whose templates will be normalized
	* @return void
	*/
	public function normalizeTag(Tag $tag)
	{
		if (isset($tag->template) && !$tag->template->isNormalized())
		{
			$tag->template->normalize($this);
		}
	}

	/**
	* Normalize a template
	*
	* @param  string $template Original template
	* @return string           Normalized template
	*/
	public function normalizeTemplate($template)
	{
		$dom = TemplateHelper::loadTemplate($template);

		// Apply all the normalizations until no more change is made or we've reached the maximum
		// number of loops
		$i = 0;
		do
		{
			$old = $template;

			foreach ($this->collection as $k => $normalization)
			{
				if ($i > 0 && !empty($normalization->onlyOnce))
				{
					continue;
				}

				$normalization->normalize($dom->documentElement);
			}

			$template = TemplateHelper::saveTemplate($dom);
		}
		while (++$i < $this->maxIterations && $template !== $old);

		return $template;
	}
}