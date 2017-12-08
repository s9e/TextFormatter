#!/usr/bin/php
<?php

function loadPage($url)
{
	$filepath = sys_get_temp_dir() . '/' . basename($url);

	if (!file_exists($filepath))
	{
		copy(
			'compress.zlib://' . $url,
			$filepath,
			stream_context_create(['http' => ['header' => 'Accept-Encoding: gzip']])
		);
	}

	// ext/dom doesn't properly close dd elements whose end tag is absent
	$html = file_get_contents($filepath);
	$html = str_replace('<dd>', '</dd><dd>', $html);

	$page = new DOMDocument;
	$page->preserveWhiteSpace = false;
	@$page->loadHTML($html, LIBXML_COMPACT | LIBXML_NOBLANKS);

	return $page;
}

$page = loadPage('http://www.w3.org/TR/html/single-page.html');

//==============================================================================
// Formatting elements, which are automatically reopened
//==============================================================================

//$page  = loadPage('http://www.w3.org/TR/html5/syntax.html');
$nodes = $page->getElementById('ref-for-list-of-active-formatting-elements-1')
              ->parentNode
              ->getElementsByTagName('code');

$formattingElements = [];
foreach ($nodes as $node)
{
	$formattingElements[$node->textContent] = 1;
}

//==============================================================================
// Void elements
//==============================================================================

$nodes = $page->getElementById('void-elements')
              ->parentNode->nextSibling
              ->getElementsByTagName('code');

$voidElements = [];
foreach ($nodes as $node)
{
	$voidElements[$node->textContent] = 1;
}

//==============================================================================
// End tags that can be omitted => closeParent rules
//==============================================================================

$node = $page->getElementById('optional-tags');

$closeParent  = [];
$closeIfEmpty = [];
while (isset($node->nextSibling))
{
	$node = $node->nextSibling;
	if ($node->nodeName === 'h5')
	{
		break;
	}
	if ($node->nodeName !== 'p')
	{
		continue;
	}

	$text = preg_replace('#\\s+#', ' ', $node->textContent);

	if (!preg_match("#^An? ([a-z0-5]+) element.*?s end tag may be omitted if the \\1 element is immediately followed by a(?:n(other)?)? #", $text, $m))
	{
		continue;
	}

	$elName = $m[1];

	$text = substr($text, strlen($m[0]));
	if (strpos($text, ' or if there is no more content in the parent element.') !== false)
	{
		$closeIfEmpty[$elName] = 1;
		$text = str_replace(' or if there is no more content in the parent element.', '', $text);
	}
	$text = rtrim($text, ' .,');

	if (preg_match('#^([a-z]+) element$#', $text, $m))
	{
		$closeParent[$m[1]][$elName] = 0;
	}
	elseif (preg_match('#^([a-z]+) element or an? ([a-z]+) element$#', $text, $m)
	     || preg_match('#^([a-z]+) or ([a-z]+) element$#', $text, $m)
	     || preg_match('#^([a-z]+) element, or if it is immediately followed by an? ([a-z]+) element$#', $text, $m))
	{
		$closeParent[$m[1]][$elName] = 0;
		$closeParent[$m[2]][$elName] = 0;
	}
	elseif (preg_match('#^((?:\\w+, )*or [a-z]+) element, or if there is no more content in the parent element and the parent element is an HTML element that is not an? (?:\\w+, )*or \\w+ element$#', $text, $m))
	{
		foreach (preg_split('(, (?:or )?)', $m[1]) as $target)
		{
			$closeParent[$target][$elName] = 0;
		}
	}
	else
	{
		die("Could not interpret '$text'\n");
	}
}

//==============================================================================
// Content models
//==============================================================================

$xpath    = new DOMXPath($page);
$elements = [];

