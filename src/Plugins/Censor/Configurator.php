<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Censor;

use ArrayAccess;
use Countable;
use Iterator;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
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

		// Create attribute
		$tag->attributes->add($this->attrName);

		// Ensure that censored content can't ever be used by other tags
		$tag->rules->denyAll();

		// Create a template for censored words using the default replacement
		$tag->templates[''] = htmlspecialchars($this->defaultReplacement);

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

		$config = array(
			'attrName' => $this->attrName,
			'tagName'  => $this->tagName
		);

		$words = array();
		$replacementWords = array();

		foreach ($this->collection as $word => $replacement)
		{
			$words[] = $word;

			if (isset($replacement) && $replacement !== $this->defaultReplacement)
			{
				$replacementWords[$replacement][] = $word;
			}
		}

		$regexpOptions = array('specialChars' => array('*' => '\\pL*', '?' => '.'));
		$regexp = RegexpBuilder::fromList($words, $regexpOptions);
		$config['regexp'] = '/(?<!\\pL)' . $regexp . '(?!\\pL)/iu';

		foreach ($replacementWords as $replacement => $words)
		{
			$regexp = '/^' . RegexpBuilder::fromList($words, $regexpOptions) . '$/Diu';
			$config['replacements'][$regexp] = $replacement;
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