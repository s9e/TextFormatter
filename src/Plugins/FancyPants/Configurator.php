<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\FancyPants;

use s9e\TextFormatter\Plugins\ConfiguratorBase;

/**
* This plugin combines some of the functionalities found in SmartyPants and Textile
*
* @link http://daringfireball.net/projects/smartypants/
* @link http://textile.thresholdstate.com/
*/
class Configurator extends ConfiguratorBase
{
	/**
	* @var string Name of attribute used to for the replacement
	*/
	protected $attrName = 'char';

	/**
	* @var string[] List of passes that have been explicitly disabled
	*/
	protected $disabledPasses = [];

	/**
	* @var string Name of the tag used to mark the text to replace
	*/
	protected $tagName = 'FP';

	/**
	* Plugin's setup
	*
	* Will initialize create the plugin's tag if it does not exist
	*/
	protected function setUp()
	{
		if (isset($this->configurator->tags[$this->tagName]))
		{
			return;
		}

		// Create tag
		$tag = $this->configurator->tags->add($this->tagName);

		// Create attribute
		$tag->attributes->add($this->attrName);

		// Create a template that replaces its content with the replacement char
		$tag->template
			= '<xsl:value-of select="@' . htmlspecialchars($this->attrName) . '"/>';
	}

	/**
	* Disable a given pass
	*
	* @param  string $passName
	* @return void
	*/
	public function disablePass($passName)
	{
		$this->disabledPasses[] = $passName;
	}

	/**
	* Enable a given pass
	*
	* @param  string $passName
	* @return void
	*/
	public function enablePass($passName)
	{
		foreach (array_keys($this->disabledPasses, $passName, true) as $k)
		{
			unset($this->disabledPasses[$k]);
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$config = [
			'attrName' => $this->attrName,
			'tagName'  => $this->tagName
		];
		foreach ($this->disabledPasses as $passName)
		{
			$config['disable' . $passName] = true;
		}

		return $config;
	}
}