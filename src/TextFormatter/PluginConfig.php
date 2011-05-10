<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter;

abstract class PluginConfig
{
	/**
	* @var ConfigBuilder
	*/
	protected $cb;

	/**
	* @var integer Maximum amount of matches to process - used by the parser when running the global
	*              regexp
	*/
	public $regexpLimit = 1000;

	/**
	* @var string  What to do if the number of matches exceeds the limit. Values can be: "ignore"
	*              (ignore matches past limit), "warn" (same as "ignore" but also log a warning) and
	*              "abort" (abort parsing)
	*/
	public $regexpLimitAction = 'ignore';

	/**
	* @param ConfigBuilder $cb
	* @param array         $overrideProps Properties of the plugin will be overwritten with those
	*/
	public function __construct(ConfigBuilder $cb, array $overrideProps = array())
	{
		foreach ($overrideProps as $k => $v)
		{
			$this->$k = $v;
		}

		$this->cb = $cb;
		$this->setUp();
	}

	/**
	* Executed by constructor
	*/
	protected function setUp() {}

	/**
	* @return array|bool This plugin's config, or FALSE to disable this plugin
	*/
	abstract public function getConfig();

	/**
	* @return string|boolean JS parser, or false if unsupported
	*/
	public function getJSParser()
	{
		return false;
	}

	/**
	* @return array List of path to properties whose name must be preserved when generating the JS
	*               config
	*/
	public function getPreservedJSProps()
	{
		return array();
	}
}