$query = '/html/body/main/section/section/h4[span/dfn[@data-dfn-type="element"]]';
foreach ($xpath->query($query) as $h4)
{
	if (!preg_match('(^[\\d.\\s]*The (?:\\w+, )*(?:(?:\\w+ )?and )*\\w+ elements?\\s*$)', $h4->textContent))
	{
		echo 'Skipping ', $h4->textContent, "\n";
		continue;
	}

	$dl = $h4->nextSibling;
	while ($dl->nodeName !== 'dl' || substr($dl->textContent, 0, 10) !== 'Categories')
	{
		$dl = $dl->nextSibling;
	}
	foreach ($h4->getElementsByTagName('dfn') as $dfn)
	{
		$elName = $dfn->textContent;
		$elements[$elName]['categories'] = getCategories($dl);
		$elements[$elName] += getContentModel($dl, $elName);
	}
}

function getCategories($dl)
{
	$cat = [];
	foreach (getDdText(getDt($dl, 'Categories')) as $text)
	{
		$text = strtolower($text);
		if (preg_match('(^(\\w+ content|[-\\w]+ element|sectioning root)\\.$)', $text, $m))
		{
			$cat[$m[1]][''] = 1;
		}
		elseif (preg_match('(^(\\w+ content), but with no \\w+ element descendants\\.$)', $text, $m))
		{
			// No need to make a distinction here
			$cat[$m[1]][''] = 1;
		}
		elseif (preg_match('(^if the element’s children include at least one (\\w+) element: (\\w+ content)\\.$)', $text, $m))
		{
			$cat[$m[2]][$m[1]] = 1;
		}
		elseif (preg_match('(^if the element’s children include at least one name-value group: (\\w+ content)\\.$)', $text, $m))
		{
			$cat[$m[1]]['dt and dd'] = 1;
		}
		elseif (preg_match('(^if the element has an? (\\w+) attribute: (\\w+ content)\\.$)', $text, $m))
		{
			$cat[$m[2]]['@' . $m[1]] = 1;
		}
		elseif (preg_match('(^(?:(?:\\w+, )*(?:\\w+ )?(?:and \\w+ )?)?form-associated element\\.$)', $text))
		{
			$cat['form-associated'][''] = 1;
		}
		elseif (preg_match('(^if the (\\w+) attribute is not in the (\\w+) state: (\\w+ content)\\.$)', $text, $m))
		{
			$cat[$m[3]]['@' . $m[1] . '!="' . $m[2] . '"'] = 1;
		}
		elseif (preg_match('(^if the (\\w+) attribute is not in the (\\w+) state: [\\w, ]*(form-associated element)\\.$)', $text, $m))
		{
			$cat[$m[3]]['@' . $m[1] . '!="' . $m[2] . '"'] = 1;
		}
		elseif (preg_match('(^if the (\\w+) attribute is in the (\\w+) state: [\\w, ]*(form-associated element)\\.$)', $text, $m))
		{
			$cat[$m[3]]['@' . $m[1] . '="' . $m[2] . '"'] = 1;
		}
		elseif ($text === 'none.')
		{
			continue;
		}
		else
		{
			die("Cannot parse category '$text'\n");
		}
	}

	return $cat;
}

