/**
 * String
 * @version 1.0.0
 */
String.prototype.br2nl = function()
{
	return this.replace(/<br\s*\/?>/mg, "\n");
};

String.prototype.nl2br = function()
{
	return this.replace(/([^>])\n/g, "$1<br />\n");
};

String.prototype.ucFirst = function()
{
	return this.charAt(0).toUpperCase() + this.slice(1);
};