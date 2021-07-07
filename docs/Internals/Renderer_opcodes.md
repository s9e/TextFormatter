<code>escape<code> values:

 - <code>attribute<code> escape `&` `<` `>` `"`
 - <code>comment<code> ?
 - <code>raw<code> no escaping
 - <code>text<code> escape `&` `<` `>`
 - <code>???<code> remove tags then unescape entities(?)
 - <code>url<code> ?

Escaping methods can be chained via buffer copy.

TODO: add output_method=(text|html) to buffer_transform.

<dl>
	<dt>buffer_append_buffer</dt>
	<dd>Append the content of buffer <code>source</code> to buffer <code>id</code>, escaped using the <code>escape</code> method.</dd>

	<dt>buffer_append_literal</dt>
	<dd>Append string literal <code>value</code> to buffer <code>id</code>.</dd>

	<dt>buffer_append_transform</dt>
	<dd>Recursively process all child nodes, or nodes selected by optional XPath expression <code>select<code> and append the result to buffer <code>id</code>, escaped using the <code>escape</code> method.</dd>

	<dt>buffer_append_xpath</dt>
	<dd>Append the result of XPath expression <code>select</code> to buffer <code>id</code>, escaped using the <code>escape</code> method.</dd>

	<dt>buffer_set_literal</dt>
	<dd>Set the value of buffer <code>id</code> to string literal <code>value</code>.</dd>

	<dt>conditional_match</dt>
	<dd>Match each condition of this block against the string value of XPath expression <code>select</code>.</dd>

	<dt>conditional_test</dt>
	<dd></dd>

	<dt>condition_match_literal</dt>
	<dd>Match literal string <code>value</code> against this block's value.</dd>

	<dt>condition_match_default</dt>
	<dd></dd>

	<dt>condition_test_default</dt>
	<dd></dd>

	<dt>condition_test_xpath</dt>
	<dd><code>test</code></dd>
</dl>