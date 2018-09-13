<? 
require_once("class/Util.php");
$categoria=$_POST["categoria"];
$directorios = Util::fsListDir("test/[a-z|A-Z]*");
$archivos = Util::fsListAll("test/$categoria/[a-z|A-Z|0-9]*.xml");
?>
<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
		<title><?=$categoria?></title>
		<link rel="icon" type="image/png" href="icon.png" />
		<link rel="stylesheet" href="pyr.css" type="text/css" />
		
		<script src="js/jquery.js"></script>
		<script src="js/util.js"></script>
		
<script type="text/javascript">

	$("document").ready(function() {
		// Se movio al final del body
		//definirEventos();
	});

/* Esto hay que hacerlo despues que están todos los elementos dibujados */
function definirEventos() {
			$("div.categoria").click(function() {
				$o=$(this);
				// Este div trae elementos id=TEST y tag=full/path/al/archivo.xml
				avisar("click para abrir "+$o.attr("id")+" en "+$o.attr("tag"));
				// ir a gotest.php?cat=$o.attr("tag")
				abrir_pagina($o.attr("tag"));
			});
			
			$("div.categoria")
				.mouseover(function() {
					$(this).addClass("selected");
				})
				.mouseout(function() {
					$(this).removeClass("selected");
				})
				/* En la TV es focus/blur */
				.focus(function() {
					$(this).addClass("selected");
				})
				.blur(function() {
					$(this).removeClass("selected");
				});
				
}
		
function avisar(s) {
	console.log(s);
	$("#aviso").prepend("<br/>");
	$("#aviso").prepend(s);
}

function abrir_pagina(archivo) {
	$("#form_ #archivo").val(archivo);
	$("#form_").submit();
}

function focus_en_primero() {
	//$(".categoria:first").mouseover();
	//$(".categoria:first").mouseenter();
	//window.focus();
	return true;
}

function procesar(xstring) {
	
	$('categoria',xstring).each(function(i) {
		cat=$(this); // este es un objeto jQuery
		CAT=cat[0];
		document.write('<h2>Categoria <u>'+CAT.id+'</u></h2>'+CAT.innerHTML+'<br/>');
	})
	
	$('test',xstring).each(function() {
		/*test=$(this);
		avisar("Tengo un test "+test);
		TEST=test[0];*/

		TEST=$(this)[0];
		avisar("Tengo un test "+TEST);
		avisar(TEST.getAttribute('nombre'));
		
		// Las preguntas de cada uno
		selector="test[nombre='"+TEST.getAttribute('nombre')+"'] pregunta";
		avisar("Voy a buscar con selector: "+selector);
		i=0;
		$(selector,xstring).each(function(){
			i++;
			preg=$(this);
			avisar('Encontrada pregunta #'+i+': '+preg);
			PREG=preg[0];
			avisar('    '+PREG.children[0].innerHTML);
		});
	});
}

function success_geoposition(pos) {
  var crd = pos.coords;

  console.log('Your current position is:');
  console.log('Latitude : ' + crd.latitude);
  console.log('Longitude: ' + crd.longitude);
  console.log('More or less ' + crd.accuracy + ' meters.');
}
		
</script>
		
	</head>
<body>
	<form id="form_" method="post" action="gotest.php">
		<input type="hidden" name="categoria" id="categoria" value="<?=$categoria?>" />
		<input type="hidden" name="archivo" id="archivo" />
	</form>

	<h1>
		
		<a href="categorias.php"><img border="0" src="img/home.png" height="54" align="absmiddle" /></a>
		|
		&nbsp;
		<?=$categoria?>
		</h1>
<?
		foreach($archivos as $fullarch) {
			$arch=basename($fullarch);
			$display=substr($arch,0,-4); // Con -4 le quito el .xml del final
			echo "<div class='categoria' id='$arch' tag='$fullarch'>$display</div>";
		}
?>
	<div id="aviso"></div>

	<div id="footer">
	</div>	
	<script type="text/javascript">
		definirEventos();
		footerPut();
		focus_en_primero();
		//navigator.geolocation.getCurrentPosition(success_geoposition);
	</script>
</body>
</html>