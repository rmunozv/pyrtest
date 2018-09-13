/*
API para manejar paises.

Datos en paises.json Sacados de https://github.com/esosedi/3166
*/
$.get( 'js/paises.json', function( data ) {
  PAIS=data;
	// Listo. se usa alert(PAIS.CL.name) o PAIS["CL"].name  // .iso .name ;
});


