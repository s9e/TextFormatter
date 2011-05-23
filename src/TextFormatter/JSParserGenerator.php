<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter;

use RuntimeException;

class JSParserGenerator
{
	/**
	* @var ConfigBuilder
	*/
	protected $cb;

	/**
	* @var string Template source
	*/
	protected $tpl;

	/**
	* @var string Source being generated
	*/
	protected $src;

	/**
	* @var array Tags' config
	*/
	protected $tagsConfig;

	/**
	* List of Javascript reserved words
	*
	* @link https://developer.mozilla.org/en/JavaScript/Reference/Reserved_Words
	*
	* Also, Closure Compiler doesn't like "char"
	*/
	const RESERVED_WORDS_REGEXP =
		'#^(?:break|case|catch|continue|debugger|default|delete|do|else|finally|for|function|if|in|instanceof|new|return|switch|this|throw|try|typeof|var|void|while|with|class|enum|export|extends|import|super|implements|interface|let|package|private|protected|public|static|yield|char)$#D';

	/**
	* Ranges to be used in Javascript regexps instead of PCRE's Unicode properties
	*/
	static public $unicodeProps = array(
		'L' => 'A-Za-z\\u00AA\\u00B5\\u00BA\\u00C0-\\u00D6\\u00D8-\\u00F6\\u00F8-\\u02C1\\u02C6-\\u02D1\\u02E0-\\u02E4\\u02EC\\u02EE\\u0370-\\u0374\\u0376\\u0377\\u037A-\\u037D\\u0386\\u0388-\\u038A\\u038C\\u038E-\\u03A1\\u03A3-\\u03F5\\u03F7-\\u0481\\u048A-\\u0527\\u0531-\\u0556\\u0559\\u0561-\\u0587\\u05D0-\\u05EA\\u05F0-\\u05F2\\u0620-\\u064A\\u066E\\u066F\\u0671-\\u06D3\\u06D5\\u06E5\\u06E6\\u06EE\\u06EF\\u06FA-\\u06FC\\u06FF\\u0710\\u0712-\\u072F\\u074D-\\u07A5\\u07B1\\u07CA-\\u07EA\\u07F4\\u07F5\\u07FA\\u0800-\\u0815\\u081A\\u0824\\u0828\\u0840-\\u0858\\u0904-\\u0939\\u093D\\u0950\\u0958-\\u0961\\u0971-\\u0977\\u0979-\\u097F\\u0985-\\u098C\\u098F\\u0990\\u0993-\\u09A8\\u09AA-\\u09B0\\u09B2\\u09B6-\\u09B9\\u09BD\\u09CE\\u09DC\\u09DD\\u09DF-\\u09E1\\u09F0\\u09F1\\u0A05-\\u0A0A\\u0A0F\\u0A10\\u0A13-\\u0A28\\u0A2A-\\u0A30\\u0A32\\u0A33\\u0A35\\u0A36\\u0A38\\u0A39\\u0A59-\\u0A5C\\u0A5E\\u0A72-\\u0A74\\u0A85-\\u0A8D\\u0A8F-\\u0A91\\u0A93-\\u0AA8\\u0AAA-\\u0AB0\\u0AB2\\u0AB3\\u0AB5-\\u0AB9\\u0ABD\\u0AD0\\u0AE0\\u0AE1\\u0B05-\\u0B0C\\u0B0F\\u0B10\\u0B13-\\u0B28\\u0B2A-\\u0B30\\u0B32\\u0B33\\u0B35-\\u0B39\\u0B3D\\u0B5C\\u0B5D\\u0B5F-\\u0B61\\u0B71\\u0B83\\u0B85-\\u0B8A\\u0B8E-\\u0B90\\u0B92-\\u0B95\\u0B99\\u0B9A\\u0B9C\\u0B9E\\u0B9F\\u0BA3\\u0BA4\\u0BA8-\\u0BAA\\u0BAE-\\u0BB9\\u0BD0\\u0C05-\\u0C0C\\u0C0E-\\u0C10\\u0C12-\\u0C28\\u0C2A-\\u0C33\\u0C35-\\u0C39\\u0C3D\\u0C58\\u0C59\\u0C60\\u0C61\\u0C85-\\u0C8C\\u0C8E-\\u0C90\\u0C92-\\u0CA8\\u0CAA-\\u0CB3\\u0CB5-\\u0CB9\\u0CBD\\u0CDE\\u0CE0\\u0CE1\\u0CF1\\u0CF2\\u0D05-\\u0D0C\\u0D0E-\\u0D10\\u0D12-\\u0D3A\\u0D3D\\u0D4E\\u0D60\\u0D61\\u0D7A-\\u0D7F\\u0D85-\\u0D96\\u0D9A-\\u0DB1\\u0DB3-\\u0DBB\\u0DBD\\u0DC0-\\u0DC6\\u0E01-\\u0E30\\u0E32\\u0E33\\u0E40-\\u0E46\\u0E81\\u0E82\\u0E84\\u0E87\\u0E88\\u0E8A\\u0E8D\\u0E94-\\u0E97\\u0E99-\\u0E9F\\u0EA1-\\u0EA3\\u0EA5\\u0EA7\\u0EAA\\u0EAB\\u0EAD-\\u0EB0\\u0EB2\\u0EB3\\u0EBD\\u0EC0-\\u0EC4\\u0EC6\\u0EDC\\u0EDD\\u0F00\\u0F40-\\u0F47\\u0F49-\\u0F6C\\u0F88-\\u0F8C\\u1000-\\u102A\\u103F\\u105A-\\u105D\\u106E-\\u1070\\u108E\\u10A0-\\u10C5\\u10D0-\\u10FA\\u10FC\\u115F\\u1160\\u124A-\\u124D\\u125A-\\u125D\\u128A-\\u128D\\u1290-\\u12B0\\u12B2-\\u12B5\\u12B8-\\u12BE\\u12C0\\u12C2-\\u12C5\\u12C8-\\u12D6\\u12D8-\\u1310\\u1318-\\u135A\\u1380-\\u138F\\u13A0-\\u13F4\\u1401-\\u166C\\u166F-\\u167F\\u1681-\\u169A\\u16A0-\\u16EA\\u1700-\\u170C\\u170E-\\u1711\\u1760-\\u176C\\u176E-\\u1770\\u1780-\\u17B3\\u17D7\\u17DC\\u1880-\\u18A8\\u18AA\\u18B0-\\u18F5\\u1900-\\u191C\\u1950-\\u196D\\u1980-\\u19AB\\u19C1-\\u19C7\\u1A00-\\u1A16\\u1A20-\\u1A54\\u1AA7\\u1B05-\\u1B33\\u1B45-\\u1B4B\\u1B83-\\u1BA0\\u1BAE\\u1BAF\\u1BC0-\\u1BE5\\u1C00-\\u1C23\\u1C4D-\\u1C4F\\u1C5A-\\u1C7D\\u1CE9-\\u1CEC\\u1CEE-\\u1CF1\\u1D00-\\u1DBF\\u1E00-\\u1F15\\u1F18-\\u1F1D\\u1F20-\\u1F45\\u1F48-\\u1F4D\\u1F50-\\u1F57\\u1F59\\u1F5B\\u1F5D\\u1F5F-\\u1F7D\\u1F80-\\u1FB4\\u1FB6-\\u1FBC\\u1FBE\\u1FC2-\\u1FC4\\u1FC6-\\u1FCC\\u1FD0-\\u1FD3\\u1FD6-\\u1FDB\\u1FE0-\\u1FEC\\u1FF2-\\u1FF4\\u1FF6-\\u1FFC\\u207F\\u2090-\\u209C\\u210A-\\u2113\\u2119-\\u211D\\u212A-\\u212D\\u212F-\\u2134\\u213C-\\u213F\\u214E\\u2C00-\\u2C2E\\u2C30-\\u2C5E\\u2C60-\\u2CE4\\u2CEB-\\u2CEE\\u2D00-\\u2D25\\u2D30-\\u2D65\\u2D6F\\u2D80-\\u2D96\\u2DA0-\\u2DA6\\u2DA8-\\u2DAE\\u2DB0-\\u2DB6\\u2DB8-\\u2DBE\\u2DC0-\\u2DC6\\u2DC8-\\u2DCE\\u2DD0-\\u2DD6\\u2DD8-\\u2DDE\\u2E2F\\u303B\\u303C\\u309D-\\u309F\\u30A1-\\u30FA\\u30FC-\\u30FF\\u3105-\\u312D\\u3131-\\u318E\\u31A0-\\u31BA\\u31F0-\\u31FF\\u3400-\\u4DB5\\u4E00-\\u9FCB\\uA000-\\uA48C\\uA4D0-\\uA4FD\\uA500-\\uA60C\\uA610-\\uA61F\\uA62A\\uA62B\\uA640-\\uA66E\\uA67F-\\uA697\\uA6A0-\\uA6E5\\uA717-\\uA71F\\uA722-\\uA788\\uA78B-\\uA78E\\uA790\\uA791\\uA7A0-\\uA7A9\\uA7FA-\\uA801\\uA803-\\uA805\\uA807-\\uA80A\\uA80C-\\uA822\\uA840-\\uA873\\uA882-\\uA8B3\\uA8F2-\\uA8F7\\uA8FB\\uA90A-\\uA925\\uA930-\\uA946\\uA960-\\uA97C\\uA984-\\uA9B2\\uA9CF\\uAA00-\\uAA28\\uAA40-\\uAA42\\uAA44-\\uAA4B\\uAA60-\\uAA76\\uAA7A\\uAA80-\\uAAAF\\uAAB1\\uAAB5\\uAAB6\\uAAB9-\\uAABD\\uAAC0\\uAAC2\\uAADB-\\uAADD\\uAB01-\\uAB06\\uAB09-\\uAB0E\\uAB11-\\uAB16\\uAB20-\\uAB26\\uAB28-\\uAB2E\\uABC0-\\uABE2\\uAC00-\\uD7A3\\uD7B0-\\uD7C6\\uD7CB-\\uD7FB\\uF900-\\uFA2D\\uFA30-\\uFA6D\\uFA70-\\uFAD9\\uFB00-\\uFB06\\uFB13-\\uFB17\\uFB1D\\uFB1F-\\uFB28\\uFB2A-\\uFB36\\uFB38-\\uFB3C\\uFB3E\\uFB40\\uFB41\\uFB43\\uFB44\\uFB46-\\uFBB1\\uFBD3-\\uFD3D\\uFD50-\\uFD8F\\uFD92-\\uFDC7\\uFDF0-\\uFDFB\\uFE70-\\uFE74\\uFE76-\\uFEFC\\uFF21-\\uFF3A\\uFF41-\\uFF5A\\uFF66-\\uFFBE\\uFFC2-\\uFFC7\\uFFCA-\\uFFCF\\uFFD2-\\uFFD7\\uFFDA-\\uFFDC',
		'L&' => 'A-Za-z\\u00AA\\u00B5\\u00BA\\u00C0-\\u00D6\\u00D8-\\u00F6\\u00F8-\\u01BA\\u01BC-\\u01BF\\u01C4-\\u0293\\u0295-\\u02AF\\u0370-\\u0373\\u0376\\u0377\\u037B-\\u037D\\u0386\\u0388-\\u038A\\u038C\\u038E-\\u03A1\\u03A3-\\u03F5\\u03F7-\\u0481\\u048A-\\u0527\\u0531-\\u0556\\u0561-\\u0587\\u10A0-\\u10C5\\u1D00-\\u1D2B\\u1D62-\\u1D77\\u1D79-\\u1D9A\\u1E00-\\u1F15\\u1F18-\\u1F1D\\u1F20-\\u1F45\\u1F48-\\u1F4D\\u1F50-\\u1F57\\u1F59\\u1F5B\\u1F5D\\u1F5F-\\u1F7D\\u1F80-\\u1FB4\\u1FB6-\\u1FBC\\u1FBE\\u1FC2-\\u1FC4\\u1FC6-\\u1FCC\\u1FD0-\\u1FD3\\u1FD6-\\u1FDB\\u1FE0-\\u1FEC\\u1FF2-\\u1FF4\\u1FF6-\\u1FFC\\u210A-\\u2113\\u2119-\\u211D\\u212A-\\u212D\\u212F-\\u2134\\u213C-\\u213F\\u214E\\u2C00-\\u2C2E\\u2C30-\\u2C5E\\u2C60-\\u2C7C\\u2C7E-\\u2CE4\\u2CEB-\\u2CEE\\u2D00-\\u2D25\\uA640-\\uA66D\\uA680-\\uA697\\uA722-\\uA76F\\uA771-\\uA787\\uA78B-\\uA78E\\uA790\\uA791\\uA7A0-\\uA7A9\\uA7FA\\uFB00-\\uFB06\\uFB13-\\uFB17\\uFF21-\\uFF3A\\uFF41-\\uFF5A',
		'Lm' => '\\u02B0-\\u02C1\\u02C6-\\u02D1\\u02E0-\\u02E4\\u02EC\\u02EE\\u0374\\u037A\\u0559\\u0640\\u07F4\\u07F5\\u07FA\\u081A\\u0824\\u0828\\u0971\\u0E46\\u0EC6\\u10FC\\u17D7\\u1AA7\\u1C78-\\u1C7D\\u1D2C-\\u1D61\\u1D78\\u1D9B-\\u1DBF\\u207F\\u2090-\\u209C\\u2C7D\\u2D6F\\u2E2F\\u303B\\u309D\\u309E\\u30FC-\\u30FE\\uA015\\uA4F8-\\uA4FD\\uA60C\\uA67F\\uA717-\\uA71F\\uA770\\uA788\\uA9CF\\uAA70\\uAADD\\uFF70\\uFF9E\\uFF9F',
		'Lo' => '\\u01BB\\u01C0-\\u01C3\\u0294\\u05D0-\\u05EA\\u05F0-\\u05F2\\u0620-\\u063F\\u0641-\\u064A\\u066E\\u066F\\u0671-\\u06D3\\u06D5\\u06EE\\u06EF\\u06FA-\\u06FC\\u06FF\\u0710\\u0712-\\u072F\\u074D-\\u07A5\\u07B1\\u07CA-\\u07EA\\u0800-\\u0815\\u0840-\\u0858\\u0904-\\u0939\\u093D\\u0950\\u0958-\\u0961\\u0972-\\u0977\\u0979-\\u097F\\u0985-\\u098C\\u098F\\u0990\\u0993-\\u09A8\\u09AA-\\u09B0\\u09B2\\u09B6-\\u09B9\\u09BD\\u09CE\\u09DC\\u09DD\\u09DF-\\u09E1\\u09F0\\u09F1\\u0A05-\\u0A0A\\u0A0F\\u0A10\\u0A13-\\u0A28\\u0A2A-\\u0A30\\u0A32\\u0A33\\u0A35\\u0A36\\u0A38\\u0A39\\u0A59-\\u0A5C\\u0A5E\\u0A72-\\u0A74\\u0A85-\\u0A8D\\u0A8F-\\u0A91\\u0A93-\\u0AA8\\u0AAA-\\u0AB0\\u0AB2\\u0AB3\\u0AB5-\\u0AB9\\u0ABD\\u0AD0\\u0AE0\\u0AE1\\u0B05-\\u0B0C\\u0B0F\\u0B10\\u0B13-\\u0B28\\u0B2A-\\u0B30\\u0B32\\u0B33\\u0B35-\\u0B39\\u0B3D\\u0B5C\\u0B5D\\u0B5F-\\u0B61\\u0B71\\u0B83\\u0B85-\\u0B8A\\u0B8E-\\u0B90\\u0B92-\\u0B95\\u0B99\\u0B9A\\u0B9C\\u0B9E\\u0B9F\\u0BA3\\u0BA4\\u0BA8-\\u0BAA\\u0BAE-\\u0BB9\\u0BD0\\u0C05-\\u0C0C\\u0C0E-\\u0C10\\u0C12-\\u0C28\\u0C2A-\\u0C33\\u0C35-\\u0C39\\u0C3D\\u0C58\\u0C59\\u0C60\\u0C61\\u0C85-\\u0C8C\\u0C8E-\\u0C90\\u0C92-\\u0CA8\\u0CAA-\\u0CB3\\u0CB5-\\u0CB9\\u0CBD\\u0CDE\\u0CE0\\u0CE1\\u0CF1\\u0CF2\\u0D05-\\u0D0C\\u0D0E-\\u0D10\\u0D12-\\u0D3A\\u0D3D\\u0D4E\\u0D60\\u0D61\\u0D7A-\\u0D7F\\u0D85-\\u0D96\\u0D9A-\\u0DB1\\u0DB3-\\u0DBB\\u0DBD\\u0DC0-\\u0DC6\\u0E01-\\u0E30\\u0E32\\u0E33\\u0E40-\\u0E45\\u0E81\\u0E82\\u0E84\\u0E87\\u0E88\\u0E8A\\u0E8D\\u0E94-\\u0E97\\u0E99-\\u0E9F\\u0EA1-\\u0EA3\\u0EA5\\u0EA7\\u0EAA\\u0EAB\\u0EAD-\\u0EB0\\u0EB2\\u0EB3\\u0EBD\\u0EC0-\\u0EC4\\u0EDC\\u0EDD\\u0F00\\u0F40-\\u0F47\\u0F49-\\u0F6C\\u0F88-\\u0F8C\\u1000-\\u102A\\u103F\\u105A-\\u105D\\u106E-\\u1070\\u108E\\u10D0-\\u10FA\\u115F\\u1160\\u124A-\\u124D\\u125A-\\u125D\\u128A-\\u128D\\u1290-\\u12B0\\u12B2-\\u12B5\\u12B8-\\u12BE\\u12C0\\u12C2-\\u12C5\\u12C8-\\u12D6\\u12D8-\\u1310\\u1318-\\u135A\\u1380-\\u138F\\u13A0-\\u13F4\\u1401-\\u166C\\u166F-\\u167F\\u1681-\\u169A\\u16A0-\\u16EA\\u1700-\\u170C\\u170E-\\u1711\\u1760-\\u176C\\u176E-\\u1770\\u1780-\\u17B3\\u17DC\\u1880-\\u18A8\\u18AA\\u18B0-\\u18F5\\u1900-\\u191C\\u1950-\\u196D\\u1980-\\u19AB\\u19C1-\\u19C7\\u1A00-\\u1A16\\u1A20-\\u1A54\\u1B05-\\u1B33\\u1B45-\\u1B4B\\u1B83-\\u1BA0\\u1BAE\\u1BAF\\u1BC0-\\u1BE5\\u1C00-\\u1C23\\u1C4D-\\u1C4F\\u1C5A-\\u1C77\\u1CE9-\\u1CEC\\u1CEE-\\u1CF1\\u2D30-\\u2D65\\u2D80-\\u2D96\\u2DA0-\\u2DA6\\u2DA8-\\u2DAE\\u2DB0-\\u2DB6\\u2DB8-\\u2DBE\\u2DC0-\\u2DC6\\u2DC8-\\u2DCE\\u2DD0-\\u2DD6\\u2DD8-\\u2DDE\\u303C\\u309F\\u30A1-\\u30FA\\u30FF\\u3105-\\u312D\\u3131-\\u318E\\u31A0-\\u31BA\\u31F0-\\u31FF\\u3400-\\u4DB5\\u4E00-\\u9FCB\\uA000-\\uA014\\uA016-\\uA48C\\uA4D0-\\uA4F7\\uA500-\\uA60B\\uA610-\\uA61F\\uA62A\\uA62B\\uA66E\\uA6A0-\\uA6E5\\uA7FB-\\uA801\\uA803-\\uA805\\uA807-\\uA80A\\uA80C-\\uA822\\uA840-\\uA873\\uA882-\\uA8B3\\uA8F2-\\uA8F7\\uA8FB\\uA90A-\\uA925\\uA930-\\uA946\\uA960-\\uA97C\\uA984-\\uA9B2\\uAA00-\\uAA28\\uAA40-\\uAA42\\uAA44-\\uAA4B\\uAA60-\\uAA6F\\uAA71-\\uAA76\\uAA7A\\uAA80-\\uAAAF\\uAAB1\\uAAB5\\uAAB6\\uAAB9-\\uAABD\\uAAC0\\uAAC2\\uAADB\\uAADC\\uAB01-\\uAB06\\uAB09-\\uAB0E\\uAB11-\\uAB16\\uAB20-\\uAB26\\uAB28-\\uAB2E\\uABC0-\\uABE2\\uAC00-\\uD7A3\\uD7B0-\\uD7C6\\uD7CB-\\uD7FB\\uF900-\\uFA2D\\uFA30-\\uFA6D\\uFA70-\\uFAD9\\uFB1D\\uFB1F-\\uFB28\\uFB2A-\\uFB36\\uFB38-\\uFB3C\\uFB3E\\uFB40\\uFB41\\uFB43\\uFB44\\uFB46-\\uFBB1\\uFBD3-\\uFD3D\\uFD50-\\uFD8F\\uFD92-\\uFDC7\\uFDF0-\\uFDFB\\uFE70-\\uFE74\\uFE76-\\uFEFC\\uFF66-\\uFF6F\\uFF71-\\uFF9D\\uFFA0-\\uFFBE\\uFFC2-\\uFFC7\\uFFCA-\\uFFCF\\uFFD2-\\uFFD7\\uFFDA-\\uFFDC',
		'N' => '0-9\\u00B2\\u00B3\\u00B9\\u00BC-\\u00BE\\u0660-\\u0669\\u06F0-\\u06F9\\u07C0-\\u07C9\\u0966-\\u096F\\u09E6-\\u09EF\\u09F4-\\u09F9\\u0A66-\\u0A6F\\u0AE6-\\u0AEF\\u0B66-\\u0B6F\\u0B72-\\u0B77\\u0BE6-\\u0BF2\\u0C66-\\u0C6F\\u0C78-\\u0C7E\\u0CE6-\\u0CEF\\u0D66-\\u0D75\\u0E50-\\u0E59\\u0ED0-\\u0ED9\\u0F20-\\u0F33\\u1369-\\u137C\\u16EE-\\u16F0\\u17E0-\\u17E9\\u17F0-\\u17F9\\u1946-\\u194F\\u19D0-\\u19DA\\u1A80-\\u1A89\\u1A90-\\u1A99\\u1B50-\\u1B59\\u1BB0-\\u1BB9\\u1C40-\\u1C49\\u1C50-\\u1C59\\u2150-\\u217F\\u2460-\\u249B\\u24EA-\\u24FF\\u2CFD\\u3038-\\u303A\\u3251-\\u325F\\u32B1-\\u32BF\\uA620-\\uA629\\uA6E6-\\uA6EF\\uA830-\\uA835\\uA8D0-\\uA8D9\\uA900-\\uA909\\uA9D0-\\uA9D9\\uAA50-\\uAA59\\uABF0-\\uABF9\\uFF10-\\uFF19',
		'Nd' => '0-9\\u0660-\\u0669\\u06F0-\\u06F9\\u07C0-\\u07C9\\u0966-\\u096F\\u0A66-\\u0A6F\\u0AE6-\\u0AEF\\u0B66-\\u0B6F\\u0BE6-\\u0BEF\\u0C66-\\u0C6F\\u0CE6-\\u0CEF\\u0D66-\\u0D6F\\u0E50-\\u0E59\\u0ED0-\\u0ED9\\u0F20-\\u0F29\\u1946-\\u194F\\u19D0-\\u19D9\\u1A80-\\u1A89\\u1A90-\\u1A99\\u1B50-\\u1B59\\u1BB0-\\u1BB9\\u1C40-\\u1C49\\u1C50-\\u1C59\\uA620-\\uA629\\uA8D0-\\uA8D9\\uA900-\\uA909\\uA9D0-\\uA9D9\\uAA50-\\uAA59\\uABF0-\\uABF9\\uFF10-\\uFF19',
		'Nl' => '\\u16EE-\\u16F0\\u2160-\\u217F\\u3038-\\u303A\\uA6E6-\\uA6EF',
		'No' => '\\u00B2\\u00B3\\u00B9\\u00BC-\\u00BE\\u09F4-\\u09F9\\u0B72-\\u0B77\\u0BF0-\\u0BF2\\u0C78-\\u0C7E\\u0D70-\\u0D75\\u0F2A-\\u0F33\\u1369-\\u137C\\u17F0-\\u17F9\\u19DA\\u2150-\\u215F\\u2460-\\u249B\\u24EA-\\u24FF\\u2CFD\\u3251-\\u325F\\u32B1-\\u32BF\\uA830-\\uA835',
		'P' => '\\!-#%-\\*,-/\\:;\\?@\\[-\\]_\\{\\}\\u00A1\\u00AB\\u00B7\\u00BB\\u00BF\\u0387\\u055A-\\u055F\\u0589\\u058A\\u05BE\\u05C0\\u05C3\\u05C6\\u05F3\\u05F4\\u0609\\u060A\\u060C\\u060D\\u061B\\u061F\\u066A-\\u066D\\u06D4\\u0700-\\u070D\\u07F7-\\u07F9\\u0830-\\u083E\\u0964\\u0965\\u0970\\u0DF4\\u0E4F\\u0E5A\\u0E5B\\u0F04-\\u0F12\\u0F3A-\\u0F3D\\u0F85\\u0FD0-\\u0FD4\\u0FD9\\u0FDA\\u104A-\\u104F\\u10FB\\u166D\\u166E\\u169B\\u169C\\u16EB-\\u16ED\\u17D4-\\u17D6\\u17D8-\\u17DA\\u1807-\\u180A\\u1A1E\\u1A1F\\u1AA0-\\u1AA6\\u1AA8-\\u1AAD\\u1B5A-\\u1B60\\u1BFC-\\u1BFF\\u1C3B-\\u1C3F\\u1C7E\\u1C7F\\u1CD3\\u201A-\\u201D\\u201F\\u203A-\\u2040\\u2055-\\u205E\\u207D\\u208D\\u232A\\u276A-\\u276D\\u276F\\u27C5\\u27C6\\u27EA-\\u27EF\\u298A-\\u298D\\u298F\\u29D8-\\u29DB\\u29FC\\u29FD\\u2CF9-\\u2CFC\\u2CFE\\u2CFF\\u2D70\\u2E00\\u2E01\\u2E0A-\\u2E16\\u2E1A-\\u2E1F\\u2E2A-\\u2E2E\\u300A-\\u300D\\u300F\\u301A-\\u301D\\u303D\\u30A0\\u30FB\\uA4FE\\uA4FF\\uA60D-\\uA60F\\uA673\\uA67E\\uA6F2-\\uA6F7\\uA874-\\uA877\\uA8CE\\uA8CF\\uA8F8-\\uA8FA\\uA92E\\uA92F\\uA95F\\uA9C1-\\uA9CD\\uA9DE\\uA9DF\\uAA5C-\\uAA5F\\uAADE\\uAADF\\uABEB\\uFD3E\\uFD3F\\uFE10-\\uFE19\\uFE30-\\uFE52\\uFE54-\\uFE61\\uFE63\\uFE68\\uFE6A\\uFE6B\\uFF01-\\uFF03\\uFF05-\\uFF0A\\uFF0C-\\uFF0F\\uFF1A\\uFF1B\\uFF1F\\uFF20\\uFF3B-\\uFF3D\\uFF3F\\uFF5B\\uFF5D\\uFF5F-\\uFF65',
		'Pc' => '_\\u203F\\u2040\\uFE33\\uFE34\\uFE4D-\\uFE4F\\uFF3F',
		'Pd' => '\\-\\u058A\\u05BE\\u2E17\\u2E1A\\u301C\\u30A0\\uFE31\\uFE32\\uFE58\\uFE63\\uFF0D',
		'Pe' => '\\)\\]\\}\\u0F3B\\u0F3D\\u169C\\u207E\\u208E\\u232A\\u276B\\u276D\\u276F\\u27C6\\u27EB\\u27ED\\u27EF\\u298A\\u298C\\u29D9\\u29DB\\u29FD\\u300B\\u300D\\u300F\\u301B\\uFD3F\\uFE18\\uFE36\\uFE38\\uFE3A\\uFE3C\\uFE3E\\uFE40\\uFE42\\uFE44\\uFE48\\uFE5A\\uFE5C\\uFE5E\\uFF09\\uFF3D\\uFF5D\\uFF60\\uFF63',
		'Pf' => '\\u00BB\\u201D\\u203A\\u2E03\\u2E05\\u2E0A\\u2E0D\\u2E1D\\u2E21',
		'Pi' => '\\u00AB\\u201B\\u201C\\u201F\\u2039\\u2E02\\u2E04\\u2E09\\u2E0C\\u2E1C\\u2E20',
		'Po' => '\\!-#%-\'\\*,\\./\\:;\\?@\\\\\\u00A1\\u00B7\\u00BF\\u037E\\u0387\\u055A-\\u055F\\u0589\\u05C0\\u05C3\\u05C6\\u05F3\\u05F4\\u0609\\u060A\\u060C\\u060D\\u061B\\u061E\\u061F\\u066A-\\u066D\\u06D4\\u0700-\\u070D\\u07F7-\\u07F9\\u0830-\\u083E\\u085E\\u0964\\u0965\\u0970\\u0DF4\\u0E4F\\u0E5A\\u0E5B\\u0F04-\\u0F12\\u0F85\\u0FD0-\\u0FD4\\u0FD9\\u0FDA\\u104A-\\u104F\\u10FB\\u166D\\u166E\\u16EB-\\u16ED\\u17D4-\\u17D6\\u17D8-\\u17DA\\u1807-\\u180A\\u1A1E\\u1A1F\\u1AA0-\\u1AA6\\u1AA8-\\u1AAD\\u1B5A-\\u1B60\\u1BFC-\\u1BFF\\u1C3B-\\u1C3F\\u1C7E\\u1C7F\\u1CD3\\u203B-\\u203E\\u2055-\\u205E\\u2CF9-\\u2CFC\\u2CFE\\u2CFF\\u2D70\\u2E00\\u2E01\\u2E06-\\u2E08\\u2E0B\\u2E0E-\\u2E16\\u2E18\\u2E19\\u2E1B\\u2E1E\\u2E1F\\u2E2A-\\u2E2E\\u2E30\\u2E31\\u303D\\u30FB\\uA4FE\\uA4FF\\uA60D-\\uA60F\\uA673\\uA67E\\uA6F2-\\uA6F7\\uA874-\\uA877\\uA8CE\\uA8CF\\uA8F8-\\uA8FA\\uA92E\\uA92F\\uA95F\\uA9C1-\\uA9CD\\uA9DE\\uA9DF\\uAA5C-\\uAA5F\\uAADE\\uAADF\\uABEB\\uFE10-\\uFE16\\uFE19\\uFE30\\uFE45\\uFE46\\uFE49-\\uFE4C\\uFE50-\\uFE52\\uFE54-\\uFE57\\uFE5F-\\uFE61\\uFE68\\uFE6A\\uFE6B\\uFF01-\\uFF03\\uFF05-\\uFF07\\uFF0A\\uFF0C\\uFF0E\\uFF0F\\uFF1A\\uFF1B\\uFF1F\\uFF20\\uFF3C\\uFF61\\uFF64\\uFF65',
		'Ps' => '\\(\\[\\{\\u0F3A\\u0F3C\\u169B\\u201A\\u207D\\u208D\\u276A\\u276C\\u27C5\\u27EA\\u27EC\\u27EE\\u298B\\u298D\\u298F\\u29D8\\u29DA\\u29FC\\u300A\\u300C\\u301A\\u301D\\uFD3E\\uFE17\\uFE35\\uFE37\\uFE39\\uFE3B\\uFE3D\\uFE3F\\uFE41\\uFE43\\uFE47\\uFE59\\uFE5B\\uFE5D\\uFF08\\uFF3B\\uFF5B\\uFF5F\\uFF62',
		'S' => '\\$\\+\\<-\\>\\^`\\|~\\u00A2-\\u00A9\\u00AC\\u00AE-\\u00B1\\u00B4\\u00B6\\u00B8\\u00D7\\u00F7\\u02C2-\\u02C5\\u02D2-\\u02DF\\u02E5-\\u02EB\\u02ED\\u02EF-\\u02FF\\u0375\\u0384\\u0385\\u03F6\\u0482\\u0606-\\u0608\\u060B\\u060E\\u060F\\u06DE\\u06E9\\u06FD\\u06FE\\u07F6\\u09F2\\u09F3\\u09FA\\u09FB\\u0AF1\\u0B70\\u0BF3-\\u0BFA\\u0C7F\\u0D79\\u0E3F\\u0F01-\\u0F03\\u0F13-\\u0F17\\u0F1A-\\u0F1F\\u0F34\\u0F36\\u0F38\\u0FBE-\\u0FC5\\u0FC7-\\u0FCC\\u0FCE\\u0FCF\\u0FD5-\\u0FD8\\u109E\\u109F\\u17DB\\u19DE-\\u19FF\\u1B61-\\u1B6A\\u1B74-\\u1B7C\\u1FBD\\u1FBF-\\u1FC1\\u1FCD-\\u1FCF\\u1FDD-\\u1FDF\\u1FED-\\u1FEF\\u1FFD\\u1FFE\\u207A-\\u207C\\u208A-\\u208C\\u20A0-\\u20B9\\u211E-\\u2123\\u212E\\u213A\\u213B\\u214A-\\u214D\\u214F\\u219A-\\u22FF\\u2308-\\u231F\\u232B-\\u23F3\\u2440-\\u244A\\u249C-\\u24E9\\u2500-\\u26FF\\u2794-\\u27C4\\u27C7-\\u27CA\\u27CC\\u27CE-\\u27E5\\u27F0-\\u28FF\\u2999-\\u29D7\\u29DC-\\u29FB\\u29FE-\\u2B4C\\u2B50-\\u2B59\\u2CE5-\\u2CEA\\u2F00-\\u2FD5\\u2FF0-\\u2FFB\\u309B\\u309C\\u3196-\\u319F\\u31C0-\\u31E3\\u3200-\\u321E\\u322A-\\u3250\\u3260-\\u327F\\u328A-\\u32B0\\u32C0-\\u32FE\\u3300-\\u33FF\\u4DC0-\\u4DFF\\uA490-\\uA4C6\\uA700-\\uA716\\uA720\\uA721\\uA789\\uA78A\\uA828-\\uA82B\\uA836-\\uA839\\uAA77-\\uAA79\\uFB29\\uFBB2-\\uFBC1\\uFDFC\\uFDFD\\uFE62\\uFE64-\\uFE66\\uFE69\\uFF04\\uFF0B\\uFF1C-\\uFF1E\\uFF3E\\uFF40\\uFF5C\\uFF5E\\uFFE0-\\uFFE6\\uFFE8-\\uFFEE\\uFFFC\\uFFFD',
		'Sc' => '\\$\\u00A2-\\u00A5\\u060B\\u09F2\\u09F3\\u09FB\\u0AF1\\u0BF9\\u0E3F\\u17DB\\u20A0-\\u20B9\\uA838\\uFDFC\\uFE69\\uFF04\\uFFE0\\uFFE1\\uFFE5\\uFFE6',
		'Sk' => '\\^`\\u00A8\\u00AF\\u00B4\\u00B8\\u02C2-\\u02C5\\u02D2-\\u02DF\\u02E5-\\u02EB\\u02ED\\u02EF-\\u02FF\\u0375\\u0384\\u0385\\u1FBD\\u1FBF-\\u1FC1\\u1FCD-\\u1FCF\\u1FDD-\\u1FDF\\u1FED-\\u1FEF\\u1FFD\\u1FFE\\u309B\\u309C\\uA700-\\uA716\\uA720\\uA721\\uA789\\uA78A\\uFBB2-\\uFBC1\\uFF3E\\uFF40\\uFFE3',
		'Sm' => '\\+\\<-\\>\\|~\\u00AC\\u00B1\\u00D7\\u00F7\\u03F6\\u0606-\\u0608\\u207A-\\u207C\\u208A-\\u208C\\u214B\\u219A\\u219B\\u21A0\\u21A3\\u21A6\\u21AE\\u21CE\\u21CF\\u21D2\\u21D4\\u21F4-\\u22FF\\u2308-\\u230B\\u237C\\u239B-\\u23B3\\u23DC-\\u23E1\\u25B7\\u25C1\\u25F8-\\u25FF\\u266F\\u27C0-\\u27C4\\u27C7-\\u27CA\\u27CC\\u27CE-\\u27E5\\u27F0-\\u27FF\\u2999-\\u29D7\\u29DC-\\u29FB\\u29FE-\\u2AFF\\u2B30-\\u2B44\\u2B47-\\u2B4C\\uFB29\\uFE62\\uFE64-\\uFE66\\uFF0B\\uFF1C-\\uFF1E\\uFF5C\\uFF5E\\uFFE2\\uFFE9-\\uFFEC',
		'So' => '\\u00A6\\u00A7\\u00A9\\u00AE\\u00B0\\u00B6\\u0482\\u060E\\u060F\\u06DE\\u06E9\\u06FD\\u06FE\\u07F6\\u09FA\\u0B70\\u0BF3-\\u0BF8\\u0BFA\\u0C7F\\u0D79\\u0F01-\\u0F03\\u0F13-\\u0F17\\u0F1A-\\u0F1F\\u0F34\\u0F36\\u0F38\\u0FBE-\\u0FC5\\u0FC7-\\u0FCC\\u0FCE\\u0FCF\\u0FD5-\\u0FD8\\u109E\\u109F\\u19DE-\\u19FF\\u1B61-\\u1B6A\\u1B74-\\u1B7C\\u211E-\\u2123\\u212E\\u213A\\u213B\\u214A\\u214C\\u214D\\u214F\\u219C-\\u219F\\u21A1\\u21A2\\u21A4\\u21A5\\u21A7-\\u21AD\\u21AF-\\u21CD\\u21D0\\u21D1\\u21D3\\u21D5-\\u21F3\\u230C-\\u231F\\u232B-\\u237B\\u237D-\\u239A\\u23B4-\\u23DB\\u23E2-\\u23F3\\u2440-\\u244A\\u249C-\\u24E9\\u2500-\\u25B6\\u25B8-\\u25C0\\u25C2-\\u25F7\\u2600-\\u266E\\u2670-\\u26FF\\u2794-\\u27BF\\u2800-\\u28FF\\u2B00-\\u2B2F\\u2B45\\u2B46\\u2B50-\\u2B59\\u2CE5-\\u2CEA\\u2F00-\\u2FD5\\u2FF0-\\u2FFB\\u3196-\\u319F\\u31C0-\\u31E3\\u3200-\\u321E\\u322A-\\u3250\\u3260-\\u327F\\u328A-\\u32B0\\u32C0-\\u32FE\\u3300-\\u33FF\\u4DC0-\\u4DFF\\uA490-\\uA4C6\\uA828-\\uA82B\\uA836\\uA837\\uA839\\uAA77-\\uAA79\\uFDFD\\uFFE4\\uFFE8\\uFFED\\uFFEE\\uFFFC\\uFFFD',
		'Z' => ' \\u00A0\\u180E\\u2000-\\u200A\\u202F\\u205F\\u3000',
		'Zl' => '\\u2028',
		'Zp' => '\\u2029',
		'Zs' => ' \\u00A0\\u1680\\u3000'
	);

