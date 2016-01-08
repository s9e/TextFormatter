<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript\Minifiers;
use RuntimeException;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;
class HostedMinifier extends Minifier
{
	public $gzLevel = 5;
	public $timeout = 20;
	public $url = 'http://s9e-textformatter.rhcloud.com/minifier/';
	public function minify($src)
	{
		$url     = $this->url;
		$headers = array('Connection: close', 'Content-Type: application/octet-stream');
		$content = $src;
		if (\extension_loaded('zlib'))
		{
			$url       = 'compress.zlib://' . $url;
			$headers[] = 'Content-Encoding: gzip';
			$headers[] = 'Accept-Encoding: gzip';
			$content   = \gzencode($content, $this->gzLevel);
		}
		$headers[] = 'Content-Length: ' . \strlen($content);
		$content = \file_get_contents($url, \false, $this->getContext($headers, $content));
		if (empty($http_response_header[0]) || \strpos($http_response_header[0], '200') === \false)
			throw new RuntimeException($content);
		return $content;
	}
	protected function getContext(array $headers, $content)
	{
		return \stream_context_create(array(
			'http' => array(
				'method'        => 'POST',
				'header'        => \implode("\r\n", $headers),
				'content'       => $content,
				'timeout'       => $this->timeout,
				'ignore_errors' => \true
			)
		));
	}
}