<html>
<? 
require_once("class/Util.php");
$directorios = Util::fsListDir("test/[a-z|A-Z|0-9]*");
?>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
		<title>Guantecillo: responde estas preguntas!</title>
		<link rel="icon" type="image/png" href="icon.png" />
		<link rel="stylesheet" href="pyr.css" type="text/css" />
		
		<script src="js/jquery.js"></script>
		<script src="js/util.js"></script>
		
<script type="text/javascript">

	$("document").ready(function() {
		//definirEventos();
	});
	
	function definirEventos() {
		
		/* Mouse sobre las categorias*/
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

		/* Click en una categoria*/
		// El div viene con id='nombre de la carpeta que corresponde a la categoria'
		$("div.categoria").click(function() {
			//$o=$(this);
			var carpeta = $(this).attr("id");
			avisar("click en "+carpeta);
			// ir a gocat.php?cat=carpeta
			abrir_pagina(carpeta);
		});

	}
	
	function avisar(s) {
		console.log(s);
		$("#aviso").prepend("<br/>");
		$("#aviso").prepend(s);
	}

	function abrir_pagina(categoria) {
		$("#form_ #categoria").val(categoria);
		$("#form_").submit();
	}
		
</script>
		
</head>

<body>
	<form id="form_" method="post" action="gocat.php">
		<input type="hidden" name="categoria" id="categoria" />
	</form>
	
	<h1>Categorías</h1>
	<?
	foreach($directorios as $carpeta) {
		$dir=basename($carpeta);
		echo "<div class='categoria' id='$dir'>$dir</div>";
	}
	?>
	<div id="aviso"></div>

	<div id="footer">
	</div>	
	
<script type="text/javascript">
	definirEventos();
	footerPut();
</script>

</body>
</html>