	/**
	* 
	*
	* @return void
	*/
	public function __construct(ConfigBuilder $cb)
	{
		$this->cb = $cb;

		$this->tpl = file_get_contents(__DIR__ . '/TextFormatter.js');
	}

	/**
	* 
	*
	* @return string
	*/
	public function get(array $options = array())
	{
		$options += array(
			'compilation'        => 'none',
			'disableLogTypes'    => array(),
			'removeDeadCode'     => true,
			'escapeScriptEndTag' => true
		);

		$this->tagsConfig = $this->cb->getTagsConfig(true);
		$this->src = $this->tpl;

		if ($options['removeDeadCode'])
		{
			$this->removeDeadCode();
		}

		$this->injectTagsConfig();
		$this->injectPlugins();
		$this->injectFiltersConfig();
		$this->injectCallbacks();

		/**
		* Turn off logging selectively
		*/
		if ($options['disableLogTypes'])
		{
			$this->src = preg_replace(
				"#\\n\\s*(?=log\\('(?:" . implode('|', $options['disableLogTypes']) . ")',)#",
				'${0}0&&',
				$this->src
			);
		}

		$this->injectXSL();

		if ($options['compilation'] !== 'none')
		{
			$this->compile($options['compilation']);
		}

		if ($options['escapeScriptEndTag'])
		{
			$this->src = preg_replace('#</(script)#i', '<\\/$1', $this->src);
		}

		return $this->src;
	}

