<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Regexp;
class HostnameList extends NormalizedList
{
	public function asConfig()
	{
		if (empty($this->items))
			return \null;
		return new Regexp($this->getRegexp());
	}
	public function getRegexp()
	{
		$hosts = array();
		foreach ($this->items as $host)
			$hosts[] = $this->normalizeHostmask($host);
		$regexp = RegexpBuilder::fromList(
			$hosts,
			array(
				'specialChars' => array(
					'*' => '.*',
					'^' => '^',
					'$' => '$'
				)
			)
		);
		return '/' . $regexp . '/DSis';
	}
	protected function normalizeHostmask($host)
	{
		if (\preg_match('#[\\x80-\xff]#', $host) && \function_exists('idn_to_ascii'))
		{
			$variant = (\defined('INTL_IDNA_VARIANT_UTS46')) ? \INTL_IDNA_VARIANT_UTS46 : 0;
			$host = @\idn_to_ascii($host, 0, $variant);
		}
		if (\substr($host, 0, 1) === '*')
			$host = \ltrim($host, '*');
		else
			$host = '^' . $host;
		if (\substr($host, -1) === '*')
			$host = \rtrim($host, '*');
		else
			$host .= '$';
		return $host;
	}
}