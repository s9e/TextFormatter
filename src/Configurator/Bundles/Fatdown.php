<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Bundles;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Bundle;

class Fatdown extends Bundle
{
	/**
	* {@inheritdoc}
	*/
	public function configure(Configurator $configurator)
	{
		$configurator->urlConfig->allowScheme('ftp');

		$configurator->Litedown;
		$configurator->Autoemail;
		$configurator->Autolink;
		$configurator->Escaper;
		$configurator->FancyPants;
		$configurator->HTMLEntities;

		$htmlElements = [
			'a' => ['!href', 'title'],
			'abbr' => ['title'],
			'b',
			'br',
			'code',
			'del',
			'em',
			'hr',
			'i',
			'img' => ['alt', '!src', 'title'],
			's',
			'strong',
			'sub',
			'sup',
			'table',
			'tbody',
			'td' => ['colspan', 'rowspan'],
			'tfoot',
			'th' => ['colspan', 'rowspan', 'scope'],
			'thead',
			'tr',
			'u'
		];
		foreach ($htmlElements as $k => $v)
		{
			if (is_numeric($k))
			{
				$elName    = $v;
				$attrNames = [];
			}
			else
			{
				$elName    = $k;
				$attrNames = $v;
			}

			$configurator->HTMLElements->allowElement($elName);
			foreach ($attrNames as $attrName)
			{
				if ($attrName[0] === '!')
				{
					$attrName = substr($attrName, 1);
					$required = true;
				}
				else
				{
					$required = false;
				}

				$configurator->HTMLElements->allowAttribute($elName, $attrName)->required = $required;
			}
		}

		$configurator->plugins->load('MediaEmbed', ['createBBCodes' => false]);
		$sites = [
			'bandcamp',
			'dailymotion',
			'facebook',
			'grooveshark',
			'liveleak',
			'soundcloud',
			'spotify',
			'twitch',
			'vimeo',
			'vine',
			'youtube'
		];
		foreach ($sites as $site)
		{
			$configurator->MediaEmbed->add($site);
		}
	}
}