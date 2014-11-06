<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Rendering;
use s9e\TextFormatter\Renderers\XSLCache as XSLCacheRenderer;

class XSLCache extends XSLT
{
	protected $cacheDir;

	public function __construct($cacheDir)
	{
		parent::__construct();
		$this->cacheDir = \realpath($cacheDir);

		if ($this->cacheDir === \false)
			throw new InvalidArgumentException("Path '" . $cacheDir . "' is invalid");
	}

	public function getRenderer(Rendering $rendering)
	{
		$xsl = $this->getXSL($rendering);
		$md5 = \md5($xsl);

		$filepath = $this->cacheDir . '/xslcache.' . $md5 . '.xsl';
		\file_put_contents($filepath, $xsl);

		return new XSLCacheRenderer($filepath);
	}
}