<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

class DisallowPHPTags extends TemplateCheck
{
	/**
	* Prevent PHP tags from appearing in the stylesheet or in renderings
	*
	* Targets <?php tags as well as <script language="php">. Cannot target short tags or ASP tags.
	* Assumes that element names and attribute names are normalized to lowercase by the template
	* normalizer. Does not cover script elements in the output, dynamic xsl:element names are
	* handled by DisallowDynamicElementNames.
	*
	* NOTE: PHP tags have no effect in templates or in renderings, they are removed on the remote
	*       chance of being used as a vector, for example if a template is saved in a publicly
	*       accessible file that the webserver is somehow configured to process as PHP, or if the
	*       output is saved in a file (e.g. for static archives) that is parsed by PHP
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag        $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		$queries = [
			'//processing-instruction()["php" = translate(name(),"HP","hp")]'
				=> 'PHP tags are not allowed in the template',

			'//script["php" = translate(@language,"HP","hp")]'
				=> 'PHP tags are not allowed in the template',

			'//xsl:processing-instruction["php" = translate(@name,"HP","hp")]'
				=> 'PHP tags are not allowed in the output',

			'//xsl:processing-instruction[contains(@name, "{")]'
				=> 'Dynamic processing instructions are not allowed',
		];

		$xpath = new DOMXPath($template->ownerDocument);
		foreach ($queries as $query => $error)
		{
			$nodes = $xpath->query($query); 

			if ($nodes->length)
			{
				throw new UnsafeTemplateException($error, $nodes->item(0));
			}
		}
	}
}