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

	public static function configureParser(Parser $parser)
	{
		if (!isset($GLOBALS['modSettings']))
			return;

		$modSettings = $GLOBALS['modSettings'];

		$plugins = [
			'Autoemail'    => 'autoLinkUrls',
			'Autolink'     => 'autoLinkUrls',
			'BBCodes'      => 'enableBBC',
			'HTMLElements' => 'enablePostHTML'
		];
		foreach ($plugins as $pluginName => $settingName)
			if (empty($modSettings[$settingName]))
				$parser->disablePlugin($pluginName);

		if (!empty($modSettings['disabledBBC']))
			foreach (\explode(',', \strtoupper($modSettings['disabledBBC'])) as $bbcodeName)
				$parser->disableTag($bbcodeName);

		if (empty($modSettings['enableEmbeddedFlash']))
			$parser->disableTag('FLASH');
	}

	public static function configureRenderer(AbstractRenderer $renderer)
	{
		$params = [];

		if (\function_exists('isBrowser'))
		{
			$params['IS_GECKO'] = \isBrowser('gecko');
			$params['IS_IE']    = \isBrowser('ie');
			$params['IS_OPERA'] = \isBrowser('opera');
		}

		foreach (['code', 'code_select', 'quote', 'quote_from', 'search_on'] as $key)
			if (isset($GLOBALS['txt'][$key]))
				$params['L_' . \strtoupper($key)] = $GLOBALS['txt'][$key];

		if (isset($GLOBALS['scripturl']))
			$params['SCRIPT_URL'] = $GLOBALS['scripturl'];

		if (isset($GLOBALS['modSettings'], $GLOBALS['user_info']['smiley_set']))
			$params['SMILEYS_PATH'] = $GLOBALS['modSettings']['smileys_url'] . '/' . $GLOBALS['user_info']['smiley_set'] . '/';

		if ($params)
			$renderer->setParameters($params);
	}

	public static function filterIurl($url, array $urlConfig, Logger $logger)
	{
		if (\substr($url, 0, 1) === '#')
			return $url;

		$url = self::prependHttp($url);

		return BuiltInFilters::filterUrl($url, $urlConfig, $logger);
	}

	public static function prependFtp($url)
	{
		if (\substr($url, 0, 6) !== 'ftp://'
		 && \substr($url, 0, 7) !== 'ftps://')
			 return 'ftp://' . $url;

		return $url;
	}

	public static function prependHttp($url)
	{
		if (\substr($url, 0, 7) !== 'http://'
		 && \substr($url, 0, 8) !== 'https://')
			 return 'http://' . $url;

		return $url;
	}
}