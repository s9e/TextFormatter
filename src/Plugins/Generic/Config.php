<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use Exception;
use InvalidArgumentException,
	RuntimeException;
use s9e\TextFormatter\Generator;
use s9e\TextFormatter\JSParserGenerator;
use s9e\TextFormatter\Plugins\GeneratorBase;

/**
* NOTE: does not support duplicate named captures
*/
class GenericConfig extends GeneratorBase
{
	/**
	* @var array Associative array of regexps. The keys are the corresponding tag names
	*/
	protected $regexp = array();

	/**
	* Add a generic replacement
	*
	* @param  string $regexp
	* @param  string $template
	* @return string           The name of the tag
	*/
	public function addReplacement($regexp, $template)
	{
		$valid = false;

		try
		{
			$valid = @preg_match_all($regexp, '', $m);
		}
		catch (Exception $e)
		{
		}

		if ($valid === false)
		{
			throw new InvalidArgumentException('Invalid regexp');
		}

		/**
		* Tag options, will store attributes and template
		*/
		$tagOptions = array(
			'template' => $template
		);

		/**
		* Parse the regexp, and generate an attribute for every named capture
		*/
		$regexpInfo = $this->generator->getRegexpHelper()->parseRegexp($regexp);

		$attrs = array();

		foreach ($regexpInfo['tokens'] as $tok)
		{
			if ($tok['type'] === 'capturingSubpatternStart'
			 && isset($tok['name']))
			{
				$attrName = $tok['name'];

				if (isset($tagOptions['attrs'][$attrName]))
				{
					throw new RuntimeException('Duplicate named subpatterns are not allowed');
				}

				$lpos = $tok['pos'];
				$rpos = $regexpInfo['tokens'][$tok['endToken']]['pos']
				      + $regexpInfo['tokens'][$tok['endToken']]['len'];

				$attrRegexp = $regexpInfo['delimiter']
				            . '^' . substr($regexpInfo['regexp'], $lpos, $rpos - $lpos) . '$'
				            . $regexpInfo['delimiter']
				            . $regexpInfo['modifiers']
				            . 'D';

				$tagOptions['attrs'][$attrName] = array(
					'filter'   => '#regexp',
					'regexp'   => $attrRegexp,
					'required' => true
				);
			}
		}

		/**
		* Generate a tag name based on the regexp
		*/
		$tagName = 'G' . strtr(dechex(crc32($regexp)), 'abcdef', 'ABCDEF');

		/**
		* Create the tag
		*/
		$this->generator->addTag($tagName, $tagOptions);

		/**
		* Finally, record the replacement
		*/
		$this->regexp[$tagName] = $regexp;

		return $tagName;
	}

	public function getConfig()
	{
		if (empty($this->regexp))
		{
			return false;
		}

		return array('regexp' => $this->regexp);
	}

	//==========================================================================
	// JS Parser stuff
	//==========================================================================

	public function getJSConfig()
	{
		$config = $this->getConfig();

		if ($config)
		{
			foreach ($config['regexp'] as $tagName => $regexp)
			{
				$this->generator->getRegexpHelper()->pcreToJs($regexp, $config['regexpMap'][$tagName]);
			}
		}

		return $config;
	}

	public function getJSConfigMeta()
	{
		return array(
			'preserveKeys' => array(
				array('regexp', true),
				array('regexpMap', true),
				array('regexpMap', true, true)
			)
		);
	}

	public function getJSParser()
	{
		return file_get_contents(__DIR__ . '/GenericParser.js');
	}
}