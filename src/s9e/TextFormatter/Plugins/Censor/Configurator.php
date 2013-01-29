<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Censor;

use ArrayAccess;
use Countable;
use Iterator;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;
use s9e\TextFormatter\Configurator\JavaScript\RegExp;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;
use s9e\TextFormatter\Configurator\Validators\AttributeName;
use s9e\TextFormatter\Configurator\Validators\TagName;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Configurator extends ConfiguratorBase implements ArrayAccess, Countable, Iterator
{
	use CollectionProxy;

	/**
	* @var string Name of attribute used for the replacement
	*/
	protected $attrName = 'with';

	/**
	* @var NormalizedCollection List of [word => replacement]
	*/
	protected $collection;

	/**
	* @var string Default string used to replace censored words
	*/
	protected $defaultReplacement = '****';

	/**
	* @var string Name of the tag used to mark censored words
	*/
	protected $tagName = 'CENSOR';

	/**
	* Plugin's setup
	*
	* Will initialize its collection and create the plugin's tag if it does not exist
	*/
	public function setUp()
	{
		$this->collection = new NormalizedCollection;

		if (isset($this->configurator->tags[$this->tagName]))
		{
			return;
		}

		// Create tag
		$tag = $this->configurator->tags->add($this->tagName);

		// Create attribute and make it optional
		$tag->attributes->add($this->attrName)->required = false	;

		// Ensure that censored content can't ever be used by other tags
		$tag->rules->denyAll();

		// Create a template for censored words using the default replacement
		$tag->defaultTemplate = htmlspecialchars($this->defaultReplacement);

		// Create a template for censored words with custom replacements
		$tag->templates['@' . $this->attrName]
			= '<xsl:value-of select="@' . htmlspecialchars($this->attrName) . '"/>';
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		if (!count($this->collection))
		{
			return false;
		}

		$config = [
			'attrName' => $this->attrName,
			'tagName'  => $this->tagName
		];

		$words = [];
		$replacementWords = [];

		foreach ($this->collection as $word => $replacement)
		{
			$words[] = $word;

			if (isset($replacement) && $replacement !== $this->defaultReplacement)
			{
				$replacementWords[$replacement][] = $word;
			}
		}

		/** @todo "?" should probably become ".?" so that "apple?" matches both "apple" and "apples" */
		$regexpOptions = [
			'caseInsensitive' => true,
			'specialChars'    => ['*' => '\\pL*', '?' => '.']
		];
		$regexp = RegexpBuilder::fromList($words, $regexpOptions);

		// Add the regexp to the config, along with a JavaScript variant
		$config['regexp'] = new Variant('/(?<!\\pL)' . $regexp . '(?!\\pL)/iu');

		// JavaScript regexps don't support Unicode properties, so instead of Unicode letters
		// we'll accept any non-whitespace, non-common punctuation
		$regexp = str_replace('\\pL', '[^\s!-\\/:-?]', $regexp);
		$config['regexp']->set('JS', new RegExp('(?:^|\W)' . $regexp . '(?!\w)', 'gi'));

		foreach ($replacementWords as $replacement => $words)
		{
			$regexp = '/^' . RegexpBuilder::fromList($words, $regexpOptions) . '$/Diu';

			// Create a regexp with a JavaScript variant for each group of words
			$variant = new Variant($regexp);

			$regexp = str_replace('\\pL', '[^\s!-\\/:-?]', $regexp);
			$variant->set('JS', RegexpConvertor::toJS($regexp));

			$config['replacements'][] = [$variant, $replacement];
		}

		return $config;
	}

	/**
	* Set the name of the attribute used by this plugin
	*
	* @param  string $attrName
	* @return void
	*/
	protected function setAttrName($attrName)
	{
		$this->attrName = AttributeName::normalize($attrName);
	}

	/**
	* Set the name of the tag used by this plugin
	*
	* @param  string $tagName
	* @return void
	*/
	protected function setTagName($tagName)
	{
		$this->tagName = TagName::normalize($tagName);
	}
}