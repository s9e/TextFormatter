matches.forEach((m) =>
{
	addTagPair(
		config.tagName,
		m[0][1],
		1,
		m[0][1] + m[0][0].length,
		0
	);
});