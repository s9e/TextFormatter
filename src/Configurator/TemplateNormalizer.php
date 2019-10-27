<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license'); The MIT License
*/
namespace s9e\TextFormatter\Configurator;
use ArrayAccess;
use Iterator;
use s9e\TextFormatter\Configurator\Collections\TemplateNormalizationList;
use s9e\TextFormatter\Configurator\Helpers\TemplateLoader;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;
class TemplateNormalizer implements ArrayAccess, Iterator
{
	public function __call($methodName, $args)
	{
		return \call_user_func_array(array($this->collection, $methodName), $args);
	}
	public function offsetExists($offset)
	{
		return isset($this->collection[$offset]);
	}
	public function offsetGet($offset)
	{
		return $this->collection[$offset];
	}
	public function offsetSet($offset, $value)
	{
		$this->collection[$offset] = $value;
	}
	public function offsetUnset($offset)
	{
		unset($this->collection[$offset]);
	}
	public function count()
	{
		return \count($this->collection);
	}
	public function current()
	{
		return $this->collection->current();
	}
	public function key()
	{
		return $this->collection->key();
	}
	public function next()
	{
		return $this->collection->next();
	}
	public function rewind()
	{
		$this->collection->rewind();
	}
	public function valid()
	{
		return $this->collection->valid();
	}
	protected $collection;
	protected $defaultNormalizations = array(
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
	);
	protected $maxIterations = 100;
	public function __construct(array $normalizations = \null)
	{
		if (!isset($normalizations))
			$normalizations = $this->defaultNormalizations;
		$this->collection = new TemplateNormalizationList;
		foreach ($normalizations as $normalization)
			$this->collection->append($normalization);
	}
	public function normalizeTag(Tag $tag)
	{
		if (isset($tag->template) && !$tag->template->isNormalized())
			$tag->template->normalize($this);
	}
	public function normalizeTemplate($template)
	{
		$dom = TemplateLoader::load($template);
		$i = 0;
		do
		{
			$old = $template;
			foreach ($this->collection as $k => $normalization)
				$normalization->normalize($dom->documentElement);
			$template = TemplateLoader::save($dom);
		}
		while (++$i < $this->maxIterations && $template !== $old);
		return $template;
	}
}