function getContentModel($dl, $elName)
{
	$presets = [
		'iframe' => [
			// We allow phrasing content to be used as fallback content
			'allowChildCategory' => ['phrasing content' => ['' => 1]],
			'allowText'          => 1
		],
		'menu'   => [
			'allowChildCategory' => ['script-supporting element' => ['' => 1]],
			'allowChildElement'  => [
				'hr'       => ['' => 1],
				'menu'     => ['' => 1],
				'menuitem' => ['' => 1]
			]
		],
		'noscript' => [
			['flow content'     => ['' => 1]],
			['phrasing content' => ['' => 1]]
		],
		'option' => ['allowText' => 1],
		'ruby'   => [
			'allowChildCategory' => ['phrasing content' => ['' => 1]],
			'allowChildElement'  => [
				'rb'  => ['' => 1],
				'rp'  => ['' => 1],
				'rt'  => ['' => 1],
				'rtc' => ['' => 1]
			]
		],
		'script'   => ['allowText' => 1, 'textOnly' => 1],
		'style'    => ['allowText' => 1, 'textOnly' => 1],
		'template' => [
			['flow content'     => ['' => 1]],
			['phrasing content' => ['' => 1]]
		]
	];
	if (isset($presets[$elName]))
	{
		return $presets[$elName];
	}

	$model = [];
	foreach (getDdText(getDt($dl, 'Content model')) as $text)
	{
		$text = preg_replace('(\\s+)', ' ', strtolower($text));
		$text = preg_replace('(^either: |^or: )', '', $text);
		$text = rtrim($text, '.');

		if (preg_match('(^(\\w+ content|[-\\w]+ element|sectioning root|transparent)$)', $text, $m))
		{
			$model['allowChildCategory'][$m[1]][''] = 1;
		}
		elseif (preg_match('(^(\\w+ content), but with no descendant (\\w+) elements$)', $text, $m)
		     || preg_match('(^(\\w+ content), but there must be no (\\w+) element descendants$)', $text, $m))
		{
			$model['allowChildCategory'][$m[1]]['']    = 1;
			$model['denyDescendantElement'][$m[2]][''] = 1;
		}
		elseif (preg_match('(^(\\w+ content), but there must be no (\\w+ content) descendant$)', $text, $m))
		{
			$model['allowChildCategory'][$m[1]]['']     = 1;
			$model['denyDescendantCategory'][$m[2]][''] = 1;
		}
		elseif (preg_match('(^(\\w+ content), but with no (\\w+) elements$)', $text, $m))
		{
			$model['allowChildCategory'][$m[1]][''] = 1;
			$model['denyChildElement'][$m[2]]['']   = 1;
		}
		elseif ($text === 'zero or more param elements, then, transparent')
		{
			$model['allowChildElement']['param']['']        = 1;
			$model['allowChildCategory']['transparent'][''] = 1;
		}
		elseif ($text === 'if the element has a src attribute: zero or more track elements, then transparent, but with no media element descendants')
		{
			$model['allowChildElement']['track']['@src']             = 1;
			$model['allowChildCategory']['transparent']['@src']      = 1;
			$model['denyDescendantElement']['media element']['@src'] = 1;
		}
		elseif ($text === 'if the element does not have a src attribute: zero or more source elements, then zero or more track elements, then transparent, but with no media element descendants')
		{
			$model['allowChildElement']['source']['not(@src)']       = 1;
			$model['allowChildElement']['track']['not(@src)']        = 1;
			$model['allowChildCategory']['transparent']['not(@src)'] = 1;
			$model['denyDescendantElement']['media element']['@src'] = 1;
		}
		elseif ($text === 'in this order: optionally a caption element, followed by zero or more colgroup elements, followed optionally by a thead element, followed by either zero or more tbody elements or one or more tr elements, followed optionally by a tfoot element, optionally intermixed with one or more script-supporting elements')
		{
			$model['allowChildElement']['caption']['']                    = 1;
			$model['allowChildElement']['colgroup']['']                   = 1;
			$model['allowChildElement']['thead']['']                      = 1;
			$model['allowChildElement']['tbody']['']                      = 1;
			$model['allowChildElement']['tr']['']                         = 1;
			$model['allowChildElement']['tfoot']['']                      = 1;
			$model['allowChildCategory']['script-supporting element'][''] = 1;
		}
		elseif ($text === 'zero or more tr and script-supporting elements')
		{
			$model['allowChildElement']['tr']['']                         = 1;
			$model['allowChildCategory']['script-supporting element'][''] = 1;
		}
		elseif ($text === 'zero or more td, th, and script-supporting elements')
		{
			$model['allowChildElement']['td']['']                         = 1;
			$model['allowChildElement']['th']['']                         = 1;
			$model['allowChildCategory']['script-supporting element'][''] = 1;
		}
		elseif ($text === 'flow content, but with no header, footer, sectioning content, or heading content descendants')
		{
			$model['allowChildCategory']['flow content']['']           = 1;
			$model['denyDescendantElement']['header']['']              = 1;
			$model['denyDescendantCategory']['sectioning content'][''] = 1;
			$model['denyDescendantCategory']['heading content']['']    = 1;
		}
		elseif ($text === 'phrasing content, but with no descendant labelable elements unless it is the element’s labeled control, and no descendant label elements')
		{
			$model['allowChildCategory']['phrasing content'][''] = 1;
			$model['denyDescendantCategory']['labelable element'][''] = 1;
			$model['denyDescendantElement']['label'][''] = 1;
		}
		elseif ($text === 'zero or more li and script-supporting elements')
		{
			$model['allowChildElement']['li']['']                         = 1;
			$model['allowChildCategory']['script-supporting element'][''] = 1;
		}
		elseif ($text === 'zero or more groups each consisting of one or more dt elements followed by one or more dd elements, optionally intermixed with script-supporting elements')
		{
			$model['allowChildElement']['dt']['']                         = 1;
			$model['allowChildElement']['dd']['']                         = 1;
			$model['allowChildCategory']['script-supporting element'][''] = 1;
		}
		elseif ($text === 'zero or more source elements, followed by one img element, optionally intermixed with script-supporting elements')
		{
			$model['allowChildElement']['source']['']                     = 1;
			$model['allowChildElement']['img']['']                        = 1;
			$model['allowChildCategory']['script-supporting element'][''] = 1;
		}
		elseif ($text === 'zero or more option, optgroup, and script-supporting elements')
		{
			$model['allowChildElement']['option']['']                     = 1;
			$model['allowChildElement']['optgroup']['']                   = 1;
			$model['allowChildCategory']['script-supporting element'][''] = 1;
		}
		elseif ($text === 'zero or more option and script-supporting elements')
		{
			$model['allowChildElement']['option']['']                     = 1;
			$model['allowChildCategory']['script-supporting element'][''] = 1;
		}
		elseif ($text === 'flow content, but with no header, footer, sectioning content, or heading content descendants')
		{
			$model['allowChildCategory']['flow content']['']           = 1;
			$model['denyDescendantElement']['header']['']              = 1;
			$model['denyDescendantElement']['footer']['']              = 1;
			$model['denyDescendantCategory']['sectioning content'][''] = 1;
			$model['denyDescendantCategory']['heading content']['']    = 1;
		}
		elseif ($text === 'flow content optionally including a figcaption child element')
		{
			$model['allowChildCategory']['flow content'][''] = 1;
			$model['allowChildElement']['figcaption']['']    = 1;
		}
		elseif ($text === 'transparent, but there must be no interactive content or a element descendants')
		{
			$model['allowChildCategory']['transparent']['']             = 1;
			$model['denyDescendantCategory']['interactive content'][''] = 1;
			$model['denyDescendantElement']['a']['']                    = 1;
		}
		elseif ($text === 'flow content, but with no heading content descendants, no sectioning content descendants, and no header, footer, or address element descendants')
		{
			$model['allowChildCategory']['flow content']['']           = 1;
			$model['denyDescendantCategory']['heading content']['']    = 1;
			$model['denyDescendantCategory']['sectioning content'][''] = 1;
			$model['denyDescendantElement']['header']['']              = 1;
			$model['denyDescendantElement']['footer']['']              = 1;
			$model['denyDescendantElement']['address']['']             = 1;
		}
		elseif ($text === 'a head element followed by a body element')
		{
			$model['allowChildElement']['head'][''] = 1;
			$model['allowChildElement']['body'][''] = 1;
		}
		elseif ($text === 'if the document is an iframe srcdoc document or if title information is available from a higher-level protocol: zero or more elements of metadata content, of which no more than one is a title element and no more than one is a base element' || $text === 'otherwise: one or more elements of metadata content, of which exactly one is a title element and no more than one is a base element')
		{
			$model['allowChildCategory']['metadata content'][''] = 1;
		}
		elseif ($text === 'text' || $text === 'text that is not inter-element whitespace')
		{
			$model['allowText'] = 1;
			$model['textOnly']  = 1;
		}
		elseif ($text === 'nothing.' || $text === 'nothing')
		{
			$model['isEmpty'][''] = 1;
		}
		elseif ($text === 'otherwise: text , but must match requirements described in prose below')
		{
			$model['allowText'] = 1;
		}
		elseif (preg_match('(^if the (\\w+) attribute is present: nothing$)', $text, $m))
		{
			$model['isEmpty']['@' . $m[1]] = 1;
		}
		elseif (preg_match('(^if the (\\w+) attribute is absent: zero or more (\\w+) and (\\w+) elements$)', $text, $m))
		{
			$model['allowChildElement'][$m[2]]['not(@' . $m[1] . ')'] = 1;
			$model['allowChildElement'][$m[3]]['not(@' . $m[1] . ')'] = 1;
		}
		elseif (preg_match('(^if the element has a (\\w+) attribute: (\\w+ content)$)', $text, $m))
		{
			$model['allowChildCategory'][$m[2]]['@' . $m[1]] = 1;
		}
		elseif (preg_match('(^(\\w+ content), (\\w+),? or (\\w+) elements$)', $text, $m))
		{
			$model['allowChildCategory'][$m[1]][''] = 1;
			$model['allowChildElement'][$m[2]]['']  = 1;
			$model['allowChildElement'][$m[3]]['']  = 1;
		}
		elseif (preg_match('(^(\\w+ content), but with no main element descendants, or header, footer elements that are not descendants of sectioning content which is a descendant of the \\w+$)', $text, $m))
		{
			$model['allowChildCategory'][$m[1]]['']     = 1;
			$model['denyDescendantElement']['main'][''] = 1;
		}
		elseif (preg_match('(^(\\w+ content), but with no (\\w+) element descendants$)', $text, $m))
		{
			$model['allowChildCategory'][$m[1]]['']    = 1;
			$model['denyDescendantElement'][$m[2]][''] = 1;
		}
		elseif (preg_match('(^(?:optionally a|one) (\\w+) element,? followed by (\\w+ content)$)', $text, $m))
		{
			$model['allowChildElement'][$m[1]]['']  = 1;
			$model['allowChildCategory'][$m[2]][''] = 1;
		}
		elseif (preg_match('(^one element of (\\w+ content)$)', $text, $m))
		{
			$model['allowChildCategory'][$m[1]][''] = 1;
		}
		else
		{
			die("Cannot parse content model '$text'\n");
		}
	}

	return $model;
}

