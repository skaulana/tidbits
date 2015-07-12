
/*
 *   DOM and form selectors
 */

function getobject(id)
{
	if (document.all)	return document.all[id];
	else			return document.getElementById(id);
}

function togglediv(id)
{
	var divid = getobject(id);
	if (divid.style.display == 'block')	divid.style.display = 'none';
	else					divid.style.display = 'block';
}

function turndivon(id)
{
	var divid = getobject(id);
	divid.style.display = 'block';
}

function turndivoff(id)
{
	var divid = getobject(id);
	divid.style.display = 'none';
}

/*
 *   Basic form functions
 */

function cleartext(id)
{
	if (id.defaultValue==id.value) id.value = '';
}

function wordcount(textfield)
{
	return textfield.value.split(' ').length;
}