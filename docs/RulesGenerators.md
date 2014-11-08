Rules generators
================

Below is the list of individual RulesGenerators. Some of them are used for convenience (rules that are expected by the average user, such as suspending formatting where code is expected) some are used for compliance (to ensure the output remains valid HTML5) and some are used for both.

Most rules generators are enabled by default, some must be enabled manually.

See [Rules.md](Rules.md) for the rules definitions.

<dl>

<dt>AutoCloseIfVoid</dt>
<dd>
<i>Purpose: convenience. Default: enabled.</i><br/>
Generates an <code>autoClose</code> rule for tags that are rendered as <a href="http://www.w3.org/TR/html5/syntax.html#void-elements">void elements</a>. For example, a BBCode that renders as an <code>img</code> element can be used as <code>[img=foo.png]</code>.
</dd>

<dt>AutoReopenFormattingElements</dt>
<dd>
<i>Purpose: convenience. Default: enabled.</i><br/>
Generates an <code>autoReopen</code> rule for tags that are renderered as formatting elements. Emulates HTML5's behaviour regarding misnested formatting elements.
</dd>

<dt>BlockElementsCloseFormattingElements</dt>
<dd>
<i>Purpose: compliance. Default: disabled.</i><br/>
Generates a <code>closeParent</code> rule for tags that are renderered as "block" elements, targeting formatting elements. For example, <code>div</code> inside of <code>b</code>.
</dd>

<dt>BlockElementsFosterFormattingElements</dt>
<dd>
<i>Purpose: convenience/compliance. Default: enabled.</i><br/>
Generates a <code>fosterParent</code> rule for tags that are renderered as "block" elements, targeting formatting elements. For example, <code>div</code> inside of <code>b</code>. Emulates HTML5's behaviour regarding misnested elements.
</dd>

<dt>DisableAutoLineBreaksIfNewLinesArePreserved</dt>
<dd>
<i>Purpose: convenience. Default: enabled.</i><br/>
Generates a <code>disableAutoLineBreaks</code> rule for tags that render their content in an element that defaults to preserving new lines such as <code>pre</code>, or in an element that has a style attribute that preserves new lines such as <code>&lt;div style="white-space: pre"&gt;</code>.
</dd>

<dt>EnforceContentModels</dt>
<dd>
<i>Purpose: compliance. Default: enabled.</i><br/>
Generates <code>denyChild</code>, <code>denyDescendant</code>, <code>disableAutoLineBreaks</code>, <code>enableAutoLineBreaks</code> and <code>suspendAutoLineBreaks</code> rules to disallow tags in contexts where their HTML representation is not allowed.<br/>
See <a href="http://www.w3.org/TR/html5/dom.html#content-models">HTML5 Content Models</a>.
</dd>

<dt>EnforceOptionalEndTags</dt>
<dd>
<i>Purpose: compliance. Default: enabled.</i><br/>
Generates <code>closeParent</code> rules to automatically close tags in contexts where their end tag is optional (such as with consecutive <code>li</code> elements) and would otherwise be automatically created by the browser's HTML5 parser.<br/>
See <a href="http://www.w3.org/TR/html5/syntax.html#optional-tags">HTML5 Optional Tags</a>.
</dd>

<dt>IgnoreTagsInCode</dt>
<dd>
<i>Purpose: convenience. Default: enabled.</i><br/>
Generates an <code>ignoreTags</code> rule for tags that render their content in a <code>code</code> element.
</dd>

<dt>IgnoreTextIfDisallowed</dt>
<dd>
<i>Purpose: compliance. Default: enabled.</i><br/>
Generates an <code>ignoreText</code> rule for tags that disallow text content as per HTML5 content models. For example, between a <code>ul</code> element its <code>li</code> child.
</dd>

<dt>IgnoreWhitespaceAroundBlockElements</dt>
<dd>
<i>Purpose: convenience. Default: enabled.</i><br/>
Generates an <code>ignoreSurroundingWhitespace</code> rule for tags that render their content in a "block" element such as <code>div</code> or <code>blockquote</code>. The concept of a "block" element does not exist in HTML5, the term is used loosely to designate elements that do not use the HTML5 phrasing content model. This rule lets the user insert an empty line to separate block elements (such as citations or list items) without adding unwanted <code>br</code>s to the output.
</dd>

<dt>ManageParagraphs</dt>
<dd>
<i>Purpose: convenience/compliance. Default: disabled.</i><br/>
Generates <code>createParagraphs</code> rules for tags that render their content in a "block" element and <code>breakParagraph</code> for elements whose template automatically closes current paragraph as per HTML5's optional tags rules.
</dd>

</dl>