function getDdText($dt)
{
	$dds  = [];
	$node = $dt->nextSibling;
	while (isset($node))
	{
		if ($node->nodeName === 'dd')
		{
			$dds[] = trim($node->textContent);
		}
		elseif ($node->nodeType !== XML_TEXT_NODE)
		{
			break;
		}
		$node = $node->nextSibling;
	}

	return $dds;
}

function getDt($dl, $title)
{
	$node = $dl->firstChild;
	while (isset($node))
	{
		if (strpos($node->textContent, $title) === 0)
		{
			return $node;
		}
		$node = $node->nextSibling;
	}

	die("Cannot get dt element $title.\n");
}

//==============================================================================
// Gather the names of elements with a built-in "white-space: pre" CSS rule
//==============================================================================

preg_match_all(
	'#^(.*)\\{[^}]*?white-space:\\s*pre#m',
	$page->getElementById('rendering')->parentNode->textContent,
	$matches
);

foreach ($matches[1] as $elNames)
{
	foreach (explode(',', $elNames) as $elName)
	{
		// Remove predicates
		$elName = preg_replace('#\\[\\w+\\]#', '', trim($elName));

		if (isset($elements[$elName]))
		{
			$elements[$elName]['pre'] = 1;
		}
	}
}

// Flatten XPath queries for each target
foreach ($elements as $elName => &$element)
{
	$flatten = [
		'categories',
		'allowChildElement',
		'allowChildCategory',
		'denyChildElement',
		'denyChildCategory',
		'allowDescendantElement',
		'allowDescendantCategory',
		'denyDescendantElement',
		'denyDescendantCategory'
	];

	foreach ($flatten as $k)
	{
		if (!isset($element[$k]))
		{
			continue;
		}

		foreach ($element[$k] as &$xpath)
		{
			if (isset($xpath['']))
			{
				$xpath = '';
			}
			else
			{
				$xpath = implode(' or ', array_keys($xpath));

				// Optimize "@foo or not(@foo)" away
				$xpath = preg_replace('#^(@[a-z]+) or not\\(\\1\\)$#D', '', $xpath);
			}
		}
		unset($xpath);
	}
}
unset($element);

