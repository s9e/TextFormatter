<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript\Minifiers;
use RuntimeException;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;
class RemoteCache extends Minifier
{
	public $url = 'http://s9e-textformatter.rhcloud.com/minifier/cache/';
	public function minify($src)
	{
		$url = $this->url . $this->getHash($src);
		if (\extension_loaded('zlib'))
			$url = 'compress.zlib://' . $url . '.gz';
		$contextOptions = array('http' => array('ignore_errors'  => \true));
		$content = \file_get_contents($url, \false, \stream_context_create($contextOptions));
		if (empty($http_response_header[0]) || \strpos($http_response_header[0], '200') === \false)
			throw new RuntimeException;
		return $content;
	}
	protected function getHash($src)
	{
		return \strtr(\base64_encode(\sha1($src, \true) . \md5($src, \true)), '+/', '-_');
	}
}