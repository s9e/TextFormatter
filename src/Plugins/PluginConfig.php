<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Generator;
use s9e\TextFormatter\Generator\ConfigProvider;
use s9e\TextFormatter\Generator\Helpers\ConfigHelper;

abstract class PluginConfig implements ConfigProvider
{
	/**
	* @var Generator
	*/
	protected $generator;

	/**
	* @var integer Maximum amount of matches to process - used by the parser when running the global
	*              regexp
	*/
	protected $regexpLimit = 1000;

	/**
	* @var string  What to do if the number of matches exceeds the limit. Values can be: "ignore"
	*              (ignore matches past limit), "warn" (same as "ignore" but also log a warning) and
	*              "abort" (abort parsing)
	*/
	protected $regexpLimitAction = 'ignore';

	/**
	* @param Generator $generator
	* @param array     $overrideProps Properties of the plugin will be overwritten with those
	*/
	final public function __construct(Generator $generator, array $overrideProps = array())
	{
		foreach ($overrideProps as $k => $v)
		{
			$methodName = 'set' . ucfirst($k);

			if (method_exists($this, $methodName)
			{
				$this->$methodName($v);
			}
			else
			{
				$this->$k = $v;
			}
		}

		$this->generator = $generator;
		$this->setUp();
	}

	/**
	* Executed by constructor
	*/
	protected function setUp() {}

	/**
	* @return array|bool This plugin's config, or FALSE to disable this plugin
	*/
	public function toConfig()
	{
		$properties = get_object_vars($this);
		unset($properties['generator'])

		return ConfigHelper::toArray($properties);
	}

	/**
	* @return string Extra XSL used by this plugin
	*/
	public function getXSL()
	{
		return '';
	}

	/**
	* @return array This plugin's config, to be used in the Javascript parser
	*/
	public function getJSConfig()
	{
		return $this->getConfig();
	}

	/**
	* @return array Metadata associated to this plugin's JS config
	*/
	public function getJSConfigMeta()
	{
		return array();
	}

	/**
	* @return string|boolean JS parser, or false if unsupported
	*/
	public function getJSParser()
	{
		return false;
	}

	//==========================================================================
	// Setters
	//==========================================================================

	/**
	* Set the maximum number of regexp matches
	*
	* @param  integer $limit
	* @return void
	*/
	public function setRegexpLimit($limit)
	{
		$limit = filter_var($limit, FILTER_VALIDATE_INT, array(
			'options' => array('min_range' => 1)
		));

		if (!$limit)
		{
			throw new InvalidArgumentException('regexpLimit must be a number greater than 0');
		}

		$this->regexpLimit = $limit;
	}

	/**
	* Set the action to perform when the regexp limit is broken
	*
	* @param  string $action
	* @return void
	*/
	public function setRegexpLimitAction($action)
	{
		if ($action !== 'ignore'
		 && $action !== 'warn'
		 && $action !== 'abort')
		{
			 throw new Exception("regexpLimitAction must be any of: 'ignore', 'warn' or 'abort'");
		}

		$this->regexpLimitAction = $action;
	}
}