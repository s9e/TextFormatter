<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license'); The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use ArrayAccess;
use Iterator;
use s9e\TextFormatter\Configurator\Collections\TemplateNormalizationList;
use s9e\TextFormatter\Configurator\Helpers\TemplateLoader;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;

/**
* @method mixed   add(mixed $value, null $void)  Add (append) a value to this list
* @method mixed   append(mixed $value)           Append a value to this list
* @method array   asConfig()
* @method void    clear()                        Empty this collection
* @method bool    contains(mixed $value)         Test whether a given value is present in this collection
* @method integer count()
* @method mixed   current()
* @method void    delete(string $key)            Delete a value from this list and remove gaps in keys
* @method bool    exists(string $key)            Test whether an item of given key exists
* @method mixed   get(string $key)               Return a value from this collection
* @method mixed   indexOf(mixed $value)          Find the index of a given value
* @method mixed   insert(integer $offset, mixed $value) Insert a value at an arbitrary 0-based position
* @method integer|string key()
* @method mixed   next()
* @method integer normalizeKey(mixed $key)       Ensure that the key is a valid offset
* @method AbstractNormalization normalizeValue(mixed $value)   Normalize the value to an instance of AbstractNormalization
* @method bool    offsetExists(string|integer $offset)
* @method mixed   offsetGet(string|integer $offset)
* @method void    offsetSet(mixed $offset, mixed $value) Custom offsetSet() implementation to allow assignment with a null offset to append to the
* @method void    offsetUnset(string|integer $offset)
* @method string  onDuplicate(string|null $action) Query and set the action to take when add() is called with a key that already exists
* @method mixed   prepend(mixed $value)          Prepend a value to this list
* @method integer remove(mixed $value)           Remove all items matching given value
* @method void    rewind()
* @method mixed   set(string $key, mixed $value) Set and overwrite a value in this collection
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
	* @var string[] Default list of normalizations
	*/
	protected $defaultNormalizations = [
		'PreserveSingleSpaces',
		'RemoveComments',
		'RemoveInterElementWhitespace',
		'NormalizeElementNames',
		'FixUnescapedCurlyBracesInHtmlAttributes',
		'EnforceHTMLOmittedEndTags',
		'InlineCDATA',
		'InlineElements',
		'InlineTextElements',
		'UninlineAttributes',
		'MinifyXPathExpressions',
		'NormalizeAttributeNames',
		'OptimizeConditionalAttributes',
		'FoldArithmeticConstants',
		'FoldConstantXPathExpressions',
		'InlineXPathLiterals',
		'DeoptimizeIf',
		'OptimizeChooseDeadBranches',
		'OptimizeChooseText',
		'OptimizeChoose',
		'OptimizeConditionalValueOf',
		'InlineAttributes',
		'NormalizeUrls',
		'InlineInferredValues',
		'RenameLivePreviewEvent',
		'SetRelNoreferrerOnTargetedLinks',
		'MinifyInlineCSS'
	];

	/**
	* @var integer Maximum number of iterations over a given template
	*/
	protected $maxIterations = 100;

	/**
	* Constructor
	*
	* Will load the default normalization rules if no list is passed
	*
	* @param array $normalizations List of normalizations
	*/
	public function __construct(array $normalizations = null)
	{
		if (!isset($normalizations))
		{
			$normalizations = $this->defaultNormalizations;
		}

		$this->collection = new TemplateNormalizationList;
		foreach ($normalizations as $normalization)
		{
			$this->collection->append($normalization);
		}
	}

	/**
	* Normalize a tag's template
	*
	* @param  Tag  $tag Tag whose template will be normalized
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
		$dom = TemplateLoader::load($template);

		// Apply all the normalizations until no more change is made or we've reached the maximum
		// number of loops
		$i = 0;
		do
		{
			$old = $template;
			foreach ($this->collection as $k => $normalization)
			{
				$normalization->normalize($dom->documentElement);
			}
			$template = TemplateLoader::save($dom);
		}
		while (++$i < $this->maxIterations && $template !== $old);

		return $template;
	}
}