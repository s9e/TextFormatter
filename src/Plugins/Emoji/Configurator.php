<?php

/**
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
	/**
	* @var string Name of the attribute used by this plugin
	*/
	protected $attrName = 'seq';

	/**
	* @var bool Whether to force the image size in the img tag
	*/
	protected $forceImageSize = true;

	/**
	* @var string Emoji set to use
	*/
	protected $imageSet = 'twemoji';

	/**
	* @var integer Target size for the emoji images
	*/
	protected $imageSize = 16;

	/**
	* @var string Preferred image type
	*/
	protected $imageType = 'png';

	/**
	* @var string Name of the tag used by this plugin
	*/
	protected $tagName = 'EMOJI';

	/**
	* Plugin's setup
	*
	* Will create the tag used by this plugin
	*/
	protected function setUp()
	{
		if (isset($this->configurator->tags[$this->tagName]))
		{
			return;
		}

		$tag = $this->configurator->tags->add($this->tagName);
		$tag->attributes->add($this->attrName)->filterChain->append(
			$this->configurator->attributeFilters['#identifier']
		);
		$this->resetTemplate();
	}

	/**
	* Force the size of the image to be set in the img element
	*
	* @return void
	*/
	public function forceImageSize()
	{
		$this->forceImageSize = true;
		$this->resetTemplate();
	}

	/**
	* Omit the size of the image in the img element
	*
	* @return void
	*/
	public function omitImageSize()
	{
		$this->forceImageSize = false;
		$this->resetTemplate();
	}

	/**
	* Set the size of the images used for emoji
	*
	* @param  integer $size Preferred size
	* @return void
	*/
	public function setImageSize($size)
	{
		$this->imageSize = (int) $size;
		$this->resetTemplate();
	}

	/**
	* Use the EmojiOne image set
	*
	* @return void
	*/
	public function useEmojiOne()
	{
		$this->imageSet = 'emojione';
		$this->resetTemplate();
	}

	/**
	* Use PNG images if available
	*
	* @return void
	*/
	public function usePNG()
	{
		$this->imageType = 'png';
		$this->resetTemplate();
	}

	/**
	* Use SVG images if available
	*
	* @return void
	*/
	public function useSVG()
	{
		$this->imageType = 'svg';
		$this->resetTemplate();
	}

	/**
	* Use the Twemoji image set
	*
	* @return void
	*/
	public function useTwemoji()
	{
		$this->imageSet = 'twemoji';
		$this->resetTemplate();
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		return [
			'attrName' => $this->attrName,
			'tagName'  => $this->tagName
		];
	}

	/**
	* Get the template used to display EmojiOne's images
	*
	* @return string
	*/
	protected function getEmojiOneTemplate()
	{
		$template = '<img alt="{.}" class="emoji"';
		if ($this->forceImageSize)
		{
			$template .= ' width="' . $this->imageSize . '" height="' . $this->imageSize . '"';
		}
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

	/**
	* Get the first available size that satisfies our size requirement
	*
	* @param  integer[] $sizes Available sizes
	* @return integer
	*/
	protected function getTargetSize(array $sizes)
	{
		$k = 0;
		foreach ($sizes as $k => $size)
		{
			if ($size >= $this->imageSize)
			{
				break;
			}
		}

		return $sizes[$k];
	}

	/**
	* Get this tag's template
	*
	* @return string
	*/
	protected function getTemplate()
	{
		return ($this->imageSet === 'emojione') ? $this->getEmojiOneTemplate() :  $this->getTwemojiTemplate();
	}

	/**
	* Get the template used to display Twemoji's images
	*
	* @return string
	*/
	protected function getTwemojiTemplate()
	{
		$template = '<img alt="{.}" class="emoji" draggable="false"';
		if ($this->forceImageSize)
		{
			$template .= ' width="' . $this->imageSize . '" height="' . $this->imageSize . '"';
		}
		$template .= ' src="//twemoji.maxcdn.com/';
		if ($this->imageType === 'svg')
		{
			$template .= 'svg';
		}
		else
		{
			$size = $this->getTargetSize([16, 36, 72]);
			$template .= $size . 'x' . $size;
		}
		$template .= '/{@seq}.' . $this->imageType . '"/>';

		return $template;
	}

	/**
	* Reset the template used by this plugin's tag
	*
	* @return void
	*/
	protected function resetTemplate()
	{
		$this->getTag()->template = $this->getTemplate();
	}
}