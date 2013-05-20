<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMNode;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

class DisallowPHPTags extends TemplateCheck
{
	/**
	* Prevent <?php tags from appearing in the stylesheet or in renderings
	*
	* NOTE: PHP tags have no effect in templates or in renderings, they are removed on the remote
	*       chance of being used as a vector, for example if a template is saved in a publicly
	*       accessible file that the webserver is somehow configured to process as PHP, or if the
	*       output is saved in a file (e.g. for static archives) that is parsed by PHP
	*
	* @param  DOMNode $template <xsl:template/> node
	* @param  Tag     $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMNode $template, Tag $tag)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//processing-instruction()["php" = translate(name(),"HP","hp")]';
		$nodes = $xpath->query($query);

		if ($nodes->length)
		{
			throw new UnsafeTemplateException('PHP tags are not allowed in the template', $nodes->item(0));
		}

		$query = '//xsl:processing-instruction["php" = translate(@name,"HP","hp")]';
		$nodes = $xpath->query($query);

		if ($nodes->length)
		{
			throw new UnsafeTemplateException('PHP tags are not allowed in the output', $nodes->item(0));
		}

		$query = '//xsl:processing-instruction[contains(@name, "{")]';
		$nodes = $xpath->query($query);

		if ($nodes->length)
		{
			throw new UnsafeTemplateException('Dynamic processing instructions are not allowed', $nodes->item(0));
		}
	}
}