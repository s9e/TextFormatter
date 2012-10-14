<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use InvalidArgumentException;

class BBCodesConfig extends PluginConfig
{
	use CollectionAccessor;

	/**
	* @var BBCodeCollection BBCode collection
	*/
	protected $items;

	/**
	* @var RepositoryCollection BBCode repositories
	*/
	protected $repositories;

	/**
	* Plugin setup
	*
	* @return void
	*/
	protected function setUp()
	{
		$this->items = new BBCodeCollection;

		$this->repositories = new RepositoryCollection;
		$this->repositories->add('default', __DIR__ . '/repository.xml');
	}

	/**
	* Add a BBCode from a repository
	*
	* @param  string $bbcodeName Name of the BBCode to add
	* @param  string $repository Name of the repository to use as source
	* @param  array  $vars       Variables that will replace default values in the tag definition
	* @return BBCode             Newly-created BBCode
	*/
	public function addFromRepository($bbcodeName, $repository = 'default', array $vars = array())
	{
		if (!$this->repositories->exists($repository))
		{
			throw new InvalidArgumentException("Repository '" . $repository . "' does not exist");
		}

		$bbcodeName = BBCode::normalizeName($bbcodeName);

		$dom = $this->repositories->get($repository);
	}







	/**
	* 
	*/
	public function getConfig()
	{
		if (empty($this->bbcodes))
		{
			return false;
		}

		/**
		* Build the regexp that matches all the BBCode names
		*/
		$regexp = $this->cb->getRegexpBuilder()->fromList(
			array_keys($this->bbcodes)
		);

		// Remove the non-capturing subpattern since we place the regexp inside a capturing pattern
		$regexp = preg_replace('#^\\(\\?:(.*)\\)$#D', '$1', $regexp);

		return array(
			'bbcodes' => $this->bbcodes,
			'regexp'  => '#\\[/?(' . $regexp . ')(?=[\\] =:/])#iS'
		);
	}

	/**
	* Create a new BBCode and its corresponding tag
	*
	* Will automatically create a tag of the same name, unless a different name is specified in
	* $config['tagName']. Attributes to be created can be passed via using "attributes" as key. The
	* same applies for "rules" and "template" or "xsl".
	*
	* @param string $bbcodeName
	* @param array  $config
	*/
	public function addBBCode($bbcodeName, array $config = array())
	{
		$bbcodeName = $this->normalizeBBCodeName($bbcodeName, false);

		if (isset($this->bbcodes[$bbcodeName]))
		{
			throw new InvalidArgumentException("BBCode '" . $bbcodeName . "' already exists");
		}

		/**
		* Separate tag options such as "trimBefore" from BBCodes-specific options such as
		* "defaultAttr"
		*/
		$bbcodeSpecificConfig = array(
			'autoClose'    => 1,
			'contentAttr'  => 1,
			'contentAttrs' => 1,
			'defaultAttr'  => 1,
			'tagName'      => 1
		);

		$bbcodeConfig = array_intersect_key($config, $bbcodeSpecificConfig);
		$tagConfig    = array_diff_key($config, $bbcodeSpecificConfig);
		$tagName      = (isset($bbcodeConfig['tagName'])) ? $bbcodeConfig['tagName'] : $bbcodeName;

		$this->cb->addTag($tagName, $tagConfig);
		$this->addBBCodeAlias($bbcodeName, $tagName, $bbcodeConfig);
	}

	/**
	* Create a new BBCode that maps to an existing tag
	*
	* @param string $bbcodeName
	* @param string $tagName
	* @param array  $bbcodeConfig
	*/
	public function addBBCodeAlias($bbcodeName, $tagName, array $bbcodeConfig = array())
	{
		$bbcodeName = $this->normalizeBBCodeName($bbcodeName, false);

		if (isset($this->bbcodes[$bbcodeName]))
		{
			throw new InvalidArgumentException("BBCode '" . $bbcodeName . "' already exists");
		}

		/**
		* This line of code has two purposes: first, it ensure that the tag name passed as second
		* parameter is not overwritten by the tagName element that may exist in $bbcodeConfig.
		*
		* Additionally, it ensures that tagName appears first in the array, so that it is available
		* when other options are set.
		*/
		$bbcodeConfig = array('tagName' => $tagName) + $bbcodeConfig;

		$this->bbcodes[$bbcodeName] = array();
		$this->setBBCodeOptions($bbcodeName, $bbcodeConfig);
	}

	//==========================================================================
	// JS Parser stuff
	//==========================================================================

	public function getJSConfig()
	{
		$config = $this->getConfig();

		$config['hasAutoCloseHint']    = false;
		$config['hasContentAttrsHint'] = false;
		$config['hasDefaultAttrHint']  = false;

		foreach ($this->bbcodes as $bbcodeConfig)
		{
			if (!empty($bbcodeConfig['autoClose']))
			{
				$config['hasAutoCloseHint'] = true;
			}

			if (!empty($bbcodeConfig['contentAttrs']))
			{
				$config['hasContentAttrsHint'] = true;
			}

			if (!empty($bbcodeConfig['defaultAttr']))
			{
				$config['hasDefaultAttrHint'] = true;
			}
		}

		return $config;
	}

	public function getJSConfigMeta()
	{
		return array(
			'preserveKeys' => array(
				array('bbcodes', true)
			)
		);
	}

	public function getJSParser()
	{
		return file_get_contents(__DIR__ . '/Parser.js');
	}
}