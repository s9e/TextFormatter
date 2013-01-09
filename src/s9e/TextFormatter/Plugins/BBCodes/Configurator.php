<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes;

use ArrayAccess;
use Countable;
use DOMDocument;
use InvalidArgumentException;
use Iterator;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Helpers\RegexpParser;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;
use s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCode;
use s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCodeCollection;
use s9e\TextFormatter\Plugins\BBCodes\Configurator\Repository;
use s9e\TextFormatter\Plugins\BBCodes\Configurator\RepositoryCollection;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Configurator extends ConfiguratorBase implements ArrayAccess, Countable, Iterator
{
	use CollectionProxy;

	/**
	* @var BBCodeCollection BBCode collection
	*/
	public $collection;

	/**
	* {@inheritdoc}
	*/
	protected $quickMatch = '[';

	/**
	* @var RepositoryCollection BBCode repositories
	*/
	public $repositories;

	/**
	* Plugin setup
	*
	* @return void
	*/
	protected function setUp()
	{
		$this->collection = new BBCodeCollection;

		$this->repositories = new RepositoryCollection;
		$this->repositories->add('default', __DIR__ . '/Configurator/repository.xml');
	}

	/**
	* Add a BBCode using their human-readable representation
	*
	* @see s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCodeMonkey
	*
	* @param  string $usage    BBCode's usage
	* @param  string $template BBCode's template
	* @return BBCode           Newly-created BBCode
	*/
	public function addCustom($usage, $template)
	{
		// Create a temporary repository for this BBCode
		$dom = new DOMDocument;
		$dom->loadXML(
			'<?xml version="1.0" encoding="utf-8" ?>
			<repository>
				<bbcode name="CUSTOM">
					<usage>' . htmlspecialchars($usage) . '</usage>
					<template>' . htmlspecialchars($template) . '</template>
				</bbcode>
			</repository>'
		);
		$repository = new Repository($dom);

		return $this->addFromRepository('CUSTOM', $repository);
	}

	/**
	* Add a BBCode from a repository
	*
	* @param  string $name       Name of the entry in the repository
	* @param  mixed  $repository Name of the repository to use as source, or instance of Repository
	* @param  array  $vars       Variables that will replace default values in the tag definition
	* @return BBCode             Newly-created BBCode
	*/
	public function addFromRepository($name, $repository = 'default', array $vars = array())
	{
		// Load the Repository if necessary
		if (!($repository instanceof Repository))
		{
			if (!$this->repositories->exists($repository))
			{
				throw new InvalidArgumentException("Repository '" . $repository . "' does not exist");
			}

			$repository = $this->repositories->get($repository);
		}

		// Get the BBCode/tag config from the repository
		$config     = $repository->get($name, $vars);
		$bbcodeName = $config['name'];
		$bbcode     = $config['bbcode'];
		$tag        = $config['tag'];

		// If the BBCode doesn't specify a tag name, it's the same as the BBCode
		if (!isset($bbcode->tagName))
		{
			$bbcode->tagName = $bbcodeName;
		}

		if ($this->collection->exists($bbcodeName))
		{
			throw new RuntimeException("BBCode '" . $bbcodeName . "' already exists");
		}

		if ($this->configurator->tags->exists($bbcode->tagName))
		{
			throw new RuntimeException("Tag '" . $bbcode->tagName . "' already exists");
		}

		// Add our BBCode then its tag
		$this->collection->add($bbcodeName, $bbcode);
		$this->configurator->tags->add($bbcode->tagName, $tag);

		return $bbcode;
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

		// Build the regexp that matches all the BBCode names
		$regexp = RegexpBuilder::fromList(
			array_keys(iterator_to_array($this->collection)),
			array('delim' => '#')
		);

		// Remove the non-capturing subpattern since we place the regexp inside a capturing pattern.
		// For that, we need to reparse the regexp
		$def    = RegexpParser::parse('#' . $regexp . '#');
		$tokens = $def['tokens'];
		if (isset($tokens[0]['endToken']) && $tokens[0]['pos'] === 0)
		{
			// Here, we test that the whole regexp is covered by one subpattern, e.g.
			// (?:AA(?:XXX|YYY)) not (?:AA|BB)XXX or (?:AA|BB)(?:XXX|YYY)
			$endToken = $tokens[0]['endToken'];
			$endPos   = $tokens[$endToken]['pos'] + $tokens[$endToken]['len'];

			if ($endPos === strlen($regexp))
			{
				$regexp = substr($regexp, 3, -1);
			}
		}

		// Create the BBCodes config, with its JavaScript variant
		$bbcodesConfig = new Variant($this->collection->asConfig());

		// Create the JavaScript config. Ensure that BBCode names are preserved
		$jsConfig = new Dictionary;
		foreach ($bbcodesConfig->get() as $bbcodeName => $bbcodeConfig)
		{
			if (isset($bbcodeConfig['predefinedAttributes']))
			{
				// Ensure that attribute names are preserved
				$bbcodeConfig['predefinedAttributes']
					= new Dictionary($bbcodeConfig['predefinedAttributes']);
			}

			$jsConfig[$bbcodeName] = $bbcodeConfig;
		}

		// Add the JavaScript config as a variant
		$bbcodesConfig->set('JS', $jsConfig);

		return array(
			'bbcodes'    => $bbcodesConfig,
			'quickMatch' => $this->quickMatch,
			'regexp'     => '#\\[/?(' . $regexp . ')(?=[\\] =:/])#iS'
		);
	}
}