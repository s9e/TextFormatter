<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\TextFormatter\PluginConfig;

include_once __DIR__ . '/../../../src/TextFormatter/PluginConfig.php';

class WhitespaceConfig extends PluginConfig
{
	public function setUp()
	{
		$this->cb->BBCodes->addBBCode('B');
		$this->cb->BBCodes->addBBCode('MARK', $this->options);

		$this->cb->setTagTemplate('MARK', '[mark]<xsl:apply-templates/>[/mark]');
		$this->cb->setTagTemplate('B', '[b]<xsl:apply-templates/>[/b]');
	}

	public function getConfig()
	{
		return array(
			'regexp' => '#(?: tagws |tag)#',
			'parserClassName' => __NAMESPACE__ . '\\WhitespaceParser',
			'parserFilepath'  => __DIR__ . '/WhitespaceParser.php'
		);
	}
}