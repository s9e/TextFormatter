<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Renderers;

use XSLTCache;

/**
* This renderer uses the xslcache PECL extension
*
* @link http://pecl.php.net/package/xslcache
* @link http://michaelsanford.com/compiling-xslcache-0-7-1-for-php-5-4/
*/
class XSLCache extends XSLT
{
	/**
	* @var string Path to the stylesheet used by this renderer
	*/
	protected $filepath;

	/**
	* @var XSLTCache The lazy-loaded XSLCache instance used by this renderer
	*/
	protected $proc;

	/**
	* Constructor
	*
	* @param  string $filepath Path to the stylesheet used by this renderer
	* @return void
	*/
	public function __construct($filepath)
	{
		$this->filepath = $filepath;
		parent::__construct(file_get_contents($this->filepath));
	}

	/**
	* Return the path to the stylesheet used by this renderer
	*
	* @return string
	*/
	public function getFilepath()
	{
		return $this->filepath;
	}

	/**
	* Cache the XSLTCache instance used by this renderer if it does not exist
	*
	* @return void
	*/
	protected function load()
	{
		if (!isset($this->proc))
		{
			$this->proc = new XSLTCache;
			$this->proc->importStylesheet($this->filepath);
		}
	}
}