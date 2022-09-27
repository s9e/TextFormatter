function parse()
{
	let pos = text.indexOf("  \n");
	while (pos > 0)
	{
		addBrTag(pos + 2).cascadeInvalidationTo(
			addVerbatim(pos + 2, 1)
		);
		pos = text.indexOf("  \n", pos + 3);
	}
}