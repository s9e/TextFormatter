<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Autoaudio;

use s9e\TextFormatter\Plugins\AbstractStaticUrlReplacer\AbstractConfigurator;

class Configurator extends AbstractConfigurator
{
	public    array  $fileExtensions = ['aac', 'flac', 'm4a', 'mp3', 'wav', 'wave'];
	protected        $attrName       = 'src';
	protected        $tagName        = 'AUDIO';

	protected function getTemplate(): string
	{
		return '<div class="autoaudio"><p class="autoaudio-title"><a href="{@' . $this->attrName . '}"><xsl:value-of select="@filename"/></a>:</p><p class="autoaudio-player"><audio controls="" src="{@' . $this->attrName . '}"/></p></div>';
	}

	protected function setUp(): void
	{
		parent::setUp();

		$tag = $this->configurator->tags[$this->tagName];
		$tag->attributes->add('filename')->filterChain->append('urldecode');
		$tag->attributePreprocessors->add($this->attrName, '/\\/(?\'filename\'[^\\/]+)$/');
	}
}
