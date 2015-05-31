<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use ArrayAccess;
use DOMDocument;
use Iterator;
use s9e\TextFormatter\Configurator\Collections\RulesGeneratorList;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\TargetedRulesGenerator;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;

class RulesGenerator implements ArrayAccess, Iterator
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
		$this->collection = new RulesGeneratorList;
		$this->collection->append('AutoCloseIfVoid');
		$this->collection->append('AutoReopenFormattingElements');
		$this->collection->append('BlockElementsFosterFormattingElements');
		$this->collection->append('DisableAutoLineBreaksIfNewLinesArePreserved');
		$this->collection->append('EnforceContentModels');
		$this->collection->append('EnforceOptionalEndTags');
		$this->collection->append('IgnoreTagsInCode');
		$this->collection->append('IgnoreTextIfDisallowed');
		$this->collection->append('IgnoreWhitespaceAroundBlockElements');
		$this->collection->append('TrimFirstLineInCodeBlocks');
	}

	public function getRules(TagCollection $tags, array $options = array())
	{
		$parentHTML = (isset($options['parentHTML'])) ? $options['parentHTML'] : '<div>';

		$rootForensics = $this->generateRootForensics($parentHTML);

		$templateForensics = array();
		foreach ($tags as $tagName => $tag)
		{
			$template = (isset($tag->template)) ? $tag->template : '<xsl:apply-templates/>';
			$templateForensics[$tagName] = new TemplateForensics($template);
		}

		$rules = $this->generateRulesets($templateForensics, $rootForensics);

		unset($rules['root']['autoClose']);
		unset($rules['root']['autoReopen']);
		unset($rules['root']['breakParagraph']);
		unset($rules['root']['closeAncestor']);
		unset($rules['root']['closeParent']);
		unset($rules['root']['fosterParent']);
		unset($rules['root']['ignoreSurroundingWhitespace']);
		unset($rules['root']['isTransparent']);
		unset($rules['root']['requireAncestor']);
		unset($rules['root']['requireParent']);

		return $rules;
	}

	protected function generateRootForensics($html)
	{
		$dom = new DOMDocument;
		$dom->loadHTML($html);

		$body = $dom->getElementsByTagName('body')->item(0);

		$node = $body;
		while ($node->firstChild)
			$node = $node->firstChild;

		$node->appendChild($dom->createElementNS(
			'http://www.w3.org/1999/XSL/Transform',
			'xsl:apply-templates'
		));

		return new TemplateForensics($dom->saveXML($body));
	}

	protected function generateRulesets(array $templateForensics, TemplateForensics $rootForensics)
	{
		$rules = array(
			'root' => $this->generateRuleset($rootForensics, $templateForensics),
			'tags' => array()
		);

		foreach ($templateForensics as $tagName => $src)
			$rules['tags'][$tagName] = $this->generateRuleset($src, $templateForensics);

		return $rules;
	}

	protected function generateRuleset(TemplateForensics $src, array $targets)
	{
		$rules = array();

		foreach ($this->collection as $rulesGenerator)
		{
			if ($rulesGenerator instanceof BooleanRulesGenerator)
				foreach ($rulesGenerator->generateBooleanRules($src) as $ruleName => $bool)
					$rules[$ruleName] = $bool;

			if ($rulesGenerator instanceof TargetedRulesGenerator)
				foreach ($targets as $tagName => $trg)
					foreach ($rulesGenerator->generateTargetedRules($src, $trg) as $ruleName)
						$rules[$ruleName][] = $tagName;
		}

		return $rules;
	}
}