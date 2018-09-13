<?php
// Manejador del almacen de premios

require_once("class/Util.php");
$categoria=getFromPOST("categoria",getFromGET("categoria",""));
$archivo=getFromPOST("archivo",getFromGET("archivo",""));
$accion=getFromPOST("accion",getFromGET("accion",""));

function leer($archivo) {
}

function escribir($data) {
}

class cRespuesta {
	public $bart,$santa,$intentos,$categoria,$archivo;
}

$respuesta = new cRespuesta();
$respuesta->bart = getFromGET("bart",0);
$respuesta->santa = getFromGET("santa",0);
$respuesta->categoria = $categoria;
$respuesta->archivo = $archivo;
//out($respuesta);

function guardar($r,$reset=false) {
	// El archivo es el mismo del test, con extension .json
	$fname = "test/" . $r->categoria . "/premios.json";
	
	$fname=dirname($r->archivo) . "/" . basename($r->archivo,'.xml') . ".json" ;
	
	// Paso 1. Leer el archivo actual. Si no existe iniciar en cero los contadores.
	if (!file_exists($fname)) {
		$bart=0;
		$santa=0;
		$intentos=0;
	} else {
		if ($data=file_get_contents($fname)) {
			$json = json_decode($data,true);
	//		out($json);
			
			$bart=(int)$json['bart'];
			$santa=(int)$json['santa'];
			$intentos=(int)$json['intentos'];
		} else {
			$bart=0;
			$santa=0;
			$intentos=0;
		}
	}
	// Paso 2. Actualizar los totales
	$r->bart += $bart;
	$r->santa += $santa;
	$r->intentos += 1;

	// Si es con reset los totales quedan en cero
	if ($reset) {
		$r->bart     = 0;
		$r->santa    = 0;
		$r->intentos = 0;
	}
	
	// Paso 3. Escribir al archivo
	$f=fopen($fname,"w");
	fwrite($f,json_encode($r));
	fwrite($f,"\n");
	fclose($f);
}


if ($accion=='save') guardar($respuesta);
else if ($accion=='reset') guardar($respuesta,true);

echo json_encode($respuesta);
?>