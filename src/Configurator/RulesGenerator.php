<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
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
* @method BooleanRulesGenerator|TargetedRulesGenerator normalizeValue(string|BooleanRulesGenerator|TargetedRulesGenerator $generator)
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
class RulesGenerator implements ArrayAccess, Iterator
{
	use CollectionProxy;

	/**
	* @var RulesGeneratorList Collection of objects
	*/
	protected $collection;

	/**
	* Constructor
	*
	* Will load the default rule generators
	*/
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

	/**
	* Generate rules for given tag collection
	*
	* @param  TagCollection $tags Tags collection
	* @return array
	*/
	public function getRules(TagCollection $tags)
	{
		// Create a proxy for the parent markup so that we can determine which tags are allowed at
		// the root of the text (IOW, with no parent) or even disabled altogether
		$rootInspector = new TemplateInspector('<div><xsl:apply-templates/></div>');

		// Study the tags
		$templateInspector = [];
		foreach ($tags as $tagName => $tag)
		{
			// Use the tag's template if applicable or XSLT's implicit default otherwise
			$template = (isset($tag->template)) ? $tag->template : '<xsl:apply-templates/>';
			$templateInspector[$tagName] = new TemplateInspector($template);
		}

		// Generate a full set of rules
		$rules = $this->generateRulesets($templateInspector, $rootInspector);

		// Remove root rules that wouldn't be applied anyway
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

	/**
	* Generate and return rules based on a set of TemplateInspector
	*
	* @param  array             $templateInspector Array of [tagName => TemplateInspector]
	* @param  TemplateInspector $rootInspector     TemplateInspector for the root of the text
	* @return array
	*/
	protected function generateRulesets(array $templateInspector, TemplateInspector $rootInspector)
	{
		$rules = [
			'root' => $this->generateRuleset($rootInspector, $templateInspector),
			'tags' => []
		];
		foreach ($templateInspector as $tagName => $src)
		{
			$rules['tags'][$tagName] = $this->generateRuleset($src, $templateInspector);
		}

		return $rules;
	}

	/**
	* Generate a set of rules for a single TemplateInspector instance
	*
	* @param  TemplateInspector $src     Source of the rules
	* @param  array             $targets Array of [tagName => TemplateInspector]
	* @return array
	*/
	protected function generateRuleset(TemplateInspector $src, array $targets)
	{
		$rules = [];
		foreach ($this->collection as $rulesGenerator)
		{
			if ($rulesGenerator instanceof BooleanRulesGenerator)
			{
				foreach ($rulesGenerator->generateBooleanRules($src) as $ruleName => $bool)
				{
					$rules[$ruleName] = $bool;
				}
			}

			if ($rulesGenerator instanceof TargetedRulesGenerator)
			{
				foreach ($targets as $tagName => $trg)
				{
					foreach ($rulesGenerator->generateTargetedRules($src, $trg) as $ruleName)
					{
						$rules[$ruleName][] = $tagName;
					}
				}
			}
		}

		return $rules;
	}
}