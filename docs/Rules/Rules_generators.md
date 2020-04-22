<h2>Rules generators</h2>

Below is the list of individual RulesGenerators. Some of them are used for convenience (rules that are expected by the average user, such as suspending formatting where code is expected) some are used for compliance (to ensure the output remains valid HTML5) and some are used for both.

Most rules generators are enabled by default, some must be enabled manually.

See [Tag rules](Tag_rules.md) for the rules definitions.

<dl>

<dt id="allowall">AllowAll</dt>
<dd>
	<p><i>Purpose: convenience. Default: disabled.</i></p>
	<p>Generates <code>allowChild</code> and <code>allowDescendant</code> rules for every combination of tags. If effectively allows any tag to be used anywhere.</p>
</dd>

<dt id="autocloseifvoid">AutoCloseIfVoid</dt>
<dd>
	<p><i>Purpose: convenience. Default: enabled.</i></p>
	<p>Generates an <code>autoClose</code> rule for tags that are rendered as <a href="https://www.w3.org/TR/html5/syntax.html#void-elements">void elements</a>. For example, a BBCode that renders as an <code>img</code> element can be used as <code>[img=foo.png]</code>.</p>
</dd>

<dt id="autoreopenformattingelements">AutoReopenFormattingElements</dt>
<dd>
	<p><i>Purpose: convenience. Default: enabled.</i></p>
	<p>Generates an <code>autoReopen</code> rule for tags that are renderered as formatting elements. Emulates HTML5's behaviour regarding misnested formatting elements.</p>
</dd>

<dt id="blockelementscloseformattingelements">BlockElementsCloseFormattingElements</dt>
<dd>
	<p><i>Purpose: compliance. Default: enabled.</i></p>
	<p>Generates a <code>closeParent</code> rule for tags that are renderered as "block" elements, targeting formatting elements. For example, <code>div</code> inside of <code>b</code>.</p>
</dd>

<dt id="blockelementsfosterformattingelements">BlockElementsFosterFormattingElements</dt>
<dd>
	<p><i>Purpose: convenience/compliance. Default: enabled.</i></p>
	<p>Generates a <code>fosterParent</code> rule for tags that are renderered as "block" elements, targeting formatting elements. For example, <code>div</code> inside of <code>b</code>. Emulates HTML5's behaviour regarding misnested elements.</p>
</dd>

<dt id="disableautolinebreaksifnewlinesarepreserved">DisableAutoLineBreaksIfNewLinesArePreserved</dt>
<dd>
	<p><i>Purpose: convenience. Default: enabled.</i></p>
	<p>Generates a <code>disableAutoLineBreaks</code> rule for tags that render their content in an element that defaults to preserving new lines such as <code>pre</code>, or in an element that has a style attribute that preserves new lines such as <code>&lt;div style="white-space: pre"&gt;</code>.</p>
</dd>

<dt id="enforcecontentmodels">EnforceContentModels</dt>
<dd>
	<p><i>Purpose: compliance. Default: enabled.</i></p>
	<p>Generates <code>denyChild</code>, <code>denyDescendant</code>, <code>disableAutoLineBreaks</code>, <code>enableAutoLineBreaks</code> and <code>suspendAutoLineBreaks</code> rules to disallow tags in contexts where their HTML representation is not allowed.</p>
	<p>See <a href="https://www.w3.org/TR/html5/dom.html#content-models">HTML5 Content Models</a>.</p>
</dd>

<dt id="enforceoptionalendtags">EnforceOptionalEndTags</dt>
<dd>
	<p><i>Purpose: compliance. Default: enabled.</i></p>
	<p>Generates <code>closeParent</code> rules to automatically close tags in contexts where their end tag is optional (such as with consecutive <code>li</code> elements) and would otherwise be automatically created by the browser's HTML5 parser.</p>
	<p>See <a href="https://www.w3.org/TR/html5/syntax.html#optional-tags">HTML5 Optional Tags</a>.</p>
</dd>

<dt id="ignoretagsincode">IgnoreTagsInCode</dt>
<dd>
	<p><i>Purpose: convenience. Default: enabled.</i></p>
	<p>Generates an <code>ignoreTags</code> rule for tags that render their content in a <code>code</code> element.</p>
</dd>

<dt id="ignoretextifdisallowed">IgnoreTextIfDisallowed</dt>
<dd>
	<p><i>Purpose: compliance. Default: enabled.</i></p>
	<p>Generates an <code>ignoreText</code> rule for tags that disallow text content as per HTML5 content models. For example, between a <code>ul</code> element its <code>li</code> child.</p>
</dd>

<dt id="ignorewhitespacearoundblockelements">IgnoreWhitespaceAroundBlockElements</dt>
<dd>
	<p><i>Purpose: convenience. Default: enabled.</i></p>
	<p>Generates an <code>ignoreSurroundingWhitespace</code> rule for tags that render their content in a "block" element such as <code>div</code> or <code>blockquote</code>. The concept of a "block" element does not exist in HTML5, the term is used loosely to designate elements that do not use the HTML5 phrasing content model. This rule lets the user insert an empty line to separate block elements (such as citations or list items) without adding unwanted <code>br</code>s to the output.</p>
</dd>

<dt id="manageparagraphs">ManageParagraphs</dt>
<dd>
	<p><i>Purpose: convenience/compliance. Default: disabled.</i></p>
	<p>Generates <code>createParagraphs</code> rules for tags that render their content in a "block" element and <code>breakParagraph</code> for elements whose template automatically closes current paragraph as per HTML5's optional tags rules.</p>
</dd>

<dt id="trimfirstlineincodeblocks">TrimFirstLineInCodeBlocks</dt>
<dd>
	<p><i>Purpose: convenience. Default: enabled.</i></p>
	<p>Generates a <code>trimFirstLine</code> rule for tags that render their content in a <code>&lt;pre&gt;&lt;code&gt;</code> block.</p>
</dd>

</dl>