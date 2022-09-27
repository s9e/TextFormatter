const MapFilter =
{
	/**
	* @param  {*} attrValue
	* @param  {!Array.<!Array>}  map
	* @return {*}
	*/
	filter: function(attrValue, map)
	{
		let i = -1, cnt = map.length;
		while (++i < cnt)
		{
			if (map[i][0].test(attrValue))
			{
				return map[i][1];
			}
		}

		return attrValue;
	}
};