<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Emoji;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\RegExp;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
class Configurator extends ConfiguratorBase
{
	protected $attrName = 'seq';
	protected $forceImageSize = \true;
	protected $imageSet = 'twemoji';
	protected $imageSize = 16;
	protected $imageType = 'png';
	protected $tagName = 'EMOJI';
	protected function setUp()
	{
		if (isset($this->configurator->tags[$this->tagName]))
			return;
		$tag = $this->configurator->tags->add($this->tagName);
		$tag->attributes->add($this->attrName)->filterChain->append(
			$this->configurator->attributeFilters['#identifier']
		);
		$this->resetTemplate();
	}
	public function forceImageSize()
	{
		$this->forceImageSize = \true;
		$this->resetTemplate();
	}
	public function omitImageSize()
	{
		$this->forceImageSize = \false;
		$this->resetTemplate();
	}
	public function setImageSize($size)
	{
		$this->imageSize = (int) $size;
		$this->resetTemplate();
	}
	public function useEmojiOne()
	{
		$this->imageSet = 'emojione';
		$this->resetTemplate();
	}
	public function usePNG()
	{
		$this->imageType = 'png';
		$this->resetTemplate();
	}
	public function useSVG()
	{
		$this->imageType = 'svg';
		$this->resetTemplate();
	}
	public function useTwemoji()
	{
		$this->imageSet = 'twemoji';
		$this->resetTemplate();
	}
	public function asConfig()
	{
		$phpRegexp = '(';
		$jsRegexp  = '';
		$phpRegexp .= '(?=[#0-9:\\xC2\\xE2\\xE3\\xF0])';
		$phpRegexp .= '(?>';
		$jsRegexp  .= '(?:';
		$phpRegexp .= ':[-+_a-z0-9]+(?=:)';
		$jsRegexp  .= ':[-+_a-z0-9]+(?=:)';
		$phpRegexp .= '|(?>';
		$jsRegexp  .= '|(?:';
		$phpRegexp .= '[#0-9](?>\\xEF\\xB8\\x8F)?\\xE2\\x83\\xA3';
		$jsRegexp  .= '[#0-9]\\uFE0F?\\u20E3';
		$phpRegexp .= '|\\xC2[\\xA9\\xAE]';
		$jsRegexp  .= '|[\\u00A9\\u00AE';
		$phpRegexp .= '|\\xE2(?>\\x80\\xBC|[\\x81-\\xAD].)';
		$jsRegexp  .= '\\u203C\\u2049\\u2122-\\u2B55';
		$phpRegexp .= '|\\xE3(?>\\x80[\\xB0\\xBD]|\\x8A[\\x97\\x99])';
		$jsRegexp  .= '\\u3030\\u303D\\u3297\\u3299]';
		$phpRegexp .= '|\\xF0\\x9F(?>';
		$jsRegexp  .= '|\\uD83C(?:';
		$phpRegexp .= '[\\x80-\\x86].';
		$jsRegexp  .= '[\\uDC04-\\uDD9A]';
		$phpRegexp .= '|\\x87.\\xF0\\x9F\\x87.';
		$jsRegexp  .= '|[\\uDDE6-\\uDDFF]\\uD83C[\\uDDE6-\\uDDFF]';
		$phpRegexp .= '|[\\x88-\\x9B].';
		$jsRegexp  .= '|[\\uDE01-\\uDFFF])|\\uD83D[\\uDC00-\\uDEC5]';
		$phpRegexp .= ')';
		$phpRegexp .= ')(?>\\xEF\\xB8\\x8F)?';
		$jsRegexp  .= ')\uFE0F?';
		$phpRegexp .= ')';
		$jsRegexp  .= ')';
		$phpRegexp .= ')S';
		$regexp = new Variant($phpRegexp);
		$regexp->set('JS', new RegExp($jsRegexp, 'g'));
		return array(
			'attrName' => $this->attrName,
			'regexp'   => $regexp,
			'tagName'  => $this->tagName
		);
	}
	protected function getEmojiOneTemplate()
	{
		$template = '<img alt="{.}" class="emoji"';
		if ($this->forceImageSize)
			$template .= ' width="' . $this->imageSize . '" height="' . $this->imageSize . '"';
		$template .= '>
			<xsl:attribute name="src">
				<xsl:text>//cdn.jsdelivr.net/emojione/assets/' . $this->imageType . '/</xsl:text>
				<xsl:if test="contains(@seq, \'-20e3\') or @seq = \'a9\' or @seq = \'ae\'">00</xsl:if>
				<xsl:value-of select="translate(@seq, \'abcdef\', \'ABCDEF\')"/>
				<xsl:text>.' . $this->imageType . '</xsl:text>
			</xsl:attribute>
		</img>';
		return $template;
	}
	protected function getTargetSize(array $sizes)
	{
		$k = 0;
		foreach ($sizes as $k => $size)
			if ($size >= $this->imageSize)
				break;
		return $sizes[$k];
	}
	protected function getTemplate()
	{
		return ($this->imageSet === 'emojione') ? $this->getEmojiOneTemplate() :  $this->getTwemojiTemplate();
	}
	protected function getTwemojiTemplate()
	{
		$template = '<img alt="{.}" class="emoji" draggable="false"';
		if ($this->forceImageSize)
			$template .= ' width="' . $this->imageSize . '" height="' . $this->imageSize . '"';
		$template .= ' src="//twemoji.maxcdn.com/';
		if ($this->imageType === 'svg')
			$template .= 'svg';
		else
		{
			$size = $this->getTargetSize(array(16, 36, 72));
			$template .= $size . 'x' . $size;
		}
		$template .= '/{@seq}.' . $this->imageType . '"/>';
		return $template;
	}
	protected function resetTemplate()
	{
		$this->getTag()->template = $this->getTemplate();
	}
}