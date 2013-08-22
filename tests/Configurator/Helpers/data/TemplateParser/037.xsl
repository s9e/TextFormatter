<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="html" encoding="utf-8" />

	<xsl:template match="FOO">
		<div>
			<xsl:comment> ... </xsl:comment>
			<xsl:apply-templates/>
		</div>
	</xsl:template>
</xsl:stylesheet>