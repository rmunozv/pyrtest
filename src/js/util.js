/*
Funciones utilitarias
*/

/* Asignar el footer en todas las paginas */
function footerPut() {
	$("div#footer").text('');
	$("div#footer").append('&copy;RMunoz 2018 - Para Inti de Papá');
	//$("div#footer").append('<br/>');
	//$("div#footer").append(navigator.userAgent);
	//$("div#footer").append('<br/>');
	//$("div#footer").append(window.screen.availWidth +'x'+window.screen.availHeight+' color '+window.screen.colorDepth);
	return false;
}