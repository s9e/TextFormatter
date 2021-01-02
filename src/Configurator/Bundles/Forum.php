<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Bundles;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Bundle;

class Forum extends Bundle
{
	/**
	* {@inheritdoc}
	*/
	public function configure(Configurator $configurator)
	{
		$configurator->rootRules->enableAutoLineBreaks();

		$configurator->BBCodes->addFromRepository('B');
		$configurator->BBCodes->addFromRepository('CENTER');
		$configurator->BBCodes->addFromRepository('CODE');
		$configurator->BBCodes->addFromRepository('COLOR');
		$configurator->BBCodes->addFromRepository('EMAIL');
		$configurator->BBCodes->addFromRepository('FONT');
		$configurator->BBCodes->addFromRepository('I');
		$configurator->BBCodes->addFromRepository('IMG');
		$configurator->BBCodes->addFromRepository('LIST');
		$configurator->BBCodes->addFromRepository('*');
		$configurator->BBCodes->add('LI');
		$configurator->BBCodes->addFromRepository('OL');
		$configurator->BBCodes->addFromRepository('QUOTE', 'default', [
			'authorStr' => '<xsl:value-of select="@author"/> <xsl:value-of select="$L_WROTE"/>'
		]);
		$configurator->BBCodes->addFromRepository('S');
		$configurator->BBCodes->addFromRepository('SIZE');
		$configurator->BBCodes->addFromRepository('SPOILER', 'default', [
			'hideStr'    => '{L_HIDE}',
			'showStr'    => '{L_SHOW}',
			'spoilerStr' => '{L_SPOILER}',
		]);
		$configurator->BBCodes->addFromRepository('TABLE');
		$configurator->BBCodes->addFromRepository('TD');
		$configurator->BBCodes->addFromRepository('TH');
		$configurator->BBCodes->addFromRepository('TR');
		$configurator->BBCodes->addFromRepository('U');
		$configurator->BBCodes->addFromRepository('UL');
		$configurator->BBCodes->addFromRepository('URL');

		$configurator->rendering->parameters = [
			'L_WROTE'   => 'wrote:',
			'L_HIDE'    => 'Hide',
			'L_SHOW'    => 'Show',
			'L_SPOILER' => 'Spoiler'
		];

		$emoticons = [
			':)'  => '1F642',
			':-)' => '1F642',
			';)'  => '1F609',
			';-)' => '1F609',
			':D'  => '1F600',
			':-D' => '1F600',
			':('  => '2639',
			':-(' => '2639',
			':-*' => '1F618',
			':P'  => '1F61B',
			':-P' => '1F61B',
			':p'  => '1F61B',
			':-p' => '1F61B',
			';P'  => '1F61C',
			';-P' => '1F61C',
			';p'  => '1F61C',
			';-p' => '1F61C',
			':?'  => '1F615',
			':-?' => '1F615',
			':|'  => '1F610',
			':-|' => '1F610',
			':o'  => '1F62E',
			':lol:' => '1F602'
		];

		foreach ($emoticons as $code => $hex)
		{
			$configurator->Emoji->aliases[$code] = html_entity_decode('&#x' . $hex . ';');
		}

		$sites = ['bandcamp', 'dailymotion', 'facebook', 'indiegogo', 'instagram', 'kickstarter', 'liveleak', 'soundcloud', 'twitch', 'twitter', 'vimeo', 'vine', 'wshh', 'youtube'];
		foreach ($sites as $siteId)
		{
			$configurator->MediaEmbed->add($siteId);
			$configurator->BBCodes->add($siteId, ['contentAttributes' => ['id', 'url']]);
		}

		$configurator->Autoemail;
		$configurator->Autolink;
	}
}