$categories = [];

// Create special categories for specific tag groups
foreach ($elements as &$element)
{
	$convert = [
		'allowChildElement',
		'denyDescendantElement'
	];

	foreach ($convert as $k)
	{
		if (isset($element[$k]))
		{
			// Sort elements by name so their order remain consistent for serialization
			ksort($element[$k]);

			// Sort elements by XPath condition
			$xpathElements = [];
			foreach ($element[$k] as $elName => $xpath)
			{
				$xpathElements[$xpath][] = $elName;
			}

			foreach ($xpathElements as $xpath => $elNames)
			{
				$category = serialize(array_unique($elNames));

				foreach ($elNames as $elName)
				{
					// Add our pseudo-category to each element of the group
					$elements[$elName]['categories'][$category] = '';
				}

				$element[preg_replace('#Element$#D', 'Category', $k)][$category] = $xpath;
			}
		}
	}
}
unset($element);

// Count the number of tags per category and remove the "transparent" pseudo-category
foreach ($elements as $elName => &$element)
{
	$element += ['categories' => []];

	foreach ($element['categories'] as $category => $xpath)
	{
		if (isset($categories[$category]))
		{
			++$categories[$category];
		}
		else
		{
			$categories[$category] = 1;
		}
	}

	if (isset($element['allowChildCategory']['transparent']))
	{
		if ($element['allowChildCategory']['transparent'] !== '')
		{
			// There's currently no real-world example of that, this is only future-proofing
			echo "!!! ATTENTION !!! The $elName element uses a conditionally transparent content model, which isn't currently supported\n";
		}

		$element['transparent'] = 1;
		unset($element['allowChildCategory']['transparent']);
	}
}
unset($element);

