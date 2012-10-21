function(str)
{
	// NOTE: this will not correctly transform \0 into a NULL byte. I consider this a feature
	//       rather than a bug. There's no reason to use NULL bytes in a text.
	return str.replace(/\\([\s\S]?)/g, '\\1');
}