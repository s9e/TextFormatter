<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
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
* @method BooleanRulesGenerator|TargetedRulesGenerator normalizeValue(string|BooleanRulesGenerator|TargetedRulesGenerator $generator) Normalize the value to an object
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

	/**
	* Generate rules for given tag collection
	*
	* @param  TagCollection $tags Tags collection
	* @return array
	*/
	public function getRules(TagCollection $tags)
	{
		$tagInspectors = $this->getTagInspectors($tags);

		return [
			'root' => $this->generateRootRules($tagInspectors),
			'tags' => $this->generateTagRules($tagInspectors)
		];
	}

	/**
	* Generate and return rules based on a set of TemplateInspector
	*
	* @param  array $tagInspectors Array of [tagName => TemplateInspector]
	* @return array                Array of [tagName => [<rules>]]
	*/
	protected function generateTagRules(array $tagInspectors)
	{
		$rules = [];
		foreach ($tagInspectors as $tagName => $tagInspector)
		{
			$rules[$tagName] = $this->generateRuleset($tagInspector, $tagInspectors);
		}

		return $rules;
	}

	/**
	* Generate a set of rules to be applied at the root of a document
	*
	* @param  array $tagInspectors Array of [tagName => TemplateInspector]
	* @return array
	*/
	protected function generateRootRules(array $tagInspectors)
	{
		// Create a proxy for the parent markup so that we can determine which tags are allowed at
		// the root of the text (IOW, with no parent) or even disabled altogether
		$rootInspector = new TemplateInspector('<div><xsl:apply-templates/></div>');
		$rules         = $this->generateRuleset($rootInspector, $tagInspectors);

		// Remove root rules that wouldn't be applied anyway
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

	/**
	* Generate a set of rules for a single TemplateInspector instance
	*
	* @param  TemplateInspector $srcInspector  Source of the rules
	* @param  array             $trgInspectors Array of [tagName => TemplateInspector]
	* @return array
	*/
	protected function generateRuleset(TemplateInspector $srcInspector, array $trgInspectors)
	{
		$rules = [];
		foreach ($this->collection as $rulesGenerator)
		{
			if ($rulesGenerator instanceof BooleanRulesGenerator)
			{
				foreach ($rulesGenerator->generateBooleanRules($srcInspector) as $ruleName => $bool)
				{
					$rules[$ruleName] = $bool;
				}
			}

			if ($rulesGenerator instanceof TargetedRulesGenerator)
			{
				foreach ($trgInspectors as $tagName => $trgInspector)
				{
					$targetedRules = $rulesGenerator->generateTargetedRules($srcInspector, $trgInspector);
					foreach ($targetedRules as $ruleName)
					{
						$rules[$ruleName][] = $tagName;
					}
				}
			}
		}

		return $rules;
	}

	/**
	* Inspect given list of tags
	*
	* @param  TagCollection $tags Tags collection
	* @return array               Array of [tagName => TemplateInspector]
	*/
	protected function getTagInspectors(TagCollection $tags)
	{
		$tagInspectors = [];
		foreach ($tags as $tagName => $tag)
		{
			// Use the tag's template if applicable or XSLT's implicit default otherwise
			$template = $tag->template ?? '<xsl:apply-templates/>';
			$tagInspectors[$tagName] = new TemplateInspector($template);
		}

		return $tagInspectors;
	}
}