// Prepend each category's number before its name so we can sort them by frequency then name
foreach ($categories as $k => &$v)
{
	$v = sprintf('%03d', $v) . $k;
}
unset($v);

// Sort the categories then flip their keys so their values go in ascending order starting from 0
arsort($categories);
$categories = array_flip(array_keys($categories));

$arr = [];
foreach ($elements as $elName => $element)
{
	$el = [];

	$fields = [
		'categories' => 'c',
		'allowChildCategory' => 'ac',
		'denyDescendantCategory' => 'dd'
	];

	foreach ($fields as $k => $v)
	{
		$el[$v] = 0;
		if (!isset($element[$k]))
		{
			continue;
		}

		foreach ($element[$k] as $category => $xpath)
		{
			$bitNumber = $categories[$category];
			$el[$v] |= 1 << $bitNumber;

			if ($xpath)
			{
				$el[$v . $bitNumber] = $xpath;
			}
		}
	}

	// Test whether this element allows text nodes
	$noText = true;
	if (isset($el['ac']))
	{
		foreach (['flow content', 'palpable content', 'phrasing content'] as $category)
		{
			if ($el['ac'] & (1 << $categories[$category]))
			{
				$noText = false;
				break;
			}
		}
	}

	if ($noText && empty($element['allowText']))
	{
		$el['nt'] = 1;
	}
	elseif (!empty($element['pre']))
	{
		// NOTE: elements that do not allow text won't convert newlines to <br/> so we only need to
		//       mark elements that allow text AND preserve newlines
		$el['pre'] = 1;
	}

	if (!empty($element['isEmpty']))
	{
		$el['e'] = 1;

		if (!isset($element['isEmpty']['']))
		{
			$xpath = key($element['isEmpty']);
			if ($xpath)
			{
				$el['e?'] = $xpath;
			}
		}
	}

	if (isset($element['textOnly']))
	{
		$el['to'] = 1;
	}

	if (isset($voidElements[$elName]))
	{
		$el['v'] = 1;
	}

	if (!empty($element['transparent']))
	{
		$el['t'] = 1;
		unset($el['nt']);
	}

	// Mark elements that are not phrasing content -- "b" stands for "block"
	if (!($el['c'] & (1 << $categories['phrasing content'])))
	{
		$el['b'] = 1;
	}

	// Mark formatting elements
	if (isset($formattingElements[$elName]))
	{
		$el['fe'] = 1;
	}

	$arr[$elName] = $el;
}

