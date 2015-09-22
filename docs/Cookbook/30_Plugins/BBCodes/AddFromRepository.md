## Add BBCodes from the bundled repository

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

__ACRONYM__  
`[ACRONYM title={TEXT1;optional}]{TEXT2}[/ACRONYM]`
```xsl
<acronym title="{TEXT1}">{TEXT2}</acronym>
```

__ALIGN__  
`[ALIGN={CHOICE=left,right,center,justify}]{TEXT}[/ALIGN]`
```xsl
<div style="text-align:{CHOICE}">{TEXT}</div>
```

__B__  
`[B]{TEXT}[/B]`
```xsl
<b><xsl:apply-templates /></b>
```

__BACKGROUND__  
`[BACKGROUND={COLOR}]{TEXT}[/BACKGROUND]`
```xsl
<span style="background-color:{COLOR}">{TEXT}</span>
```

__C__  
`[C]{TEXT}[/C]`
```xsl
<code class="inline"><xsl:apply-templates /></code>
```

__CENTER__  
`[CENTER]{TEXT}[/CENTER]`
```xsl
<div style="text-align:center">{TEXT}</div>
```

__CODE__  
`[CODE lang={IDENTIFIER;optional}]{TEXT}[/CODE]`
```xsl
<!--
	if (typeof hljs !== 'undefined')
	{
		var html = this.innerHTML;
		if (html in hljs['_'])
		{
			this.innerHTML = hljs['_'][html];
		}
		else
		{
			if (Object.keys && Object.keys(hljs['_']).length > 7)
			{
				hljs['_'] = {}
			}
			hljs['highlightBlock'](this.firstChild);
			hljs['_'][html] = this.innerHTML;
		}
	}
-->
<pre data-s9e-livepreview-postprocess="if('undefined'!==typeof hljs){{var a=this.innerHTML;a in hljs._?this.innerHTML=hljs._[a]:(Object.keys&amp;&amp;7&lt;Object.keys(hljs._).length&amp;&amp;(hljs._={{}}),hljs.highlightBlock(this.firstChild),hljs._[a]=this.innerHTML)}}"><code class="{@lang}">
	<xsl:apply-templates />
</code></pre>
<!--
	if (typeof hljs === typeof hljsLoading)
	{
		hljsLoading = true;

		var head = document.getElementsByTagName('head')[0],
			el   = document.createElement('link'),
			stylesheetUrl = '//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.8.0/styles/default.min.css',
			scriptUrl = '//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.8.0/highlight.min.js';

		el.type = 'text/css';
		el.rel  = 'stylesheet';
		el.href = stylesheetUrl;

		head.appendChild(el);

		el        = document.createElement('script');
		el.type   = 'text/javascript';
		el.onload = function()
		{
			hljs['_'] = {};
			hljs['initHighlighting']();
			delete hljsLoading;
		};
		el.async = true;
		el.src   = scriptUrl;

		head.appendChild(el);
	}
-->
<script>if(typeof hljs===typeof hljsLoading){hljsLoading=!0;var head=document.getElementsByTagName("head")[0],el=document.createElement("link"),stylesheetUrl='//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.8.0/styles/default.min.css',scriptUrl='//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.8.0/highlight.min.js';el.type="text/css";el.rel="stylesheet";el.href=stylesheetUrl;head.appendChild(el);el=document.createElement("script");el.type="text/javascript";el.onload=function(){hljs._={};hljs.initHighlighting();delete hljsLoading};el.async=!0;el.src=scriptUrl;head.appendChild(el)}</script>
```
<table>
	<tr>
		<th>Var name</th>
		<th>Default</th>
		<th>Description</th>
	</tr>
	<tr>
		<td><code>stylesheetUrl</code></td>
		<td>//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.8.0/styles/default.min.css</td>
		<td></td>
	</tr>
	<tr>
		<td><code>scriptUrl</code></td>
		<td>//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.8.0/highlight.min.js</td>
		<td></td>
	</tr>
	<tr>
		<td><code>stylesheetUrl</code></td>
		<td>//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.8.0/styles/default.min.css</td>
		<td></td>
	</tr>
	<tr>
		<td><code>scriptUrl</code></td>
		<td>//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.8.0/highlight.min.js</td>
		<td></td>
	</tr>
