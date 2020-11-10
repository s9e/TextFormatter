<code>escape<code> values:

 - <code>attribute<code>
 - <code>comment<code> ?
 - <code>raw<code>
 - <code>text<code>
 - <code>url<code> ?

<dl>
	<dt>apply_templates</dt>
	<dd>Recursiverly process all child nodes, or nodes selected by optional XPath expression <code>select<code>.</dd>

	<dt>buffer_append_literal</dt>
	<dd>Append given string literal <code>value</code> to buffer <code>id</code>.</dd>

	<dt>buffer_append_xpath</dt>
	<dd>Append the result of XPath expression <code>expr</code> to buffer <code>id</code>.</dd>

	<dt>buffer_set_literal</dt>
	<dd>Store given string literal <code>value</code> into buffer <code>id</code>.</dd>

	<dt>buffer_set_xpath</dt>
	<dd>Store the result of XPath expression <code>expr</code> into buffer <code>id</code>.</dd>

	<dt>conditional_choose</dt>
	<dd></dd>

	<dt>conditional_match</dt>
	<dd><code>expr</code></dd>

	<dt>condition_default</dt>
	<dd></dd>

	<dt>condition_match</dt>
	<dd><code>value</code></dd>

	<dt>condition_when</dt>
	<dd><code>test</code></dd>

	<dt>output_buffer</dt>
	<dd>Output the content of buffer <code>id</code> escaped using <code>escape</code> strategy.</dd>

	<dt>output_literal</dt>
	<dd>Output given string literal <code>value</code>.</dd>

	<dt>output_xpath</dt>
	<dd>Output the result of XPath expression <code>expr</code> escaped using <code>escape</code> strategy.</dd>
</dl>