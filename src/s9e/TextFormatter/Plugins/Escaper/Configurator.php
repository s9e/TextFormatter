<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Escaper;

use DOMDocument;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Configurator extends ConfiguratorBase
{
	/**
	* {@inheritdoc}
	*/
	protected $quickMatch = '\\';

	/**
	* @var string Regexp that matches one backslash followed by the escape character
	*/
	protected $regexp;

	/**
	* @var string Name of the tag used by this plugin
	*/
	protected $tagName = 'ESC';

	/**
	* Set whether any Unicode character should be escapable, or limit to some ASCII symbols
	*
	* @param  bool $bool Whether any Unicode character should be escapable
	* @return void
	*/
	public function escapeAll($bool = true)
	{
		$this->regexp = ($bool) ? '/\\\\./su' : '/\\\\[-!#()*+.:@[\\\\\\]^_`{}]/';
	}

	/**
	* {@inheritdoc}
	*/
	protected function setUp()
	{
		// Set the default regexp
		$this->escapeAll(false);

		// Create the tag
		$tag = $this->configurator->tags->add($this->tagName);
		$tag->rules->denyAll();
		$tag->defaultTemplate = '<xsl:value-of select="substring(.,2)"/>';
	}
}