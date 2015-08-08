matches.forEach(function(m)
{
	var tag = addVerbatim(m[0][1] + 1, m[0][0].length - 1);
	tag.setFlags(0);
	tag.setSortPriority(-1000);

	addIgnoreTag(m[0][1], 1).cascadeInvalidationTo(tag);
});