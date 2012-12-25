/**
* IMPORTANT NOTE: those filters are only meant to catch bad input and honest mistakes. They don't
*                 match their PHP equivalent exactly and may let unwanted values through. Their
*                 result should always be checked by PHP filters
*/
var BuiltInFilters =
{
	filterColor: function(attrValue)
	{
		return /^(?:#[0-9a-f]{3,6}|[a-z]+)$/i.test(attrValue) ? attrValue : false;
	},

	filterEmail: function(attrValue)
	{
		return /^[-\w.+]+@[-\w.]+$/.test(attrValue) ? attrValue : false;
	},

	filterFloat: function(attrValue)
	{
		return /^(?:0|-?[1-9]\d*)(?:\.\d+)?(?:e[1-9]\d*)?$/i.test(attrValue) ? attrValue : false;
	}

	filterIdentifier: function(attrValue)
	{
		return /^[-\w]+$/.test(attrValue) ? attrValue : false;
	}

	filterIp: function(attrValue)
	{
		if (/^[\d.]+$/.test(attrValue))
		{
			return BuiltInFilters.filterIpv4(attrValue);
		}

		if (/^[\da-f:]+$/i.test(attrValue))
		{
			return BuiltInFilters.filterIpv6(attrValue);
		}

		return false;
	}

	filterIpport: function(attrValue)
	{
		var m, ip;

		if (m = /^\[([\da-f:]+)(\]:[1-9]\d*)$/i.exec(attrValue))
		{
			ip = BuiltInFilters.filterIpv6(m[1]);

			if (ip === false)
			{
				return false;
			}

			return '[' + ip + m[2];
		}

		if (m = /^([\d.]+)(:[1-9]\d*)$/.exec(attrValue))
		{
			ip = BuiltInFilters.filterIpv4(m[1]);

			if (ip === false)
			{
				return false;
			}

			return ip + m[2];
		}

		return false;
	}

	filterIpv4: function(attrValue)
	{
		if (/^\d+\.\d+\.\d+\.\d+$/.test(attrValue))
		{
			return false;
		}

		var i = 4, p = attrValue.split('.');
		while (--i >= 0)
		{
			// NOTE: ext/filter doesn't support octal notation
			if (p[i].charAt(0) === '0' || p[i] > 255)
			{
				return false;
			}
		}

		return true;
	}

	filterIpv6: function(attrValue)
	{
		return /^(\d*:){2,7}\d+(?:\.\d+\.\d+\.\d+)?$/.test(attrValue) ? attrValue : false;
	}

	filterMap: function(attrValue, map)
	{
		var i = -1, cnt = map.length;
		while (++i < cnt)
		{
			/** @todo the map should be converted to [/regexp/,replacement] */
			if (map[i][0].test(attrValue))
			{
				return map[i][1];
			}
		}

		return attrValue;
	}

	filterInt: function(attrValue)
	{
		return /^(?:0|-?[1-9]\d*)$/.test(attrValue) ? attrValue : false;
	}

	filterNumber: function(attrValue)
	{
		return /^\d+$/.test(attrValue) ? attrValue : false;
	}

	filterRange: function(attrValue, min, max, logger)
	{
		if (!/^(?:0|-?[1-9]\d*)$/.test(attrValue))
		{
			return false;
		}

		if (attrValue < min)
		{
			logger.warn(
				'Value outside of range, adjusted up to min value',
				{
					'attrValue' : attrValue,
					'min'       : min,
					'max'       : max
				}
			);

			return min;
		}

		if (attrValue > max)
		{
			logger.warn(
				'Value outside of range, adjusted down to max value',
				{
					'attrValue' : attrValue,
					'min'       : min,
					'max'       : max
				}
			);

			return max;
		}

		return attrValue;
	}

	/** @todo convert regexp */
	filterRegexp: function(attrValue, regexp)
	{
		return regexp.test(attrValue) ? attrValue : false;
	}

	filterSimpletext: function(attrValue)
	{
		return /^[-\w+., ]+$/.test(attrValue) ? attrValue : false;
	}

	filterUint: function(attrValue)
	{
		return /^(?:0|[1-9]\d*)$/.test(attrValue) ? attrValue : false;
	}

	filterUrl: function(attrValue, urlConfig, logger)
	{
		/** @todo */
		return attrValue;
	}
}