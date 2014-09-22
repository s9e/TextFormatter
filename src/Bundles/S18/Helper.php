<?php
/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/

namespace s9e\TextFormatter\Bundles\S18;

use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Parser\BuiltInFilters;
use s9e\TextFormatter\Parser\Logger;
use s9e\TextFormatter\Renderer as AbstractRenderer;

abstract class Helper
{
	/*
	* Format timestamps inside of an XML representation
	*
	* NOTE: has no effect if SMF is not loaded
	*
	* @param  string $xml XML representation of a parsed text
	* @return string      XML representation, with human-readable dates
	*/
	public static function applyTimeformat($xml)
	{
		if (\substr($xml, 0, 2) === '<r')
		{
			$xml = \preg_replace_callback(
				'/(<(?:QUOT|TIM)E [^>]*?\\b(?:dat|tim)e=")(\\d+)(?=")/',
				function ($m)
				{
					$datetime = (\function_exists('timeformat'))
					          ? \timeformat($m[2])
					          : \strftime('%B %d, %Y, %I:%M:%S %p', $m[2]);

					return $m[1] . \htmlspecialchars($datetime, \ENT_COMPAT);
				},
				$xml
			);
		}

		return $xml;
	}

	/*
	* Configure the given parser to current SMF environment
	*
	* NOTE: has no effect if SMF is not loaded
	*
	* @param  Parser $parser
	* @return void
	*/
	public static function configureParser(Parser $parser)
	{
		if (!isset($GLOBALS['modSettings']))
			return;

		$modSettings = $GLOBALS['modSettings'];

		$plugins = array(
			'Autoemail'    => 'autoLinkUrls',
			'Autolink'     => 'autoLinkUrls',
			'BBCodes'      => 'enableBBC',
			'HTMLElements' => 'enablePostHTML'
		);
		foreach ($plugins as $pluginName => $settingName)
			if (empty($modSettings[$settingName]))
				$parser->disablePlugin($pluginName);

		if (!empty($modSettings['disabledBBC']))
			foreach (\explode(',', \strtoupper($modSettings['disabledBBC'])) as $bbcodeName)
				$parser->disableTag($bbcodeName);

		if (empty($modSettings['enableEmbeddedFlash']))
			$parser->disableTag('FLASH');
	}

	/*
	* Configure the given renderer to current SMF environment
	*
	* NOTE: has no effect if SMF is not loaded
	*
	* @param  Renderer $renderer
	* @return void
	*/
	public static function configureRenderer(AbstractRenderer $renderer)
	{
		$params = array();

		if (\function_exists('isBrowser'))
		{
			$params['IS_GECKO'] = \isBrowser('gecko');
			$params['IS_IE']    = \isBrowser('ie');
			$params['IS_OPERA'] = \isBrowser('opera');
		}

		foreach (array('code', 'code_select', 'quote', 'quote_from', 'search_on') as $key)
			if (isset($GLOBALS['txt'][$key]))
				$params['L_' . \strtoupper($key)] = $GLOBALS['txt'][$key];

		if (isset($GLOBALS['scripturl']))
			$params['SCRIPT_URL'] = $GLOBALS['scripturl'];

		if (isset($GLOBALS['modSettings'], $GLOBALS['user_info']['smiley_set']))
			$params['SMILEYS_PATH'] = $GLOBALS['modSettings']['smileys_url'] . '/' . $GLOBALS['user_info']['smiley_set'] . '/';

		if ($params)
			$renderer->setParameters($params);
	}

	/*
	* Prepend the http:// scheme in front of a URL if it's not already present and it doesn't start
	* with a #, and validate as a URL if it doesn't start with #
	*
	* @param  string $url       Original URL
	* @param  array  $urlConfig Config used by the URL filter
	* @param  Logger $logger    Default logger
	* @return mixed             Original value if valid, FALSE otherwise
	*/
	public static function filterIurl($url, array $urlConfig, Logger $logger)
	{
		// Anchor links are returned as-is
		if (\substr($url, 0, 1) === '#')
			return $url;

		// Prepend http:// if applicable
		$url = self::prependHttp($url);

		// Validate as a URL
		return BuiltInFilters::filterUrl($url, $urlConfig, $logger);
	}

	/*
	* Prepend the ftp:// scheme in front of a URL if it's not already present
	*
	* @param  string $url Original URL
	* @return string      URL that starts with ftp:// or ftps://
	*/
	public static function prependFtp($url)
	{
		if (\substr($url, 0, 6) !== 'ftp://'
		 && \substr($url, 0, 7) !== 'ftps://')
			 return 'ftp://' . $url;

		return $url;
	}

	/*
	* Prepend the http:// scheme in front of a URL if it's not already present
	*
	* @param  string $url Original URL
	* @return string      URL that starts with http:// or https://
	*/
	public static function prependHttp($url)
	{
		if (\substr($url, 0, 7) !== 'http://'
		 && \substr($url, 0, 8) !== 'https://')
			 return 'http://' . $url;

		return $url;
	}
}