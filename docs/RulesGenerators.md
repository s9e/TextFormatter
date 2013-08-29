Rules generators
================

Below is the list of individual RulesGenerators. Some of them are used for convenience (rules that are expected by the average user, such as suspending formatting where code is expected) some are used for compliance (to ensure the output remains valid HTML5) and some are used for both.

Most rules generators are enabled by default, some must be enabled manually.

<dl>

<dt>AutoCloseIfVoid</dt>
<dd>
<i>Purpose: convenience, default: enabled.</i><br/>
Generates an <code>autoClose</code> rule for tags that are rendered as void elements. For example, a BBCode that renders as an <code>img</code> element can be used as <code>[img=foo.png]</code>.
</dd>

<dt>AutoReopenFormattingElements</dt>
<dd>
<i>Purpose: convenience, default: enabled.</i><br/>
Generates an <code>autoReopen</code> rule for tags that are renderered as formatting elements. Emulates HTML5's behaviour regarding misnested formatting elements.
</dd>

<dt>EnforceContentModels</dt>
<dd>
<i>Purpose: compliance, default: enabled.</i><br/>
Generates <code>denyChild</code>, <code>denyDescendant</code>, <code>noBrChild</code> and <code>noBrDescendant</code> rules to disallow tags in contexts where they are not allowed.<br/>
See [HTML5 Content Models](http://www.w3.org/TR/html5/dom.html#content-models).
</dd>

<dt>EnforceOptionalEndTags</dt>
<dd>
<i>Purpose: compliance, default: enabled.</i><br/>
Generates <code>closeParent</code> rules to automatically close tags in contexts where their end tag is optional and would otherwise be automatically created by the browser.<br/>
See [HTML5 Optional Tags](http://www.w3.org/TR/html5/syntax.html#optional-tags).
</dd>

<dt>IgnoreTagsInCode</dt>
<dd>
<i>Purpose: convenience, default: enabled.</i><br/>
Generates an <code>ignoreTags</code> rule for tags that render their content in a <code>code</code> element.
</dd>

<dt>IgnoreTextIfDisallowed</dt>
<dd>
<i>Purpose: compliance, default: enabled.</i><br/>
Generates an <code>ignoreText</code> rule for tags that disallow text content as per HTML5 content models.
</dd>

<dt>IgnoreWhitespaceAroundBlockElements</dt>
<dd>
<i>Purpose: convenience, default: enabled.</i><br/>
Generates an <code>ignoreSurroundingWhitespace</code> rule for tags that render their content in a "block" element such as `div` or `blockquote`. The concept of a "block" element does not exist in HTML5, the term is used loosely to designate elements that do not use the HTML5 phrasing content model.
</dd>

<dt>ManageParagraphs</dt>
<dd>
<i>Purpose: convenience/compliance, default: disabled.</i><br/>
Generates <code>createParagraphs</code> rules for tags that render their content in a "block" element and <code>breakParagraph</code> for elements whose template automatically close current paragraph as per HTML5's optional tags rules.
</dd>

<dt>NoBrIfWhitespaceIsPreserved</dt>
<dd>
<i>Purpose: convenience, default: enabled.</i><br/>
Generates a <code>noBrDescendant</code> rule for tags that render their content in an element that defaults to preserving whitespace, such as `pre`.
</dd>

</dl>