</table>

__COLOR__  
`[COLOR={COLOR}]{TEXT}[/COLOR]`
```xsl
<span style="color:{COLOR}">{TEXT}</span>
```

__DD__  
`[DD]{TEXT}[/DD]`
```xsl
<dd>{TEXT}</dd>
```

__DEL__  
`[DEL]{TEXT}[/DEL]`
```xsl
<del>{TEXT}</del>
```

__DL__  
`[DL]{TEXT}[/DL]`
```xsl
<dl>{TEXT}</dl>
```

__DT__  
`[DT]{TEXT}[/DT]`
```xsl
<dt>{TEXT}</dt>
```

__EM__  
`[EM]{TEXT}[/EM]`
```xsl
<em>{TEXT}</em>
```

__EMAIL__  
`[EMAIL={EMAIL;useContent}]{TEXT}[/EMAIL]`
```xsl
<a href="mailto:{EMAIL}">{TEXT}</a>
```

__FLASH__  
`[FLASH={PARSE=/^(?<width>\d+),(?<height>\d+)/} width={RANGE=0,1920;defaultValue=80} height={RANGE=0,1080;defaultValue=60} url={URL;useContent}]
		`
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

__FLOAT__  
`[float={CHOICE=left,right,none}]{TEXT}[/float]`
```xsl
<div style="float:{CHOICE}">{TEXT}</div>
```

__FONT__  
`[font={SIMPLETEXT}]{TEXT}[/font]`
```xsl
<span style="font-family:{SIMPLETEXT}">{TEXT}</span>
```

__H1__  
`[H1]{TEXT}[/H1]`
```xsl
<h1>{TEXT}</h1>
```

__H2__  
`[H2]{TEXT}[/H2]`
```xsl
<h2>{TEXT}</h2>
```

__H3__  
`[H3]{TEXT}[/H3]`
```xsl
<h3>{TEXT}</h3>
```

__H4__  
`[H4]{TEXT}[/H4]`
```xsl
<h4>{TEXT}</h4>
```

__H5__  
`[H5]{TEXT}[/H5]`
```xsl
<h5>{TEXT}</h5>
```

__H6__  
`[H6]{TEXT}[/H6]`
```xsl
<h6>{TEXT}</h6>
```

__HR__  
`[HR]`
```xsl
<hr/>
```

__I__  
`[I]{TEXT}[/I]`
```xsl
<i>{TEXT}</i>
```

__IMG__  
`[IMG src={URL;useContent} title={TEXT;optional} alt={TEXT;optional}]`
```xsl
<img src="{@src}" title="{@title}" alt="{@alt}" />
```

__INS__  
`[INS]{TEXT}[/INS]`
```xsl
<ins>{TEXT}</ins>
```

__JUSTIFY__  
`[JUSTIFY]{TEXT}[/JUSTIFY]`
```xsl
<div style="text-align:justify">{TEXT}</div>
```

__LEFT__  
`[LEFT]{TEXT}[/LEFT]`
```xsl
<div style="text-align:left">{TEXT}</div>
```

__LIST__  
`[LIST type={HASHMAP=1:decimal,a:lower-alpha,A:upper-alpha,i:lower-roman,I:upper-roman;optional;postFilter=#simpletext}]{TEXT}[/LIST]`
```xsl
<xsl:choose>
	<xsl:when test="not(@type)">
		<ul><xsl:apply-templates /></ul>
	</xsl:when>
	<xsl:when test="starts-with(@type,'decimal') or starts-with(@type,'lower') or starts-with(@type,'upper')">
		<ol style="list-style-type:{@type}"><xsl:apply-templates /></ol>
	</xsl:when>
	<xsl:otherwise>
		<ul style="list-style-type:{@type}"><xsl:apply-templates /></ul>
	</xsl:otherwise>
</xsl:choose>
```

