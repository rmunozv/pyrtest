<?php
class Util {
	
    public static function fsListAll($comodin="../xml/*.xml") {
    	return glob($comodin);
    }
    
    public static function fsListDir($comodin="../xml") {
    	return glob($comodin,GLOB_ONLYDIR);
    }
}
  
  
/* Funciones utilitarias */
function out($algo,$caption="",$color="#b0b0b0") {
	// Dumpea una variable. Para debug.
	echo "<pre>";
	if ($caption) {echo "<b style=\"background-color:$color;\">$caption</b><br>"; }
	if (gettype($algo)=="string") {
		var_dump(str_replace("<","&lt;",$algo));
	} else {
		var_dump($algo);
	}
	echo "</pre>";
}

function outtxt($algo,$caption="",$color="#b0b0b0") {
	// Dumpea una variable. Para debug.
	if ($caption) {echo "$caption:\n"; }
	if (gettype($algo)=="string") {
		var_dump(str_replace("<","&lt;",$algo));
	} else {
		var_dump($algo);
	}
	echo "\n";
}

// Entrega un elemento del $_GET o un valor default
function getFromGET($clave,$valorDefault) {
	if (array_key_exists($clave,$_GET))
		return $_GET[$clave];
	else
	  return $valorDefault;
}

// Entrega un elemento del $_GET o un valor default
function getFromPOST($clave,$valorDefault) {
	if (array_key_exists($clave,$_POST))
		return $_POST[$clave];
	else
	  return $valorDefault;
}
    
?>