	/**
	* Compile/minimize the JS source
	*
	* @param string $level Level to be passed to the Google Closure Compiler service
	*/
	protected function compile($level)
	{
		$content = http_build_query(array(
			'js_code' => $this->src,
			'compilation_level' => $level,
			'output_format' => 'text',
//			'formatting' => 'pretty_print',
			'output_info' => 'compiled_code'
		));

		$this->src = file_get_contents(
			'http://closure-compiler.appspot.com/compile',
			false,
			stream_context_create(array(
				'http' => array(
					'method'  => 'POST',
					'header'  => "Connection: close\r\n"
					           . "Content-length: " . strlen($content) . "\r\n"
					           . "Content-type: application/x-www-form-urlencoded\r\n",
					'content' => $content
				)
			))
		);
	}

	/**
	* Remove JS code that is not going to be used
	*/
	protected function removeDeadCode()
	{
		$this->removeDeadRules();
		$this->removeAttributesProcessing();
		$this->removeDeadFilters();
		$this->removeWhitespaceTrimming();
		$this->removeCompoundAttritutesSplitting();
		$this->removePhaseCallbacksProcessing();
		$this->removeAttributesDefaultValueProcessing();
		$this->removeRequiredAttributesProcessing();
	}

	/**
	* Remove the code related to attributes' default value if no attribute has a default value
	*/
	protected function removeAttributesDefaultValueProcessing()
	{
		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['attrs']))
			{
				foreach ($tagConfig['attrs'] as $attrName => $attrConf)
				{
					if (isset($attrConf['defaultValue']))
					{
						return;
					}
				}
			}
		}

		$this->removeFunctions('addDefaultAttributeValuesToCurrentTag');
	}

	/**
	* Remove the code checks whether all required attributes have been filled in for a tag, if
	* there no required attribute for any tag
	*/
	protected function removeRequiredAttributesProcessing()
	{
		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['attrs']))
			{
				foreach ($tagConfig['attrs'] as $attrName => $attrConf)
				{
					if (!empty($attrConf['isRequired']))
					{
						return;
					}
				}
			}
		}

		$this->removeFunctions('currentTagRequiresMissingAttribute');
	}

	/**
	* Remove the code related to preFilter/postFilter callbacks
	*/
	protected function removePhaseCallbacksProcessing()
	{
		$remove = array(
			'applyTagPreFilterCallbacks' => 1,
			'applyTagPostFilterCallbacks' => 1,
			'applyAttributePreFilterCallbacks' => 1,
			'applyAttributePostFilterCallbacks' => 1
		);

		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['preFilter']))
			{
				unset($remove['applyTagPreFilterCallbacks']);
			}

			if (!empty($tagConfig['postFilter']))
			{
				unset($remove['applyTagPostFilterCallbacks']);
			}

			if (!empty($tagConfig['attrs']))
			{
				foreach ($tagConfig['attrs'] as $attrName => $attrConf)
				{
					if (!empty($attrConf['preFilter']))
					{
						unset($remove['applyAttributePreFilterCallbacks']);
					}

					if (!empty($attrConf['postFilter']))
					{
						unset($remove['applyAttributePostFilterCallbacks']);
					}
				}
			}
		}

		if (count($remove) === 4)
		{
			$remove['applyCallback'] = 1;
		}

		if ($remove)
		{
			$this->removeFunctions(implode('|', array_keys($remove)));
		}
	}

	/**
	* Remove JS code related to rules that are not used
	*/
	protected function removeDeadRules()
	{
		$rules = array(
			'closeParent',
			'closeAscendant',
			'requireParent',
			'requireAscendant'
		);

		$remove = array();

		foreach ($rules as $rule)
		{
			foreach ($this->tagsConfig as $tagConfig)
			{
				if (!empty($tagConfig['rules'][$rule]))
				{
					continue 2;
				}
			}

			$remove[] = $rule;
		}

		if ($remove)
		{
			$this->removeFunctions(implode('|', $remove));
		}
	}

	/**
	* Remove JS code related to attributes if no attributes exist
	*/
	protected function removeAttributesProcessing()
	{
		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['attrs']))
			{
				return;
			}
		}

		$this->removeFunctions('filterAttributes|filter');
	}

	/**
	* Remove JS code related to compound attributes if none exist
	*/
	protected function removeCompoundAttritutesSplitting()
	{
		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['attrs']))
			{
				foreach ($tagConfig['attrs'] as $attrConf)
				{
					if ($attrConf['type'] === 'compound')
					{
						return;
					}
				}
			}
		}

		$this->removeFunctions('splitCompoundAttributes');
	}

	/**
	* Remove JS code related to filters that are not used
	*/
	protected function removeDeadFilters()
	{
		$keepFilters = array();

		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['attrs']))
			{
				foreach ($tagConfig['attrs'] as $attrConf)
				{
					$keepFilters[$attrConf['type']] = 1;
				}
			}
		}
		$keepFilters = array_keys($keepFilters);

		$this->src = preg_replace_callback(
			"#\n\tfunction filter\\(.*?\n\t\}#s",
			function ($functionBlock) use ($keepFilters)
			{
				return preg_replace_callback(
					"#\n\t\t\tcase .*?\n\t\t\t\treturn[^\n]+\n#s",
					function ($caseBlock) use ($keepFilters)
					{
						preg_match_all("#\n\t\t\tcase '([a-z]+)#", $caseBlock[0], $m);

						if (array_intersect($m[1], $keepFilters))
						{
							// at least one of those case can happen, we keep the whole block
							return $caseBlock[0];
						}

						// remove block
						return '';
					},
					$functionBlock[0]
				);
			},
			$this->src
		);
	}

	/**
	* Remove the content of some JS functions from the source
	*
	* @param string  $regexp Regexp used to match the function's name
	*/
	protected function removeFunctions($regexp)
	{
		$this->src = preg_replace(
			'#(\\n\\t+function\\s+(?:' . $regexp . ')\\(.*?\\)(\\n\\t+)\\{).*?(\\2\\})#s',
			'$1$3',
			$this->src
		);
	}

	protected function injectTagsConfig()
	{
		$this->src = str_replace(
			'tagsConfig = {/* DO NOT EDIT*/}',
			'tagsConfig = ' . $this->generateTagsConfig(),
			$this->src
		);
	}

	protected function injectPlugins()
	{
		$pluginParsers = array();
		$pluginsConfig = array();

		foreach ($this->cb->getJSPlugins() as $pluginName => $plugin)
		{
			$js = self::encodeConfig(
				$plugin['config'],
				$plugin['meta']
			);

			$pluginsConfig[] = json_encode($pluginName) . ':' . $js;
			$pluginParsers[] =
				json_encode($pluginName) . ':function(text,matches){/** @const */var config=pluginsConfig["' . $pluginName . '"];' . $plugin['parser'] . '}';
		}

		$this->src = str_replace(
			'pluginsConfig = {/* DO NOT EDIT*/}',
			'pluginsConfig = {' . implode(',', $pluginsConfig) . '}',
			$this->src
		);

		$this->src = str_replace(
			'pluginParsers = {/* DO NOT EDIT*/}',
			'pluginParsers = {' . implode(',', $pluginParsers) . '}',
			$this->src
		);
	}

	protected function injectPluginParsers()
	{
		$this->src = str_replace(
			'pluginParsers = {/* DO NOT EDIT*/}',
			'pluginParsers = {' . $this->generatePluginParsers() . '}',
			$this->src
		);
	}

	protected function injectPluginsConfig()
	{
		$this->src = str_replace(
			'pluginsConfig = {/* DO NOT EDIT*/}',
			'pluginsConfig = {' . $this->generatePluginsConfig() . '}',
			$this->src
		);
	}

	protected function injectFiltersConfig()
	{
		$this->src = str_replace(
			'filtersConfig = {/* DO NOT EDIT*/}',
			'filtersConfig = ' . $this->generateFiltersConfig(),
			$this->src
		);
	}

	protected function injectCallbacks()
	{
		$this->src = str_replace(
			'callbacks = {/* DO NOT EDIT*/}',
			'callbacks = ' . $this->generateCallbacks(),
			$this->src
		);
	}

	protected function generateCallbacks()
	{
		$usedCallbacks = array();

		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['preFilter']))
			{
				foreach ($tagConfig['preFilter'] as $callbackConf)
				{
					$usedCallbacks[] = $callbackConf['callback'];
				}
			}

			if (!empty($tagConfig['postFilter']))
			{
				foreach ($tagConfig['postFilter'] as $callbackConf)
				{
					$usedCallbacks[] = $callbackConf['callback'];
				}
			}

			if (!empty($tagConfig['attrs']))
			{
				foreach ($tagConfig['attrs'] as $attrName => $attrConf)
				{
					if (!empty($attrConf['preFilter']))
					{
						foreach ($attrConf['preFilter'] as $callbackConf)
						{
							$usedCallbacks[] = $callbackConf['callback'];
						}
					}

					if (!empty($attrConf['postFilter']))
					{
						foreach ($attrConf['postFilter'] as $callbackConf)
						{
							$usedCallbacks[] = $callbackConf['callback'];
						}
					}
				}
			}
		}

		$jsCallbacks = array();

		foreach (array_unique($usedCallbacks) as $funcName)
		{
			if (!preg_match('#^[a-z_0-9]+$#Di', $funcName))
			{
				/**
				* This cannot actually happen because callbacks are validated in ConfigBuilder.
				* HOWEVER, if there was a way to get around this validation, this method could be
				* used to get the content of any file in the filesystem, so we're still validating
				* the callback name here as a failsafe.
				*/
				throw new RuntimeException("Invalid callback name '" . $funcName . "'");
			}

			$filepath = __DIR__ . '/jsFunctions/' . $funcName . '.js';
			if (file_exists($filepath))
			{
				$jsCallbacks[] = json_encode($funcName) . ':' . file_get_contents($filepath);
			}
		}

		return '{' . implode(',', $jsCallbacks) . '}';
	}

	/**
	* Kind of hardcoding stuff here, will need to be cleaned up at some point
	*/
	protected function generateFiltersConfig()
	{
		$filtersConfig = $this->cb->getFiltersConfig();

		if (isset($filtersConfig['url']['disallowedHosts']))
		{
			// replace the unsupported lookbehind assertion with a non-capturing subpattern
			$filtersConfig['url']['disallowedHosts'] = str_replace(
				'(?<![^\\.])',
				'(?:^|\\.)',
				$filtersConfig['url']['disallowedHosts']
			);
		}

		return self::encode(
			$filtersConfig,
			array(
				'preserveKeys' => array(
					array(true)
				),
				'isRegexp' => array(
					array('url', 'allowedSchemes'),
					array('url', 'disallowedHosts')
				)
			)
		);
	}

	protected function generateTagsConfig()
	{
		$tagsConfig = $this->tagsConfig;

		foreach ($tagsConfig as $tagName => &$tagConfig)
		{
			$this->fixTagAttributesRegexp($tagConfig);

			/**
			* Sort tags alphabetically. It can improve the compression if the source gets gzip'ed
			*/
			ksort($tagConfig['allow']);

			if (!empty($tagConfig['rules']))
			{
				foreach ($tagConfig['rules'] as $rule => &$tagNames)
				{
					/**
					* The PHP parser uses the keys, but the JS parser uses an Array instead
					*/
					$tagNames = array_keys($tagNames);
				}
				unset($tagNames);
			}
		}
		unset($tagConfig);

		return self::encode(
			$tagsConfig,
			array(
				'preserveKeys' => array(
					array(true),
					array(true, 'allow', true),
					array(true, 'attrs', true)
				),
				'isRegexp' => array(
					array(true, 'attrs', true, 'regexp')
				)
			)
		);
	}

	protected function fixTagAttributesRegexp(array &$tagConfig)
	{
		if (empty($tagConfig['attrs']))
		{
			return;
		}

		foreach ($tagConfig['attrs'] as &$attrConf)
		{
			if (!isset($attrConf['regexp']))
			{
				continue;
			}

			$backslashes = '(?<!\\\\)(?<backslashes>(?:\\\\\\\\)*)';
			$nonCapturing = '(?<nonCapturing>\\?[a-zA-Z]*:)';
			$name = '(?<name>[A-Za-z_0-9]+)';
			$namedCapture = implode('|', array(
				'\\?P?<' . $name . '>',
				"\\?'" . $name . "'"
			));

			$k = 0;
			$attrConf['regexp'] = preg_replace_callback(
				'#' . $backslashes . '\\((?J:' . $nonCapturing . '|' . $namedCapture . ')#',
				function ($m) use (&$attrConf, &$k)
				{
					if ($m['nonCapturing'])
					{
						return $m[0];
					}

					$attrConf['regexpMap'][$m['name']] = ++$k;

					return $m['backslashes'] . '(';
				},
				$attrConf['regexp']
			);
		}
	}

	protected function generatePluginParsers()
	{
		$pluginParsers = array();

		foreach ($this->cb->getLoadedPlugins() as $pluginName => $plugin)
		{
			$js = $plugin->getJSParser();

			if (!$js)
			{
				continue;
			}

			$pluginParsers[] = json_encode($pluginName) . ':function(text,matches){' . $js . '}';
		}

		return implode(',', $pluginParsers);
	}

	protected function generatePluginsConfig()
	{
		$pluginsConfig = array();

		foreach ($this->cb->getLoadedPlugins() as $pluginName => $plugin)
		{
			$js = self::encodeConfig(
				$plugin->getJSConfig(),
				$plugin->getJSConfigMeta()
			);

			$pluginsConfig[] = json_encode($pluginName) . ':' . $js;
		}

		return implode(',', $pluginsConfig);
	}

	/**
	* Remove JS code related to whitespace trimming if not used
	*/
	protected function removeWhitespaceTrimming()
	{
		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['trimBefore'])
			 || !empty($tagConfig['trimAfter']))
			{
				return;
			}
		}

		$this->removeFunctions('addTrimmingInfoToTag');
	}

	protected function injectXSL()
	{
		$pos = 58 + strpos(
			$this->src,
			"xslt['importStylesheet'](new DOMParser().parseFromString('', 'text/xml'));"
		);

		$this->src = substr($this->src, 0, $pos)
		           . addcslashes($this->cb->getXSL(), "'\\\r\n")
		           . substr($this->src, $pos);
	}

	static public function encodeConfig(array $pluginConfig, array $struct)
	{
		unset(
			$pluginConfig['parserClassName'],
			$pluginConfig['parserFilepath']
		);

		// mark the plugin's regexp(s) as global regexps
		if (!empty($pluginConfig['regexp']))
		{
			$keypath = (is_array($pluginConfig['regexp']))
			         ? array('regexp', true)
			         : array('regexp');

			$struct = array_merge_recursive($struct, array(
				'isGlobalRegexp' => array(
					$keypath
				)
			));
		}

		return self::encode($pluginConfig, $struct);
	}

	static public function encode(array $arr, array $struct = array())
	{
		/**
		* Replace booleans with 1/0
		*/
		array_walk_recursive($arr, function(&$v)
		{
			if (is_bool($v))
			{
				$v = (int) $v;
			}
		});

		return self::encodeArray($arr, $struct);
	}

	static public function convertRegexp($regexp, $flags = '')
	{
		$pos = strrpos($regexp, $regexp[0]);

		$modifiers = substr($regexp, $pos + 1);
		$regexp    = substr($regexp, 1, $pos - 1);

		if (strpos($modifiers, 's') !== false)
		{
			/**
			* Uses the "s" modifier, which doesn't exist in Javascript RegExp and has
			* to be replaced with the character class [\s\S]
			*/
			$regexp = preg_replace('#(?<!\\\\)((?:\\\\\\\\)*)\\.#', '$1[\\s\\S]', $regexp);
		}

		/**
		* Replace \pL with \w because Javascript doesn't support Unicode properties. Other Unicode
		* properties are currently unsupported.
		*/
		$regexp = str_replace('\\pL', '\\w', $regexp);

		$modifiers = preg_replace('#[SusD]#', '', $modifiers);

		$js = 'new RegExp(' . json_encode($regexp)
		    . (($flags || $modifiers) ?  ',' . json_encode($flags . $modifiers) : '')
		    . ')';

		return $js;
	}

	static public function encodeArray(array $arr, array $struct = array())
	{
		$match = array();

		foreach ($struct as $name => $keypaths)
		{
			foreach ($arr as $k => $v)
			{
				$match[$name][$k] =
					(in_array(array($k), $keypaths, true)
					|| in_array(array(true), $keypaths, true));
			}
		}

		foreach ($arr as $k => &$v)
		{
			if (!empty($match['isRegexp'][$k]))
			{
				$v = self::convertRegexp($v);
			}
			elseif (!empty($match['isGlobalRegexp'][$k]))
			{
				$v = self::convertRegexp($v, 'g');
			}
			elseif (is_array($v))
			{
				$v = self::encodeArray(
					$v,
					self::filterKeyPaths($struct, $k)
				);
			}
			else
			{
				$v = json_encode($v);
			}
		}
		unset($v);

		if (array_keys($arr) === range(0, count($arr) - 1))
		{
			return '[' . implode(',', $arr) . ']';
		}

		$ret = array();
		foreach ($arr as $k => $v)
		{
			if (!empty($match['preserveKeys'][$k])
			 || preg_match(self::RESERVED_WORDS_REGEXP, $k)
			 || !preg_match('#^[a-z_0-9]+$#Di', $k))
			{
				$k = json_encode($k);
			}

			$ret[] = "$k:" . $v;
		}

		return '{' . implode(',', $ret) . '}';
	}

	static protected function filterKeyPaths(array $struct, $key)
	{
		$ret = array();
		foreach ($struct as $name => $keypaths)
		{
			foreach ($keypaths as $keypath)
			{
				if ($keypath[0] === $key
				 || $keypath[0] === true)
				{
					$ret[$name][] = array_slice($keypath, 1);
				}
			}
		}

		return array_map('array_filter', $ret);
	}
}