__*__  
`[*]{TEXT}[/*]`
```xsl
<li><xsl:apply-templates /></li>
```

__MAGNET__  
`[MAGNET={REGEXP=/^magnet:/;useContent}]{TEXT}[/MAGNET]`
```xsl
<a href="{REGEXP}"><img alt="" src="data:image/gif;base64,R0lGODlhDAAMALMPAOXl5ewvErW1tebm5oocDkVFRePj47a2ts0WAOTk5MwVAIkcDesuEs0VAEZGRv///yH5BAEAAA8ALAAAAAAMAAwAAARB8MnnqpuzroZYzQvSNMroUeFIjornbK1mVkRzUgQSyPfbFi/dBRdzCAyJoTFhcBQOiYHyAABUDsiCxAFNWj6UbwQAOw==" style="vertical-align:middle;border:0;margin:0 5px 0 0"/>{TEXT}</a>
```

__NOPARSE__  
`[NOPARSE #ignoreTags=true]{TEXT}[/NOPARSE]`
```xsl
{TEXT}
```

__QUOTE__  
`[QUOTE author={TEXT;optional}]{TEXT}[/QUOTE]`
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
		<td></td>
	</tr>
</table>

__RIGHT__  
`[RIGHT]{TEXT}[/RIGHT]`
```xsl
<div style="text-align:right">{TEXT}</div>
```

__S__  
`[S]{TEXT}[/S]`
```xsl
<s>{TEXT}</s>
```

__SIZE__  
`[SIZE={RANGE=8,36}]{TEXT}[/SIZE]`
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
		<td></td>
	</tr>
	<tr>
		<td><code>max</code></td>
		<td>36</td>
		<td></td>
	</tr>
</table>

__SPOILER__  
`[SPOILER title={TEXT1;optional}]{TEXT2}[/SPOILER]`
```xsl
<div class="spoiler">
	<div class="spoiler-header">
		<!--
			var nextSiblingStyle = parentNode.nextSibling.style,
				firstChildStyle  = firstChild.style,
				lastChildStyle   = lastChild.style;

			if (nextSiblingStyle.display !== '')
			{
				nextSiblingStyle.display = lastChildStyle.display = '';
				firstChildStyle.display  = 'none';
			}
			else
			{
				nextSiblingStyle.display = lastChildStyle.display = 'none';
				firstChildStyle.display  = '';
			}
		-->
		<button onclick="var a=parentNode.nextSibling.style,b=firstChild.style,c=lastChild.style;''!==a.display?(a.display=c.display='',b.display='none'):(a.display=c.display='none',b.display='')"><span>Show</span><span style="display:none">Hide</span></button>
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
		<td></td>
	</tr>
	<tr>
		<td><code>hideStr</code></td>
		<td>Hide</td>
		<td></td>
	</tr>
	<tr>
		<td><code>spoilerStr</code></td>
		<td>Spoiler:</td>
		<td></td>
	</tr>
</table>

__STRONG__  
`[STRONG]{TEXT}[/STRONG]`
```xsl
<strong>{TEXT}</strong>
```

__SUB__  
`[SUB]{TEXT}[/SUB]`
```xsl
<sub>{TEXT}</sub>
```

__SUP__  
`[SUP]{TEXT}[/SUP]`
```xsl
<sup>{TEXT}</sup>
```

__U__  
`[U]{TEXT}[/U]`
```xsl
<u>{TEXT}</u>
```

__URL__  
`[URL={URL;useContent} title={TEXT;optional}]{TEXT}[/URL]`
```xsl
<a href="{@url}"><xsl:copy-of select="@title" /><xsl:apply-templates /></a>
```

__VAR__  
`[VAR]{TEXT}[/VAR]`
```xsl
<var>{TEXT}</var>
```
