<h2>Add BBCodes from the bundled repository</h2>

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addFromRepository('B');
$configurator->BBCodes->addFromRepository('I');
$configurator->BBCodes->addFromRepository('URL');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Here be [url=http://example.org]the [b]bold[/b] [i]italic[/i] URL[/url].';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Here be <a href="http://example.org">the <b>bold</b> <i>italic</i> URL</a>.
```

### Add a configurable BBCode from the bundled repository

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addFromRepository('SIZE', 'default', ['min' => 5, 'max' => 40]);

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = "[size=1]Smallest[/size]\n[size=99]Biggest[/size]";
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<span style="font-size:5px">Smallest</span>
<span style="font-size:40px">Biggest</span>
```

### List of bundled BBCodes

###### ACRONYM
```[ACRONYM title={TEXT1?}]{TEXT2}[/ACRONYM]```
```xsl
<acronym title="{TEXT1}">{TEXT2}</acronym>
```

###### ALIGN
```[ALIGN={CHOICE=left,right,center,justify}]{TEXT}[/ALIGN]```
```xsl
<div style="text-align:{CHOICE}">{TEXT}</div>
```

###### B
```[B]{TEXT}[/B]```
```xsl
<b><xsl:apply-templates /></b>
```

###### BACKGROUND
```[BACKGROUND={COLOR}]{TEXT}[/BACKGROUND]```
```xsl
<span style="background-color:{COLOR}">{TEXT}</span>
```

###### C
```[C]{TEXT}[/C]```
```xsl
<code class="inline"><xsl:apply-templates /></code>
```

###### CENTER
```[CENTER]{TEXT}[/CENTER]```
```xsl
<div style="text-align:center">{TEXT}</div>
```

###### CODE
```[CODE lang={IDENTIFIER?}]{TEXT}[/CODE]```
```xsl
<pre data-s9e-livepreview-hash="" data-s9e-livepreview-onupdate="if(typeof hljsLoader!=='undefined')hljsLoader.highlightBlocks(this)">
	<code>
		<xsl:if test="@lang">
			<xsl:attribute name="class">language-<xsl:value-of select="@lang"/></xsl:attribute>
		</xsl:if>
		<xsl:apply-templates />
	</code>
	<script async="" crossorigin="anonymous">
		<xsl:if test="'default' != 'github-gist'">
			<xsl:attribute name="data-hljs-style">
				github-gist
			</xsl:attribute>
		</xsl:if>
		<xsl:if test="'https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@10.2.1/build/' != 'https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@10.2.1/build/'">
			<xsl:attribute name="data-hljs-url">
				https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@10.2.1/build/
			</xsl:attribute>
		</xsl:if>
		<xsl:attribute name="data-s9e-livepreview-onrender">if(typeof hljsLoader!=='undefined')this.parentNode.removeChild(this)</xsl:attribute>
		<xsl:attribute name="integrity">sha384-kBP7QXPLhMrjryTXt/DbHNLhpGntUAuqLVHeBTFUAmpLKJvJt35XA4brF9DFQ1NQ</xsl:attribute>
		<xsl:attribute name="onload">hljsLoader.highlightBlocks(this.parentNode)</xsl:attribute>
		<xsl:attribute name="src">https://cdn.jsdelivr.net/gh/s9e/hljs-loader@1.0.14/loader.min.js</xsl:attribute>
	</script>
</pre>
```
<table>
	<tr>
		<th>Var name</th>
		<th>Default</th>
		<th>Description</th>
	</tr>
	<tr>
		<td><code>style</code></td>
		<td>github-gist</td>
		<td>highlight.js style name (or "none")</td>
	</tr>
	<tr>
		<td><code>url</code></td>
		<td>https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@10.2.1/build/</td>
		<td>highlight.js CDN URL</td>
	</tr>
</table>

###### COLOR
```[COLOR={COLOR}]{TEXT}[/COLOR]```
```xsl
<span style="color:{COLOR}">{TEXT}</span>
```

###### DD
```[DD]{TEXT}[/DD]```
```xsl
<dd>{TEXT}</dd>
```

###### DEL
```[DEL]{TEXT}[/DEL]```
```xsl
<del>{TEXT}</del>
```

###### DL
```[DL]{TEXT}[/DL]```
```xsl
<dl>{TEXT}</dl>
```

###### DT
```[DT]{TEXT}[/DT]```
```xsl
<dt>{TEXT}</dt>
```

###### EM
```[EM]{TEXT}[/EM]```
```xsl
<em>{TEXT}</em>
```

###### EMAIL
```[EMAIL={EMAIL;useContent}]{TEXT}[/EMAIL]```
```xsl
<a href="mailto:{EMAIL}">{TEXT}</a>
```

###### FLASH
```[FLASH={PARSE=/^(?<width>\d+),(?<height>\d+)/} width={RANGE=0,1920;defaultValue=80} height={RANGE=0,1080;defaultValue=60} url={URL;useContent}]
		```
```xsl
<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://fpdownload.macromedia.com/get/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="{@width}" height="{@height}">
	<param name="movie" value="{@url}" />
	<param name="quality" value="high" />
	<param name="wmode" value="opaque" />
	<param name="play" value="false" />
	<param name="loop" value="false" />

	<param name="allowScriptAccess" value="never" />
	<param name="allowNetworking" value="internal" />

	<embed src="{@url}" quality="high" width="{@width}" height="{@height}" wmode="opaque" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer" play="false" loop="false" allowscriptaccess="never" allownetworking="internal"></embed>
</object>
```
<table>
	<tr>
		<th>Var name</th>
		<th>Default</th>
		<th>Description</th>
	</tr>
	<tr>
		<td><code>minWidth</code></td>
		<td>0</td>
		<td>Minimum width for the Flash object</td>
	</tr>
	<tr>
		<td><code>maxWidth</code></td>
		<td>1920</td>
		<td>Maximum width for the Flash object</td>
	</tr>
	<tr>
		<td><code>minHeight</code></td>
		<td>0</td>
		<td>Minimum height for the Flash object</td>
	</tr>
	<tr>
		<td><code>maxHeight</code></td>
		<td>1080</td>
		<td>Maximum height for the Flash object</td>
	</tr>
</table>

###### FLOAT
```[float={CHOICE=left,right,none}]{TEXT}[/float]```
```xsl
<div style="float:{CHOICE}">{TEXT}</div>
```

###### FONT
```[font={FONTFAMILY}]{TEXT}[/font]```
```xsl
<span style="font-family:{FONTFAMILY}">{TEXT}</span>
```

###### H1
```[H1]{TEXT}[/H1]```
```xsl
<h1>{TEXT}</h1>
```

###### H2
```[H2]{TEXT}[/H2]```
```xsl
<h2>{TEXT}</h2>
```

###### H3
```[H3]{TEXT}[/H3]```
```xsl
<h3>{TEXT}</h3>
```

###### H4
```[H4]{TEXT}[/H4]```
```xsl
<h4>{TEXT}</h4>
```

###### H5
```[H5]{TEXT}[/H5]```
```xsl
<h5>{TEXT}</h5>
```

###### H6
```[H6]{TEXT}[/H6]```
```xsl
<h6>{TEXT}</h6>
```

###### HR
```[HR]```
```xsl
<hr/>
```

###### I
```[I]{TEXT}[/I]```
```xsl
<i>{TEXT}</i>
```

###### IMG
```[IMG src={URL;useContent} title={TEXT?} alt={TEXT?} height={UINT?}  width={UINT?} ]```
```xsl
<img src="{@src}" title="{@title}" alt="{@alt}">
	<xsl:copy-of select="@height"/>
	<xsl:copy-of select="@width"/>
</img>
```

###### INS
```[INS]{TEXT}[/INS]```
```xsl
<ins>{TEXT}</ins>
```

###### JUSTIFY
```[JUSTIFY]{TEXT}[/JUSTIFY]```
```xsl
<div style="text-align:justify">{TEXT}</div>
```

###### LEFT
```[LEFT]{TEXT}[/LEFT]```
```xsl
<div style="text-align:left">{TEXT}</div>
```

###### LIST
```[LIST type={HASHMAP=1:decimal,a:lower-alpha,A:upper-alpha,i:lower-roman,I:upper-roman;optional;postFilter=#simpletext} start={UINT;optional} #createChild=LI]{TEXT}[/LIST]```
```xsl
<xsl:choose>
	<xsl:when test="not(@type)">
		<ul><xsl:apply-templates /></ul>
	</xsl:when>
	<xsl:when test="starts-with(@type,'decimal') or starts-with(@type,'lower') or starts-with(@type,'upper')">
		<ol style="list-style-type:{@type}"><xsl:copy-of select="@start"/><xsl:apply-templates /></ol>
	</xsl:when>
	<xsl:otherwise>
		<ul style="list-style-type:{@type}"><xsl:apply-templates /></ul>
	</xsl:otherwise>
</xsl:choose>
```

###### *
```[*]{TEXT}[/*]```
```xsl
<li><xsl:apply-templates /></li>
```

###### MAGNET
```[MAGNET={REGEXP=/^magnet:/;useContent}]{TEXT}[/MAGNET]```
```xsl
<a href="{REGEXP}"><img alt="" src="data:image/gif;base64,R0lGODlhDAAMALMPAOXl5ewvErW1tebm5oocDkVFRePj47a2ts0WAOTk5MwVAIkcDesuEs0VAEZGRv///yH5BAEAAA8ALAAAAAAMAAwAAARB8MnnqpuzroZYzQvSNMroUeFIjornbK1mVkRzUgQSyPfbFi/dBRdzCAyJoTFhcBQOiYHyAABUDsiCxAFNWj6UbwQAOw==" style="vertical-align:middle;border:0;margin:0 5px 0 0"/>{TEXT}</a>
```

###### NOPARSE
```[NOPARSE #ignoreTags=true]{TEXT}[/NOPARSE]```
```xsl
{TEXT}
```

###### OL
```[OL]{TEXT}[/OL]```
```xsl
<ol>{TEXT}</ol>
```

###### QUOTE
```[QUOTE author={TEXT?}]{TEXT}[/QUOTE]```
```xsl
<blockquote>
	<xsl:if test="not(@author)">
		<xsl:attribute name="class">uncited</xsl:attribute>
	</xsl:if>
	<div>
		<xsl:if test="@author">
			<cite>
				<xsl:value-of select="@author" /> wrote:
			</cite>
		</xsl:if>
		<xsl:apply-templates />
	</div>
</blockquote>
```
<table>
	<tr>
		<th>Var name</th>
		<th>Default</th>
		<th>Description</th>
	</tr>
	<tr>
		<td><code>authorStr</code></td>
		<td>&lt;xsl:value-of select=&quot;@author&quot; /&gt; wrote:</td>
		<td>Author string</td>
	</tr>
</table>

###### RIGHT
```[RIGHT]{TEXT}[/RIGHT]```
```xsl
<div style="text-align:right">{TEXT}</div>
```

###### S
```[S]{TEXT}[/S]```
```xsl
<s>{TEXT}</s>
```

###### SIZE
```[SIZE={RANGE=8,36}]{TEXT}[/SIZE]```
```xsl
<span style="font-size:{RANGE}px">{TEXT}</span>
```
<table>
	<tr>
		<th>Var name</th>
		<th>Default</th>
		<th>Description</th>
	</tr>
	<tr>
		<td><code>min</code></td>
		<td>8</td>
		<td>Minimum size in px</td>
	</tr>
	<tr>
		<td><code>max</code></td>
		<td>36</td>
		<td>Maximum size in px</td>
	</tr>
</table>

###### SPOILER
```[SPOILER title={TEXT1?}]{TEXT2}[/SPOILER]```
```xsl
<div class="spoiler">
	<div class="spoiler-header">
		<button onclick="var a=parentNode.nextSibling.style,b=firstChild.style,c=lastChild.style;b.display=a.display;a.display=c.display=(b.display)?'':'none';return!1"><span>Show</span><span style="display:none">Hide</span></button>
		<span class="spoiler-title">Spoiler: {TEXT1}</span>
	</div>
	<div class="spoiler-content" style="display:none">{TEXT2}</div>
</div>
```
<table>
	<tr>
		<th>Var name</th>
		<th>Default</th>
		<th>Description</th>
	</tr>
	<tr>
		<td><code>showStr</code></td>
		<td>Show</td>
		<td>String used for the Show button</td>
	</tr>
	<tr>
		<td><code>hideStr</code></td>
		<td>Hide</td>
		<td>String used for the Hide button</td>
	</tr>
	<tr>
		<td><code>spoilerStr</code></td>
		<td>Spoiler:</td>
		<td>String used for the spoiler label</td>
	</tr>
</table>

###### STRONG
```[STRONG]{TEXT}[/STRONG]```
```xsl
<strong>{TEXT}</strong>
```

###### SUB
```[SUB]{TEXT}[/SUB]```
```xsl
<sub>{TEXT}</sub>
```

###### SUP
```[SUP]{TEXT}[/SUP]```
```xsl
<sup>{TEXT}</sup>
```

###### TABLE
```[TABLE]{ANYTHING}[/TABLE]```
```xsl
<table>{ANYTHING}</table>
```

###### TBODY
```[TBODY]{ANYTHING}[/TBODY]```
```xsl
<tbody>{ANYTHING}</tbody>
```

###### TD
```[TD align={CHOICE=left,center,right,justify;caseSensitive;optional;preFilter=strtolower} colspan={UINT?} rowspan={UINT?} #createParagraphs=false]{TEXT}[/TD]```
```xsl
<td>
	<xsl:copy-of select="@colspan"/>
	<xsl:copy-of select="@rowspan"/>
	<xsl:if test="@align">
		<xsl:attribute name="style">text-align:{CHOICE}</xsl:attribute>
	</xsl:if>
	<xsl:apply-templates/>
</td>
```

###### TH
```[TH align={CHOICE=left,center,right,justify;caseSensitive;optional;preFilter=strtolower} colspan={UINT?} rowspan={UINT?} #createParagraphs=false]{TEXT}[/TH]```
```xsl
<th>
	<xsl:copy-of select="@colspan"/>
	<xsl:copy-of select="@rowspan"/>
	<xsl:if test="@align">
		<xsl:attribute name="style">text-align:{CHOICE}</xsl:attribute>
	</xsl:if>
	<xsl:apply-templates/>
</th>
```

###### THEAD
```[THEAD]{ANYTHING}[/THEAD]```
```xsl
<thead>{ANYTHING}</thead>
```

###### TR
```[TR]{ANYTHING}[/TR]```
```xsl
<tr>{ANYTHING}</tr>
```

###### U
```[U]{TEXT}[/U]```
```xsl
<u>{TEXT}</u>
```

###### UL
```[UL]{TEXT}[/UL]```
```xsl
<ul>{TEXT}</ul>
```

###### URL
```[URL={URL;useContent} title={TEXT?}]{TEXT}[/URL]```
```xsl
<a href="{@url}"><xsl:copy-of select="@title" /><xsl:apply-templates /></a>
```

###### VAR
```[VAR]{TEXT}[/VAR]```
```xsl
<var>{TEXT}</var>
```
