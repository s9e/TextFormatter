function(str)
{
	return str.replace(/["'\\]/g, '\\$&').replace(/\0/g, '\\0');
}