<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
use ArrayAccess;
use DOMDocument;
use Iterator;
use s9e\TextFormatter\Configurator\Collections\RulesGeneratorList;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\Helpers\TemplateInspector;
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
		$this->collection->append('BlockElementsCloseFormattingElements');
		$this->collection->append('BlockElementsFosterFormattingElements');
		$this->collection->append('DisableAutoLineBreaksIfNewLinesArePreserved');
		$this->collection->append('EnforceContentModels');
		$this->collection->append('EnforceOptionalEndTags');
		$this->collection->append('IgnoreTagsInCode');
		$this->collection->append('IgnoreTextIfDisallowed');
		$this->collection->append('IgnoreWhitespaceAroundBlockElements');
		$this->collection->append('TrimFirstLineInCodeBlocks');
	}
	public function getRules(TagCollection $tags)
	{
		$tagInspectors = $this->getTagInspectors($tags);
		return array(
			'root' => $this->generateRootRules($tagInspectors),
			'tags' => $this->generateTagRules($tagInspectors)
		);
	}
	protected function generateTagRules(array $tagInspectors)
	{
		$rules = array();
		foreach ($tagInspectors as $tagName => $tagInspector)
			$rules[$tagName] = $this->generateRuleset($tagInspector, $tagInspectors);
		return $rules;
	}
	protected function generateRootRules(array $tagInspectors)
	{
		$rootInspector = new TemplateInspector('<div><xsl:apply-templates/></div>');
		$rules         = $this->generateRuleset($rootInspector, $tagInspectors);
		unset($rules['autoClose']);
		unset($rules['autoReopen']);
		unset($rules['breakParagraph']);
		unset($rules['closeAncestor']);
		unset($rules['closeParent']);
		unset($rules['fosterParent']);
		unset($rules['ignoreSurroundingWhitespace']);
		unset($rules['isTransparent']);
		unset($rules['requireAncestor']);
		unset($rules['requireParent']);
		return $rules;
	}
	protected function generateRuleset(TemplateInspector $srcInspector, array $trgInspectors)
	{
		$rules = array();
		foreach ($this->collection as $rulesGenerator)
		{
			if ($rulesGenerator instanceof BooleanRulesGenerator)
				foreach ($rulesGenerator->generateBooleanRules($srcInspector) as $ruleName => $bool)
					$rules[$ruleName] = $bool;
			if ($rulesGenerator instanceof TargetedRulesGenerator)
				foreach ($trgInspectors as $tagName => $trgInspector)
				{
					$targetedRules = $rulesGenerator->generateTargetedRules($srcInspector, $trgInspector);
					foreach ($targetedRules as $ruleName)
						$rules[$ruleName][] = $tagName;
				}
		}
		return $rules;
	}
	protected function getTagInspectors(TagCollection $tags)
	{
		$tagInspectors = array();
		foreach ($tags as $tagName => $tag)
		{
			$template = (isset($tag->template)) ? $tag->template : '<xsl:apply-templates/>';
			$tagInspectors[$tagName] = new TemplateInspector($template);
		}
		return $tagInspectors;
	}
}