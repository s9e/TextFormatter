<h2>Extend the template syntax through normalization</h2>

In the following example, we create a custom normalization that replaces pairs of comments in the form `<!-- IF FOO -->`, `<!-- ENDIF -->` with a normal `<xsl:if test="$FOO">` element. Since template normalization is automatically run on every template, this effectively extends the template syntax to support this new construct.

```php
$configurator = new s9e\TextFormatter\Configurator;

// We prepend our callback so that it's executed before comments are removed
$configurator->templateNormalizer->prepend(
	function (DOMNode $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);
		$query = '//comment()[. = " ENDIF "]';

		// Query all ENDIF comments
		foreach ($xpath->query($query) as $comment)
		{
			// Iterate backwards from the ENDIF comment until we find an IF comment
			$node = $comment->previousSibling;

			while ($node)
			{
				if ($node->nodeType === XML_COMMENT_NODE
				 && preg_match('/^ IF (\\w+) $/', $node->textContent, $m))
				{
					break;
				}

				$node = $node->previousSibling;
			}

			if (empty($m))
			{
				continue;
			}

			// Create the xsl:if element that will replace the pair of comments
			$xslIf = $dom->createElementNS('http://www.w3.org/1999/XSL/Transform', 'if');
			$xslIf->setAttribute('test', '$' . $m[1]);

			// Iterate forward from the IF comment to the ENDIF and move nodes to the xsl:if
			while (!$node->nextSibling->isSameNode($comment))
			{
				$xslIf->appendChild($node->parentNode->removeChild($node->nextSibling));
			}

			// All that's left is to remove the ENDIF comment and replace the IF comment with xsl:if
			$node->parentNode->removeChild($node->nextSibling);
			$node->parentNode->replaceChild($xslIf, $node);
		}
	}
);

echo $configurator->templateNormalizer->normalizeTemplate('
	<!-- IF S_USER_LOGGED_IN -->
	<div>Welcome!</div>
	<!-- ENDIF -->
');
```
```xslt
<xsl:if test="$S_USER_LOGGED_IN"><div>Welcome!</div></xsl:if>
```
