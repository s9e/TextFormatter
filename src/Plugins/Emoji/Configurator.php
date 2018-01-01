<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2018 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Emoji;

use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Configurator extends ConfiguratorBase
{
	/**
	* @var string Name of the attribute used by this plugin
	*/
	protected $attrName = 'seq';

	/**
	* @var array Associative array of alias => emoji
	*/
	protected $aliases = [];

	/**
	* @var string Name of the tag used by this plugin
	*/
	protected $tagName = 'EMOJI';

	/**
	* Plugin's setup
	*
	* Will create the tag used by this plugin
	*/
	protected function setUp()
	{
		if (isset($this->configurator->tags[$this->tagName]))
		{
			return;
		}

		$tag = $this->configurator->tags->add($this->tagName);
		$tag->attributes->add($this->attrName)->filterChain->append(
			$this->configurator->attributeFilters['#identifier']
		);
		$tag->template = '<img alt="{.}" class="emoji" draggable="false" src="//cdn.jsdelivr.net/emojione/assets/3.1/png/64/{@seq}.png"/>';
	}

	/**
	* Add an emoji alias
	*
	* @param  string $alias
	* @param  string $emoji
	* @return void
	*/
	public function addAlias($alias, $emoji)
	{
		$this->aliases[$alias] = $emoji;
	}

	/**
	* Remove an emoji alias
	*
	* @param  string $alias
	* @return void
	*/
	public function removeAlias($alias)
	{
		unset($this->aliases[$alias]);
	}

	/**
	* Get all emoji aliases
	*
	* @return array
	*/
	public function getAliases()
	{
		return $this->aliases;
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

		if (!empty($this->aliases))
		{
			$aliases = array_keys($this->aliases);
			$regexp  = '/' . RegexpBuilder::fromList($aliases) . '/';

			$config['aliases']       = $this->aliases;
			$config['aliasesRegexp'] = new Regexp($regexp, true);

			$quickMatch = ConfigHelper::generateQuickMatchFromList($aliases);
			if ($quickMatch !== false)
			{
				$config['aliasesQuickMatch'] = $quickMatch;
			}
		}

		return $config;
	}

	/**
	* {@inheritdoc}
	*/
	public function getJSHints()
	{
		$quickMatch = ConfigHelper::generateQuickMatchFromList(array_keys($this->aliases));

		return [
			'EMOJI_HAS_ALIASES'          => !empty($this->aliases),
			'EMOJI_HAS_ALIAS_QUICKMATCH' => ($quickMatch !== false)
		];
	}
}