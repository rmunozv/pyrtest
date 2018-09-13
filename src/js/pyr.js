/*
Libreria de clases para pyr
*/


/*
class recursoItem
*/
function recursoItem() {
}

/*
Escoge k alternativas entre las disponibles en un arreglo arr.
k puede ser mayor que la longitud de arr. En ese caso se entregan elementos repetidos.
*/
function randomizar(arr,k) {
	var respuesta = [];
	
	var N=arr.length;
	if (N<1) return respuesta;
	
	if (k==undefined) k=4; // Por default 4 elementos
	
	// Paso 1: Crear arreglo con los n indices
	var indices = [];
	for (i=0;i<arr.length;i++)indices.push(i);


  // Paso 3: Devolver k elementos. Iterar circularmente, pueden devolverse elementos repetidos si se da mas de una vuelta.
  for (i=0;i<k;i++) {
  	var usar=i % arr.length; // Modulo n
  	if (usar==0) shuffleArray(indices); // Cada vez que inicie una vuelta volver a desordenar los elementos
  	//respuesta.push(arr[indices[usar]]);
  	respuesta.push(indices[usar]);
  }
  
	return respuesta;
}

/* Desordena un arreglo */
function shuffleArray(array) {
    for (var i = array.length - 1; i > 0; i--) {
        var j = Math.floor(Math.random() * (i + 1));
        var temp = array[i];
        array[i] = array[j];
        array[j] = temp;
    }
}

/* 
Genera matriz con indices de preguntas escogidas y alternativas. 
Si NPreguntas <= arr.length no se repiten las preguntas.
Si nAlternatvas <= arr.length no se repiten las alternativas.

La respuesta es un arreglo[nP] de arreglos[nA]. [ [a b c d] [e f g h] ... nP veces  ]
El primer elemento de cada arreglo es la alternativa correcta para la pregunta.
Los primeros elementos de cada arreglo no se repiten.
 */
function generarMatriz(arr,NPreguntas,NAlternativas) {
	var resp = [];
	var preguntas=randomizar(arr,NPreguntas);
	// para cada pregunta generar NAlternativas alternativas
	
	preguntas.forEach(function(indiceP) {
		//console.log('Matriz para '+indiceP);
		// Generar las demas alternativas, distinta de indiceP
		var alternativas=[ indiceP ];
		var k=0;
		while (k<NAlternativas-1) {
			indice=Math.floor(Math.random()*arr.length);
			
			// Agregarlas sólo si no están repetidas o si ya se usaron tantas como el tamaño de las preguntas.
			//(se piden más alternativas que opciones, y en ese caso necesariamente se repiten las alternativas).
			//console.log('Ensayar con '+indice+' cuando alternativas.length='+alternativas.length+' y arr.length='+arr.length);
			if (!alternativas.includes(indice) || alternativas.length>=arr.length) {
				alternativas.push(indice);
				//console.log('alt '+k+' agrega '+indice,alternativas);
				k++;
			}
		}
		
		resp.push(alternativas);
	});
	
	return resp;
}