//==============================================================================
// Elements with optional end tag if empty
//==============================================================================

// The HTML specs mention that some elements' end tag is optional "if there is no more content in
// the parent element." But how can we determine whether there is still some content and what
// elements would cause their parent to end. Here, we infer that an element E that automatically
// closes an element P (as per the rules stored in $closeParent) will also close element C if it's
// a child of E and both E and P's end tags are optional. This is not strictly true but in practice
// this is good enough to cover most (all?) actual interactions.
function getAllowedParents($arr, $elName)
{
	$parents = [];
	$c = $arr[$elName]['c'];
	foreach ($arr as $parentName => $el)
	{
		if (isset($el['ac']) && ($el['ac'] & $c))
		{
			$parents[] = $parentName;
		}
	}

	return $parents;
}
function getClosedIfEmptyClosers($arr, $elName)
{
	global $closeIfEmpty, $closeParent;

	$parentNames = [];
	foreach (getAllowedParents($arr, $elName) as $parentName)
	{
		foreach ($closeParent as $srcName => $targets)
		{
			if (isset($targets[$parentName]))
			{
				$parentNames[] = $srcName;
			}
		}
		if (isset($closeIfEmpty[$parentName]))
		{
			$parentNames[] = $parentName;
		}
	}

	// We do not recurse as it does not find more parent names and could theoretically lead to an
	// infinite loop in a future version of the specs
//	foreach (array_unique($parentNames) as $parentName)
//	{
//		$parentNames = array_merge($parentNames, getClosedIfEmptyClosers($arr, $parentName));
//	}

	return array_unique($parentNames);
}
foreach (array_keys($closeIfEmpty) as $targetName)
{
	$closerNames = getClosedIfEmptyClosers($arr, $targetName);
	foreach ($closerNames as $closerName)
	{
		$closeParent[$closerName][$targetName] = 1;
	}
}

// Sort the elements so that their order remain consistent across revisions
ksort($arr);

$php = '';
foreach ($arr as $elName => $elValues)
{
	$phpValues = [];

	foreach ($elValues as $k => $v)
	{
		if ($k === 'c' || $k === 'ac' || $k === 'dd')
		{
			$str = '"';

			// Build a bitfield using the octal notation, starting with the least significant byte
			do
			{
				$str .= '\\' . decoct($v & 255);
				$v >>= 8;
			}
			while ($v);

			$str .= '"';
		}
		else
		{
			$str = var_export($v, true);
		}

		$phpValues[$k] = $str;
	}

	if (isset($closeParent[$elName]))
	{
		ksort($closeParent[$elName]);
		$phpValues['cp'] = "['" . implode("','", array_keys($closeParent[$elName])) . "']";
	}

	$php .= "\n\t\t'" . $elName . "'=>[";
	foreach ($phpValues as $k => $v)
	{
		$php .= "'$k'=>$v,";
	}
	$php = substr($php, 0, -1) . '],';
}

$php = substr($php, 0, -1);

$filepath = __DIR__ . '/../src/Configurator/Helpers/ElementInspector.php';
$file = file_get_contents($filepath);

if (!preg_match('#(?<=static \\$htmlElements = \\[)(.*?)(?=\\n\\t\\];)#s', $file, $m, PREG_OFFSET_CAPTURE))
{
	die("Could not find the location in the file\n");
}

$file = substr($file, 0, $m[0][1]) . $php . substr($file, $m[0][1] + strlen($m[0][0]));

file_put_contents($filepath, $file);

die("Done.\n");