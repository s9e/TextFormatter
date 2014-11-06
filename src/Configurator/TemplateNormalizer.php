<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license'); The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use ArrayAccess;
use Iterator;
use s9e\TextFormatter\Configurator\Collections\TemplateNormalizationList;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
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

	public function __construct()
	{
		$this->collection = new TemplateNormalizationList;

		$this->collection->append('InlineAttributes');
		$this->collection->append('InlineCDATA');
		$this->collection->append('InlineElements');
		$this->collection->append('InlineInferredValues');
		$this->collection->append('InlineTextElements');
		$this->collection->append('InlineXPathLiterals');
		$this->collection->append('MinifyXPathExpressions');
		$this->collection->append('NormalizeAttributeNames');
		$this->collection->append('NormalizeElementNames');
		$this->collection->append('NormalizeUrls');
		$this->collection->append('OptimizeConditionalAttributes');
		$this->collection->append('OptimizeConditionalValueOf');
		$this->collection->append('PreserveSingleSpaces');
		$this->collection->append('RemoveComments');
		$this->collection->append('RemoveInterElementWhitespace');
	}

	public function normalizeTag(Tag $tag)
	{
		if (isset($tag->template) && !$tag->template->isNormalized())
			$tag->template->normalize($this);
	}

	public function normalizeTemplate($template)
	{
		$dom = TemplateHelper::loadTemplate($template);

		$applied = array();

		$loops = 5;
		do
		{
			$old = $template;

			foreach ($this->collection as $k => $normalization)
			{
				if (isset($applied[$k]) && !empty($normalization->onlyOnce))
					continue;

				$normalization->normalize($dom->documentElement);
				$applied[$k] = 1;
			}

			$template = TemplateHelper::saveTemplate($dom);
		}
		while (--$loops && $template !== $old);

		return $template;
	}
}