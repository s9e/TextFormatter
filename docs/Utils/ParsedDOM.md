### Getting started

The ParsedDOM utility allows you to load the [parsed representation of a text](../Getting_started/How_it_works.md) (XML that is usually stored in a database) into a DOM document and operate on it with regular DOM methods as well as a [specialized API](https://s9e.github.io/TextFormatter/api/s9e/TextFormatter/Utils/ParsedDOM.html). Unlike native string manipulation it provides better guarantees that the resulting XML will match what the parser would normally produce. It is best suited for maintenance tasks. For lightweight, real-time operations, it is recommended to use the limited but more efficient [Utils](https://s9e.github.io/TextFormatter/api/s9e/TextFormatter/Utils.html) class if possible.

```php
// Start with the parsed representation of the text
$xml = '<r><p>Hello <EM><s>*</s>world<e>*</e></EM> &#128512;</p></r>';

// Load it into a DOM document
$dom = s9e\TextFormatter\Utils\ParsedDOM::loadXML($xml);

// Select each EM elements using XPath...
foreach ($dom->query('//EM') as $em)
{
	// ...and unparse it
	$em->unparse();
}

// Converting the document to a string will serialize it back to XML in a way that
// matches what the parser would output. This is different from calling saveXML()
echo '__toString() ', (string) $dom . "\n";
echo 'saveXML()    ', $dom->saveXML();
```
```
__toString() <t><p>Hello *world* &#128512;</p></t>
saveXML()    <?xml version="1.0"?>
<t><p>Hello *world* &#x1F600;</p></t>
```


### Replacing a tag and its markup

In the following example, we replace Markdown-style emphasis with a `I` BBCode. The Litedown plugin uses `EM` tags for emphasis whereas the BBCodes plugin uses `I` tags for `I` BBCodes, so we have to replace element with a new tag, and replace its markup without touching its content.

```php
$xml = '<r><p>Hello <EM><s>*</s>world<e>*</e></EM></p></r>';
$dom = s9e\TextFormatter\Utils\ParsedDOM::loadXML($xml);

// Select each EM element
foreach ($dom->query('//EM') as $em)
{
	// Replace it with what a I tag would generate (a I element)
	$b = $em->replaceTag('I');

	// Set the markup for this new element/tag, it will be placed in the appropriate location
	$b->setMarkupStart('[i]');
	$b->setMarkupEnd('[/i]');
}

echo $dom;
```
```
<r><p>Hello <I><s>[i]</s>world<e>[/i]</e></I></p></r>
```


### Replacing a tag and its content

In the following example, we replace an embedded YouTube video with a normal text link using BBCode markup. Here we set its text content to be the YouTube URL, but it could be replaced by something more meaningful such as the video's title.


```php
$xml = '<r><YOUTUBE id="QH2-TGUlwu4">https://www.youtube.com/watch?v=QH2-TGUlwu4</YOUTUBE></r>';
$dom = s9e\TextFormatter\Utils\ParsedDOM::loadXML($xml);

// Select each YOUTUBE element with an id attribute
foreach ($dom->query('//YOUTUBE[@id]') as $youtubeElement)
{
	// Generate a URL for the original video
	$url = str_starts_with($youtubeElement->textContent, 'https://')
	     ? $youtubeElement->textContent
	     : 'https://youtu.be/' . $youtubeElement->getAttribute('id');

	// Replace the YOUTUBE element with what a [url] BBCode would produce. The default [url]
	// BBCode uses a URL tag with a url attribute
	$urlElement = $youtubeElement->replaceTag('URL', ['url' => $url]);

	// Reset its text content and add the appropriate markup. The order is important here as
	// overwriting the text content of an element will remove its markup
	$urlElement->textContent = $url;
	$urlElement->setMarkupStart('[url]');
	$urlElement->setMarkupEnd('[/url]');
}

echo $dom;
```
```
<r><URL url="https://www.youtube.com/watch?v=QH2-TGUlwu4"><s>[url]</s>https://www.youtube.com/watch?v=QH2-TGUlwu4<e>[/url]</e></URL></r>
```
