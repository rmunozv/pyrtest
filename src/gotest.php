<? 
require_once("class/Util.php");
$categoria=$_POST["categoria"];
$archivo=$_POST["archivo"];
?>
<html>
	<head>
<style type="text/css">
	/* Este es un posicionador para recibir los estilos definidos en el Xml */
</style>
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
		<meta http-equiv="Cache-Control" content="no-store" />
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
		<title><?=$categoria?></title>
		<link rel="icon" type="image/png" href="icon.png" />
		<link rel="stylesheet" href="pyr.css?<?=rand()?>" type="text/css" />
		
		<script src="js/jquery.js"></script>
		<script src="js/util.js"></script>
		<!--<script src="js/paises.js"></script>-->
		<script src="js/pyr.js?random=<?=rand()?>"></script>
		
<script type="text/javascript">
	//Definicion de variables globales                                

	xmlfile='<?=$archivo?>';
	categoria = "<?=$categoria ?>";
	
	testname='<?= substr(basename($archivo),0,-4) ?>';
	AUTOAVANZAR = false;   	// Si true después de responder avanza a la próxima pregunta
	var testEnCurso=false; 	// Si true hay un test en curso.
	var revisando=0;				// Cantidad de veces que se ha apretado el botón evaluar. Es el número de intentos anteriores.
	
	$("document").ready(function() {
		// Nada aquí. La TV no procesa este ready en el momento oportuno
		console.log("*********** Activando fase final en document ready...");
		log("Definiendo document.ready..."); // Esta escritura es relevante. Sin ella a veces no queda definida la accion que sige click()....
	
		console.log("*********** Termine el document.onReady()");
		//$("h1").fadeOut(); // Este es un testigo visual para saber que está todo cargado
	});

// Define todos los eventos onClick()
function definirClicks() {
	
	/* Mover izq/der */
	$(".barranavegar .boton").click(function() {
		boton=$(this);
		direccion= boton.hasClass("left") ? -1 : +1;
		preguntaContenedora=$(this).parents(".pregunta");
		indice= 1 * preguntaContenedora.attr("index");
		N = $(".pregunta").length;
		
		newindice = indice + direccion;
		if (newindice<1) newindice=N;
		if (newindice>N+1) newindice=1; // Aceptable ir a N+1, que es la pagina para evaluar.
		cp=$("#progress .CuadroDeProgreso[indice='"+indice+"'");
		estado= cp.hasClass("mala")?"mala":
						cp.hasClass("buena")?"buena":
						cp.hasClass("sincontestar")?"sincontestar":"otro";
		log('Click en boton. Direccion es '+direccion+' en pregunta #'+indice+' que es '+estado+'. Proxima es '+newindice);
		if (newindice<=N) {
			stopAnimarBoton();
			setCurrentPregunta(newindice);
		}	else
			setPaginaEvaluar();	
	});
	
	/* Saltar a cierta pregunta pichando en la barra de progreso */
	$("span.cuadroDeProgreso").click(function() {
		cuadro=$(this);
		indice=cuadro.attr("indice");
		if (indice<=$(".pregunta").length)
			setCurrentPregunta(indice);
		else
			setPaginaEvaluar();		
	});
	
	/* Escoger una alternativa. Se resalta la alternativa y se modifica el cuadro de progreso */
	$("div.alternativa").click(function() {
		log('Clicked alternativa');
		clicked=$(this);
		$(this).siblings().removeClass("respuestaActual"); // Quitar el resalte a todos los hermanos.
		$(this).addClass("respuestaActual"); // Agregar el resalte a esta respuesta, porque es la nueva respuesta
		ipregunta=Number(clicked.parents(".pregunta").attr("index"));
		ialternativa=clicked.index()+1; // Base 0
		
		actualRespuesta=$("span.cuadroDeProgreso[indice="+ipregunta+"]").attr("respuesta");
		
		log("Click en pregunta #"+ipregunta+". Respuesta actual="+(actualRespuesta==null?"No hay":actualRespuesta)+", respuesta nueva="+ialternativa+' revisando='+revisando);
		
		$("span.cuadroDeProgreso[indice="+ipregunta+"]").addClass(revisando==0?"contestada":"recontestada"); // Marcar como contestada y recontestada
		$("span.cuadroDeProgreso[indice="+ipregunta+"]").attr("respuesta",ialternativa); // y guardar la respuesta como atributo
		
		// Si vuelve a escoger la misma escogida se interpreta como deshacer la respuesta
		if (ialternativa==actualRespuesta) {
			$("span.cuadroDeProgreso[indice="+ipregunta+"]").removeClass("contestada"); // Marcar como no contestada
			$("span.cuadroDeProgreso[indice="+ipregunta+"]").removeClass("recontestada"); // Marcar como no recontestada
			$("span.cuadroDeProgreso[indice="+ipregunta+"]").removeAttr("respuesta"); // Se queda sin respuesta
			$(this).removeClass("respuestaActual"); // Quitar el resalte de
		} else { // sino autoavanzar
			AUTOAVANZAR = $("#chk_autoavanzar").is(":checked");
			if (AUTOAVANZAR) {
				setTimeout(function(){
					stopAnimarBoton(); // En este punto detengo la posible animacion
					if (revisando==0)
						nextpregunta=ipregunta+1;
					else
						nextpregunta=getNextPendiente(ipregunta); // todo: obtener la sgte pregunta sin contestar o mala
							
					if (nextpregunta<=FULLTEST.length)
						setCurrentPregunta(nextpregunta);
					else
						setPaginaEvaluar();	
				}
				,800);
			}
		}

	});
	
	/* Empezar el test */
	$("#btn_comenzar").click(function() {
		$("#about").hide();
		$("#testarea").fadeIn();
		$("#progress").fadeIn();
		testEnCurso=true;
	});
	
	/* Contar las buenas y malas */
	$("#btn_evaluar").click(function() {
		// Si ya terminó el Test no hay que hacer nada.
		if (!testEnCurso) {
			return;
		}
		revisando++;
		log('Voy a evaluar por '+revisando+'a vez ...'); // 1a, 2a, etc.  No hay límite, pero debería influir en las recompensas.
		stopAnimarBoton();
		var buenas=0;
		var malas=0;
		var sincontestar=0;
		
		// Quitar los efectos de color
		$("span.cuadroDeProgreso").removeClass("buena");
		$("span.cuadroDeProgreso").removeClass("mala");
		$("span.cuadroDeProgreso").removeClass("sincontestar");
		
		var p=0; // Preguntas se numeran de 1 a n
		FULLTEST.forEach(function(UnTest) {
			p++;
			$preguntaX=$("#pregunta_"+p);
			if (UnTest.tipo=="alternativas") {
				log('Evaluar como tipo alternativas');
				correcta=FULLTEST[p-1].correcta; // p-1 porque es base 0. 
				// La correcta es la posicion 0,1,2,... dentro de las alternativas mostradas.
				// Esto permite solo una respuesta correcta, lo cual esta bien para preguntas generadas random, donde se garantiza que solo
				// una de ellas es la correcta.
				// todo:permitir varias alternativas correctas.
				
				$respondida=$preguntaX.find(".alternativa.respuestaActual"); // Este entrega cual de las alternativas tiene class=respuestaActual
				
				if ($respondida.length==0) {
					$("span.cuadroDeProgreso:nth-child("+p+")").addClass("sincontestar");
					$("#pregunta_"+p+" .enunciado").css("background-color","#aeaeae");
					sincontestar++;
					return true; // Es un continue; return false sería break;
				}
				
				// Antes era index(). Como ahora se revuelven las alternativas, el indice se arrastra como <alternativa indice="xx"...>
				//var tuRespuesta=$respondida.index(); // Esta es la posicion 0,1,2,... dentro de las alternativas
				var tuRespuesta=Number($respondida.attr("indice"));
				// Ahora buscar en el FULLTEST si la alternativa [tuRespuesta] tiene puntaje 1
				// Esto permite que varias alternativas tengan puntaje>0
				puntaje=parseFloat(FULLTEST[p-1].alternativas[tuRespuesta].puntaje);
			} else if (UnTest.tipo=="responder") {
				log('Evaluar como tipo responder');
				correcta=UnTest.respuesta;
				respondida=$("#pregunta_"+p+" .responder_value").text();
				puntaje=Number(correcta)==Number(respondida) ? 1 : 0;
			}
			
			if (puntaje>0) {
				$("span.cuadroDeProgreso:nth-child("+p+")").addClass("buena");
				$("#pregunta_"+p+" .enunciado").css("background-color","#aec7ae");
				buenas++;
			}
			else {
				$("span.cuadroDeProgreso:nth-child("+p+")").addClass("mala");
				$("#pregunta_"+p+" .enunciado").css("background-color","#c7aeae");
				malas++;
			}
		});
		
		var puntaje = Math.floor(buenas/FULLTEST.length*100);
		$("#resultados").html("");
		$("#resultados").append("Buenas: "+buenas).append("<br/>");
		$("#resultados").append("Malas: "+malas).append("<br/>");
		$("#resultados").append("Sin contestar: "+sincontestar).append("<br/>");
		$("#resultados").append("Tu puntaje: "+puntaje+"%").append("<br/>");
		$("#resultados").show();
		
		if (puntaje==100) {
			testEnCurso=false; // Si se logró el 100% terminar el Test.
			$("#btn_evaluar").hide();
		}

		// Ver las recompensa para un puntaje en cierto intento.
		recompensa=asignarRecompensa(puntaje,revisando);
		if (recompensa.hayRecompensa) {
			premios=$("<div />").attr("id","premios").appendTo($("#resultados"));
			$("<span/>").html("Has ganado ").appendTo($("#premios"));
			if (recompensa.bart>0) {
				$("<span />").html(recompensa.bart+"x").appendTo(premios);
				$("<img height='48' />").attr("src","img/bart.jpg").appendTo(premios);
			}
			if (recompensa.santa>0) {
				$("<span> /").html(recompensa.santa+"x").appendTo(premios);
				$("<img height='48' />").attr("src","img/santa.png").appendTo(premios);
			}
			// Boton Recoger
			$("<div />").html("Recoger")
				.addClass("btn_recoger")
				.attr("bart",recompensa.bart).attr("santa",recompensa.santa)
				.appendTo(premios);
		}
	});
	
	// Alterna entre vista grande y normal de una imagen. Solo las del enunciado
	$("div.enunciado img").click(function() {
		$f=$(this);
		w=$f.width();
		h=$f.height();
		
		// Calcular un factor para agrandar hasta el máximo de la ventana
		WW=$(window).width();
		HH=$(window).height();
		
		// Ratios en ancho y alto.
		ratioW=WW/w*0.94; // Un 94% del ancho cabe
		ratioH=HH/h*0.70; // Un 60% del alto cabe

		factor=Math.min(ratioW,ratioH);

		console.log('Click en una imagen de '+w+'x'+h+' en ventana de WW='+WW+' factor='+factor);
		
		if ($f.attr("grande")=="si") { // Hay que achicar
			factor=1/$f.attr("factor");
			w2=w*factor;
			h2=h*factor;
			$f.animate({"width":w2,"height":h2});
			$f.attr("grande","no");
		} else { // Hay que agrandar
			factor=factor;
			w2=w*factor;
			h2=h*factor;
			$f.animate({"width":w2,"height":h2});
			$f.attr("grande","si");
			$f.attr("factor",factor);
		}
	});
	
	$(".tecla").click(function() {
		tecla=$(this);
		valor=$(this).attr("valor");
		isBackspace=$(this).hasClass("tecla_backspace");
		isOk=$(this).hasClass("tecla_ok");
		ipregunta=Number(tecla.parents(".pregunta").attr("index")); // Numero de pregunta, base 1.
		
		recuadro_valor = $(this).parent().parent().children(".responder_value");
		log('recuadro_valor',recuadro_valor);
		if (isOk) {
			// Avanzar pregunta si está activo autoavanzar
			log('Estoy en pregunta ',ipregunta);
			AUTOAVANZAR = $("#chk_autoavanzar").is(":checked");
			if (AUTOAVANZAR) {
				setTimeout(function(){
					stopAnimarBoton(); // En este punto detengo la posible animacion
					if (revisando==0)
						nextpregunta=ipregunta+1;
					else
						nextpregunta=getNextPendiente(ipregunta); // todo: obtener la sgte pregunta sin contestar o mala
							
					if (nextpregunta<=FULLTEST.length)
						setCurrentPregunta(nextpregunta);
					else
						setPaginaEvaluar();	
				}
				,800);			}
			return;
		}
		if (recuadro_valor!=null) {
			currval=recuadro_valor.text();
			if (isBackspace) {
				newval=currval.substring(0,currval.length-1);
			} else {
				newval=currval + valor;
			}
			recuadro_valor.text(newval);
			// Guardar la respuesta en el area de progreso.
			$("span.cuadroDeProgreso[indice="+ipregunta+"]").attr("respuesta",recuadro_valor.text());
			$("span.cuadroDeProgreso[indice="+ipregunta+"]").addClass(revisando==0?"contestada":"recontestada"); // Marcar como contestada y recontestada
			if (newval=="") { // Si respuesta quedó vacía ...
				$("span.cuadroDeProgreso[indice="+ipregunta+"]").removeClass("contestada"); // Marcar como no contestada
				$("span.cuadroDeProgreso[indice="+ipregunta+"]").removeClass("recontestada"); // Marcar como no recontestada
				$("span.cuadroDeProgreso[indice="+ipregunta+"]").removeAttr("respuesta"); // Se queda sin respuesta
			}
		}
	});
	
	// Atajo secreto. Para borrar los puntajes.
	$("#isanta,#ibart").click(function() {
		var n=Number($(this).attr("tap"));
		n=isNaN(n)?1:n+1;
		$(this).attr("tap",n);
		
		// La combinacion es 2 bart y 2 santa
		var selectorElOtro=$(this).attr("id")=='ibart'?'isanta':'ibart';
		var nOtro=$("#" + selectorElOtro).attr("tap");
		if (isNaN(nOtro)) nOtro=0;
		
		//log('nros='+n+', '+nOtro);
		
		// La combinacion es 2+ bart y 2+ santa
		if (n>=2 && nOtro>=2) {
			//log('Hay que resetear los contadores');
			if(confirm('Volver a cero los contadores del test "'+testname+'" ?')) {
				laData = {};
				laData.bart=0;
				laData.santa=0;
				laData.categoria = categoria;
				laData.testname = testname;
				laData.archivo = "<?=$archivo ?>";
				laData.accion = 'reset';
				$.ajax({
			    type: "GET" ,
			    url: "file.php?"+Math.random() ,
			    data: laData,
			    dataType: "json" ,
			    success: function(xdata) {
			    	log('Recibido de vuelta ',xdata);
				    // Actualizar el panel de logros
				    leerPremios('test/'+categoria+'/'+testname);
			    },
			    error: function(jqXHR,txt) {
			    	log('Error llamando a file.php',txt);
			    	log('El objeto extraño',jqXHR);
			    }
			  });
			}
		}
		
	});
	
	// Este es el metodo para bindear sobre elementos creados dinamicamente
	$(document).on("click","div.btn_recoger",function() {
		recoger();
	});
}

// Guardar los premios obtenidos en archivo externo.
function recoger() {
	$boton=$("div.btn_recoger");
	bart=Number($boton.attr("bart"));
	santa=Number($boton.attr("santa"));
	
	log('Recoger '+bart+" bart y "+santa+" santa");
	
	laData = {};
	laData.bart=bart;
	laData.santa=santa;
	laData.categoria = "<?=$categoria ?>";
	laData.archivo = "<?=$archivo ?>";
	laData.accion = 'save';
	
	log('Llamando con data=',laData);
	
	$.ajax({
    type: "GET" ,
    url: "file.php?"+Math.random() ,
    data: laData,
    dataType: "json" ,
    success: function(xdata) {
    	log('Recibido de vuelta ',xdata);
	    // Actualizar el panel de logros
	    leerPremios('test/'+categoria+'/'+testname);
	    // Y apagar el boton
	    $boton.hide();
    },
    error: function(jqXHR,txt) {
    	log('Error llamando a file.php',txt);
    	log('El objeto extraño',jqXHR);
    }
  });
}

// Obtiene desde el archivo <testname>.json la cantidad de premios a la fecha.
// path: Ruta para buscar premios.json
// Devuelve: nada
var PREMIOS={}; // Objeto json será llenado en la funcion leerPremios()
function leerPremios(path) {
	log('leerPremios('+path+')');
	pfile=path + ".json" + "?reload=" + Math.random(); 
	log('Obtener json de '+pfile);
	$.ajax({
		url: pfile,
		dataType: "json",
		success: function(data) {
			var PREMIOS=data;
			// Presentar los premios en la tabla de logros
			$("#lugar_intentos").html(PREMIOS.intentos);
			$("#lugar_bart").html(PREMIOS.bart);
			$("#lugar_santa").html(PREMIOS.santa);
			log('Exito mostrando los logros',PREMIOS);
		},
		error: function(text) {
			console.log('Error mostrando logros:'+text);
		}
	});

}

/*
Politica de asignacion de recompensas. Se entregan premio1 (bart) y premio2 (santa).
+ 100% al primer intento ==> 3 bart
+ 80%  al primer intento ==> 3 santa


*/
function asignarRecompensa(puntaje,intentos) {
	log('Recompensa para puntaje='+puntaje+' en '+intentos+' intentos.');
	if (intentos>3) return {"hayRecompensa":false}; // No hay premio
	bart=0;
	santa=0;
	
	if (intentos==1) {
		if (puntaje==100) bart=3;
		else if (puntaje>=90) santa=3;
		else if (puntaje>=75) santa=2;
	} else if (intentos==2){
		if (puntaje==100) bart=2;
		else if (puntaje>=90) santa=2;
		else if (puntaje>=75) santa=1;
	} else if (intentos==3){
		if (puntaje==100) bart=1;
		else if (puntaje>=90) santa=1;
		else if (puntaje>=75) santa=0;
	}
	
	return {"hayRecompensa": (bart+santa)>0 ,"bart":bart,"santa":santa};
}

/* Obtiene el indice de la proxima pregunta pendiente (mala o no contestada).
Si no hay obtiene un indice mayor que el total de preguntas.*/
function getNextPendiente(indice) {
	// En #progress se analizan las posteriores a 'indice' y se excluyen las marcadas con class="buena" 
	// Los indices css son base 0. El indice de preguntas es base 1.
	result = $("#progress").find(":gt("+(indice-1)+"):not(.buena)").index() + 1;
	log('getNextPendiente('+indice+')='+result);
	return result;	
}

/* Muestra y resalta la pregunta actual. Todas las demas quedan ocultadas y sin resalte */
function setCurrentPregunta(indice) {
	$(".pregunta").hide();
	$("#pregunta_"+indice).show();
	$("span.cuadroDeProgreso").removeClass("current");
	$("span.cuadroDeProgreso:nth-child("+indice+")").addClass("current");
	log('setCurrentPregunta('+indice+')');
	
	// Ademas oculta la pagina de evaluacion y detiene la posible animacion
	$("#evaluacion").hide();
	stopAnimarBoton();
	
}

/* En la GUI muestra la página de evaluación y calcula las estadísticas.
+ Se muestra una estadisticas de las respondidas/por responder
+ Se ofrece botón Evaluar
+ Se muestra el resultado
*/
function setPaginaEvaluar() {
	log('Entro a la pagina de evaluacion, con testEnCurso='+testEnCurso+', revisando='+revisando);
	$(".pregunta").hide();
	
	$("span.cuadroDeProgreso").removeClass("current");
	// Esta es la posicion del recuadro final
	var posEvaluar=$(".pregunta").length+1;
	$("span.cuadroDeProgreso:nth-child("+posEvaluar+")").addClass("current");
	
	$("#evaluacion").show();
  nContestadas=$("#progress span.contestada").length;
  nTotales = FULLTEST.length;
	
	if (nContestadas==0)
	  $("#evaluacion #estadisticas").html("No has contestado ninguna pregunta.");
	else if (nContestadas==nTotales)
		$("#evaluacion #estadisticas").html("Listo, has contestado las "+nContestadas+" preguntas.");
	else {
		plural_singular=(nTotales-nContestadas)==1?"pregunta":"preguntas";
		$("#evaluacion #estadisticas").html("Aún falta contestar "+(nTotales-nContestadas)+" "+plural_singular+".");
	}
	
	if (nContestadas>0) {
		$("#evaluacion #estadisticas").append("<br/><br/>Pulsa el botón para conocer tu puntaje.");
		$("#evaluacion #btn_evaluar").show();
		if (!$("#resultados").is(":visible")) animarBoton(); // Solo animar la primera vez, cuando no se han mostrado los resultados.
	}
	else {
		$("#evaluacion #btn_evaluar").hide();
	}
		
}

function animarBoton() {
	$b=$("#btn_evaluar");
	factor=1.15;
	w1=$b.width();
	h1=$b.height();
	
	w2=w1*factor;
	h2=h1*factor;
	
	$("#btn_evaluar")
		.animate({width:w2,height:h2})
		.animate({width:w1,height:h1});
		
	animarfx=setTimeout(animarBoton,1500);	
}

function stopAnimarBoton() {
	if (typeof animarfx!="undefined") clearTimeout(animarfx);
}
/*
Establece el parseo del archivo Xml en un JSON apropiado.
En el proceso reemplaza los elementos especiales, como <recurso ...> por
la expansión Html adecuada.
El objeto JSON generado es MEMORIA, contiene todas las definiciones del test pero aun falta
resolver algunas partes, como las repeticiones y los recursos.
La fase posterior convierte MEMORIA en FULLTEST. Este ultimo queda listo para renderizar en GUI.
*/
function traerArchivo(xmlfile) {
	// La global MEMORIA es el objeto JSON resultante.
	MEMORIA = {};
	//$ta=$("#testarea");
	//$ta.css("background-color","gray");
	/* Recuperar el archivo y procesar sus partes */
	$.ajax({
	    type: "GET" ,
	    url: xmlfile+'?reload='+Math.random() ,
	    dataType: "xml" ,
	    success: function(xml) {
				log('1-Leido '+xmlfile);
		    oxml=$(xml);
		    //test = $(xml).find('test').text();  
		    
		    /*==============================================================*/
		    /* about 
		    		<test><about>
		    */
		    /*==============================================================*/
				parte_about = oxml.find('test about');
				if (parte_about.length==0) parte_about=$('<span />').html('Este es el test <u>'+testname+'</u> y no tiene descripcion.');
				MEMORIA["about"] = parte_about;
				
		    /*==============================================================*/
		    /* style 
		    		<test><style>
		    */
		    /*==============================================================*/
				parte_style = oxml.find('test style');
				if (parte_style.length>0) {
					//console.log('Agregue estilo'); 
					//console.log(parte_style);
					MEMORIA["style"] = parte_style;
					//MEMORIA.style.appendTo("head"); // Este metodo agrega el estilo pero no lo aplica.
					$("head style").append(parte_style.html()); // Este metodo agrega el estilo y lo aplica, pero necesita un <style> de antemano. No sirve en SMART-TV
				}
				
		    /*==============================================================*/
		    /* global 
		     			<recursos><global>
		    */
		    /*==============================================================*/
				glob     = $(xml).find("recursos global");
				MEMORIA.global = glob;
				
		    /*==============================================================*/
		    /* recursos 
		     			<test><recursos><recurso> | <test><recursos><grupo><recurso>
		    */
		    /*==============================================================*/
				// Recolectar los recursos en la jerarquia <recursos><recurso>...
				MEMORIA.xrecursos = $(xml).find('recursos'); // Guardar esta porcion en la MEMORIA
				LosRecursos = [];
		    $(xml).find('recursos').find('recurso').each(function(){
		    	$r=$(this);
		    	//Obtener la expansion del pseudo tag <recurso> en un tag <img>
		    	rec_expandido=recurso_expandir($r,glob);

		    	// Puede tener un grupo como padre
		    	$padre=$r.parent();
		    	if ($padre!=null && $padre[0].nodeName=='grupo') {
		    		rec_expandido.grupo = $padre[0].id;
		    	}

		      LosRecursos.push(rec_expandido);
		      //$("#testarea").append(rec_expandido.resuelto);  
		    }); 
		    // Guardar los recursos.
				MEMORIA["recursos"] = LosRecursos;
				// Luego los recursos se encuentran y usan con:
				//   <recurso id="cl" />
				//   MEMORIA.recursos.find(item=> {return item.id=='cl'}).resuelto


		    
		    // Las preguntas
		    MEMORIA["eheight"]=$(xml).find('preguntas').attr("eheight"); // Global Height para el enunciado
		    if (typeof MEMORIA["eheight"]=='undefined') MEMORIA["eheight"]="50%";
		    
		    lasPreguntas = [];
		    $(xml).find('preguntas').find('pregunta').each(function() {
		    	$p=$(this);
		    	estaPregunta = {};
		    	
		    	// Caso especial si es una <pregunta repetir="n">
		    	estaPregunta.repetir = $p.attr("repetir");
		    	estaPregunta.isRepetible = ( estaPregunta.repetir!=null );
		    	estaPregunta.isAlternativas = $p.children("alternativas").length>0;
		    	estaPregunta.isResponder = $p.children("responder").length>0;
		    	
		    	estaPregunta.enunciado = $p.children("enunciado");
		    	estaPregunta.enunciadoHeight = estaPregunta.enunciado.attr("height");
		    	if (typeof estaPregunta.enunciadoHeight=="undefined")
		    		estaPregunta.enunciadoHeight = MEMORIA["eheight"]; // Cambiar por <preguntas eheight="xx%">, si no existe asumir 50%
		    	estaPregunta.html = $p.children("enunciado").html();
		    	estaPregunta.resuelto = {}; // todo: expandir un enunciado/alternativa.
		    	estaPregunta.alternativas = [];
		    	estaPregunta.responder = $p.children("responder"); // <responder tipo="num" correcta="A*B" />

		    	// Procesar <alternativas [generar=n grupo=g filtro=f]>
		    	$alternativas = $p.find('alternativas');
		    	estaPregunta.opcionesParaAlternativas = {};
		    	estaPregunta.opcionesParaAlternativas.height = $alternativas.attr("height");
		    	if (estaPregunta.opcionesParaAlternativas.height=="")
		    		estaPregunta.opcionesParaAlternativas.height="50%";
		    	estaPregunta.opcionesParaAlternativas.generar = $alternativas.attr("generar");
		    	estaPregunta.opcionesParaAlternativas.grupo = $alternativas.attr("grupo");
		    	estaPregunta.opcionesParaAlternativas.filtro = $alternativas.attr("filtro");
		    	estaPregunta.opcionesParaAlternativas.renderas = $alternativas.attr("renderas");
		    	log('estaPregunta',estaPregunta);
		    	
		    	// Procesar cada <alternativa>
		    	$p.find('alternativa').each(function() {
		    		estaAlternativa = {};
		    		estaAlternativa.id = $(this).attr("id");
		    		estaAlternativa.puntaje = $(this).attr("puntaje");
		    		estaAlternativa.html = $(this).html();
		    		estaAlternativa.resuelto = {}; // todo: expandir un enunciado/alternativa.
		    		estaPregunta.alternativas.push(estaAlternativa);
		    		log('estaAlternativa',estaAlternativa);
		    		//$ta.append('<span />').append(estaAlternativa.id+': '+estaAlternativa.html);
		    		//$ta.append('&nbsp;');
		    	});
		    	// Agregar al arreglo de preguntas
		    	lasPreguntas.push(estaPregunta);
		    });
		    MEMORIA["preguntas"] = lasPreguntas;

				FULLTEST = expandirMemoria();
				
				// Estas que siguen tienen que estar acá. Sino, aunque se incluyen en el DOM no son presentadas.
				repartirGUI();
				definirClicks();
				presentarTest();
				
		    return true;
	    }    
	});

}

/* repartirGUI 
	Toma los elementos en FULLTEST y crea los elementos del DOM doc_* para presentar el Test.
*/
function repartirGUI() {
	var i=0;
	
	reemplazar=resolverRecurso(MEMORIA.about);
	$("#about").prepend(reemplazar.html());
	agregarFotosAlAbout();
	
	preparar_progress(FULLTEST.length);
	FULLTEST.forEach(function (pregunta) {
		PREGUNTA=pregunta;
		i++;
		// La estructura css es pregunta -> (enunciado,alternativas -> alternativa)
		doc_preg=$("<div id='pregunta_"+i+"' class='pregunta' />").appendTo("#testarea");
		doc_preg.attr("index",i);
		
		if (!(i==1)) {doc_preg.hide(); $("#about").hide();}
		
		// El area_eyr es un div para enunciado y respuesta. Interiormente se reparte el 100% height en los valores que indique el Xml.
		area_eyr = $("<div />").attr("class","enunciado_y_alternativas").appendTo(doc_preg);

		doc_enunciado = $("<div class='enunciado' />").css("height",pregunta.enunciadoHeight).appendTo(area_eyr);
		doc_enunciado.html(pregunta.enunciado);
		
		doc_barranavegar = $("<div class='barranavegar' />").appendTo(doc_preg);
		preparar_barra(doc_barranavegar,i,FULLTEST.length);
		

		if (pregunta.tipo=="alternativas") {		
			doc_alternativas = $("<div class='alternativas' />").css("height",pregunta.alternativasHeight).appendTo(area_eyr);
			// Revolver las alternativas
			orden=randomizar(pregunta.alternativas);
			log('pregunta.alternativas=',pregunta.alternativas);
			//pregunta.alternativas.forEach(function (alt) {
			for (var k=0;k<pregunta.alternativas.length;k++) {
				alt=pregunta.alternativas[orden[k]];
				doc_alternativa = $("<div class='alternativa' />").appendTo(doc_alternativas);
				doc_alternativa.attr("indice",orden[k]);
				doc_alternativa = $("<span/>").appendTo(doc_alternativa);
				doc_alternativa.html(alt.render);
			};
		}
		else if (pregunta.tipo=="responder") {
			// A la izquierda va un teclado.
			// A la derecha el valor que resulta de operar el teclado.
			doc_area_respuesta = $("<div class='responder' />").appendTo(area_eyr);
			doc_teclado = $("<div class='responder_kbd' />").appendTo(doc_area_respuesta);
			doc_input = $("<span class='responder_value' />").appendTo(doc_area_respuesta);
			poner_teclado(doc_teclado);
		}
	});
	
	// Ahora pararse en la primera pregunta
	setCurrentPregunta(1);
}

function poner_teclado(doc_teclado) {
	teclas=['1','2','3','4','5','6','7','8','9','X','0','Ok'];
	teclas.forEach(function(c) {
		new_tecla=$("<span />").addClass("tecla").attr("valor",c).text(c).appendTo(doc_teclado);
		if (c=='X') {
			new_tecla.addClass("tecla_backspace");
			new_tecla.text(""); // Quitar la X
		}
		else if (c=='Ok') {
			new_tecla.addClass("tecla_ok");
			new_tecla.text(""); // Quitar la parte Ok
		}
	});
}

// Crea la barra de accion para 1 pregunta, con los botones ATRAS/ADELANTE y un contador x/N
function preparar_barra(barra,i,N) {
	
	b1=$("<span class='boton left'/>").appendTo(barra);
	b1.html("");
	estadisticas=$("<span class='stats'/>").appendTo(barra);
	//N = MEMORIA.preguntas.length;
	estadisticas.html(i+" / "+N);
	b3=$("<span class='boton right'/>").appendTo(barra);
	b3.html("");
}

// Crea la barra de progreso para N preguntas
// La barra mantiene el estado de contestar y el número de la alternativa respondida hasta ahora.
function preparar_progress(N) {
	ancho=100/(N+1); // Son N preguntas + 1 cuadro de resultados
	log('Preparar progress para '+N+' elementos, ancho='+ancho);
	var P=$("#progress");
	if (N==0) {
		P.hide();
		return;
	}
	for(var k=0;k<(N+1);k++) {
		cuadro=$("<span/>").addClass("CuadroDeProgreso").appendTo(P);
		cuadro.css("width",ancho+"%");
		cuadro.attr("indice",k+1);
	}
	
	P.show();
	$("#resultados").hide();

}

// $r es un objeto jQuery para <recurso tipo="img" id="uk" src="http://flags.fmcdn.net/data/flags/w580/gb.png" caption="Gran Bretaña" title="Palacio de Buckinham" />
// Devuelve la expansion de $r segun el tipo.
// glob es como <global height="120" localdir="res" class="miniatura" />
// Por ahora:
//   Tipo:img  ===>  <img src="" ...>
function recurso_expandir($r,glob) {
	//console.log('recurso_expandir('+$r+','+glob+')');
	if (glob==null) {
		localdir="";clase="";height="";
	} else {
		localdir = glob.attr("localdir");
		clase    = glob.attr("class");
		height   = glob.attr("height");
	}
	if (localdir==null || localdir=="") localdir='.';
	if (clase==null) clase='';
	if (height==null) height='';

	tipo = $r.attr("tipo");
	id   = $r.attr("id");
	src  = $r.attr("src")||'';
	caption = $r.attr("caption")||'';
	title = $r.attr("title")||'';
	localheight = $r.attr("height")||'';
	if ($r.attr("class")!=undefined) clase=$r.attr("class");
	
	//console.log('atributos(tipo:'+tipo+', id:'+id+', src:'+src+', localheight:'+localheight+')');
	url = src;
	// Ver si el recurso es local. En ese caso intentar agregarle la base.
	// En la TV no funciona startsWith. ASe usara substr
	//if (!xsrc.startsWith('http://') && !xsrc.startsWith('https://')) {
	if (!(src.substr(0,4)=='http')) {
		//console.log('Corrigiendo url '+src+' con localdir=['+localdir+']');
		url = 'test/<?=$categoria?>/' + localdir + '/' + src;
	}
	
	s = "<img id='@1' src='@2' title='@3' @HEIGHT @CLASE />";
	s = s.replace(/@1/g,id).replace(/@2/g,url).replace(/@3/g,title);
	
	// Para @HEIGHT primero el local, sino el global, sino nada.
	if (localheight!='')
		s=s.replace("@HEIGHT","height='"+localheight+"'");
	else if (height!='')
		s=s.replace("@HEIGHT","height='"+height+"'");		
	else
		s=s.replace('@HEIGHT','');	
	
	if (clase=='') 
		s=s.replace('@CLASE','');
	else	
		s=s.replace(/@CLASE/,"class='"+clase+"'");		

	// Envolver la imagen con <div class='fotografia'> <IMG> <div class='subtitle'> EL_SUBTITULO </div> </div>
	if (title!=null) {
		s = "<div class='fotografia'>" + s + "<div class='subtitle'> "+title+" </div></div>";
	}
	
  esterecurso = {
  	"tipo" : tipo,
  	"id": id,
  	"caption": caption,
  	"title": title,
  	"src": src,
  	"resuelto" : s,
  	"imgurl" : url
  }
  
  // todo: ver si tiene atributos adicionales, como sexo=f
  conocidos=['id','tipo','src','caption','title'];
  for (j=0;j<$r[0].attributes.length;j++) {
  	atributo=$r[0].attributes[j].name;
  	valor=$r[0].attributes[j].value;
  	if (conocidos.includes(atributo)) continue;
  	log('Encontre atributo '+atributo+'='+valor);
  	esterecurso[atributo]=valor;
  }
  return esterecurso;
}

function log(s,objeto) {
	console.log(s);
	if(objeto!=null) console.log(objeto);
	return;
	
	$("#aviso").prepend("<br/>");
	if(objeto!=null) {
		if (objeto instanceof jQuery)
			try {
				$("#aviso").prepend(objeto[0].outerHTML.replace(/</g,'&lt;'));
			} catch (err) {}
		else	
			$("#aviso").prepend(objeto);
	}
	$("#aviso").prepend(s+' :: ');
}

function abrir_pagina(categoria,archivo) {
	$("#form_ #categoria").val(categoria);
	$("#form_ #archivo").val(archivo);
	$("#form_").submit();
}

/*
Resuelve las partes en pseudo-tag, las partes @random y las repeticiones de la MEMORIA.
Las partes en pseudo-tag pueden ir en enunciado, alternativas.
Las partes @random pueden ir en el pseudo-tag <recurso ...>
Las repeticiones van en MEMORIA.preguntas[k].repetir y en MEMORIA.preguntas[k].opcionesParaAlternativas.generar (.grupo .filtro)

Esta expansion devuelve un objeto FULLTEST, con todas las preguntas planas listas para ser presentadas en la GUI.
Opcionalmente se pueden revolver al azar las preguntas y alternativas.
*/
function expandirMemoria() {
	respuesta = [];
	
	var p=0;
	MEMORIA.preguntas.forEach(function (pregunta) {
		p++;
		log('Expandiendo pregunta #'+p);
		if (p==1) PP=pregunta; // Guardar para debug
		if (pregunta.isRepetible) {
			log('Debo generar '+pregunta.repetir+' preguntas a partir de '+pregunta.html);
			
			// Paso 1. Armar el conjunto de recursos segun el grupo y filtro
			// El fragmento está contenido en el enunciado y es de la forma:
			//      <recurso id="@random" grupo="alumnos" filtro="sexo:f" renderas="img" />
			//fragmento=pregunta.enunciado.children("recurso"); // Por ahora solo ver el caso <recurso...>
			fragmento=pregunta.enunciado.find("recurso"); // Por ahora solo ver el caso <recurso...> TODO: ¿puede haber mas de un <recurso> en el enunciado?
			grupo=fragmento.attr("grupo");
			renderas = fragmento.attr("renderas");
			log('Generar como '+fragmento.attr("renderas")+' a partir de grupo '+grupo+' id='+fragmento.attr("id"));
			
			// Paso 1a. Generar el selector adecuado como "recursos grupo#alumnos recurso[sexo='f']"
			selector="recursos"; // Gran padre es <recursos ...>
			if (grupo!=null) {
				selector+=" grupo#"+grupo; // Puede haber un grupo al que pertenezca <recurso ...><grupo...>
			}
			
			selector += ' recurso'; // Luego viene <recurso ...>
			if ((elFiltro=pregunta.opcionesParaAlternativas.filtro)!=null) { // y puede haber un filtro="sexo:f"
				var pos=elFiltro.indexOf(':');
				var atributo=elFiltro.substring(0,pos);
				var valor=elFiltro.substring(pos+1);
				// Agregar al selector la parte [sexo='f']
				selector +="["+atributo+"='"+valor+"']";
			}
			log('El selector es '+selector);

			// Obtener el subconjunto de recursos y revolverlo.			
			arr=MEMORIA.xrecursos.find(selector);
			
			// Generar la matriz de preguntas y alternativas.
			matriz=generarMatriz(arr,pregunta.repetir,pregunta.opcionesParaAlternativas.generar);
			// La matriz es [  [iCorrecta a b c] [iCorrecta d e f]  ... ]  Los indices van de 0 a nOpciones-1
			// El primer elemento es el correcto.
			
			
			//OrdenDeLasPreguntas = randomizar(arr,pregunta.repetir); // Entrega los indices en orden random
			//shuffleArray(arr);
			log('En MEMORIA.xrecursos.find("'+selector+'") se seleccionaron '+arr.length+' elementos');

			//log('OrdenDeLasPreguntas=======',OrdenDeLasPreguntas);
			// Paso 2. Generar las preguntas, según <pregunta repetir="6">
			for (k=0;k<pregunta.repetir;k++) {
				log('\n================ Para pregunta '+(k+1)+' ============================');
				
				if (pregunta.isResponder) { // Para <responder tipo="num" correcta="A*B" />
					// La pregunta se responde con una palabra en teclado (numerico o texto)
					log('Pregunta es de tipo responder. Enunciado='+pregunta.enunciado.html());
					PREGUNTANUEVA={};
					PREGUNTANUEVA.tipo="responder";
					
					// Buscar en el enunciado los elementos <numero>. Agregarlos a las variables
					variables = [];
					pregunta.enunciado.find("numero").each(function(item) {
						$numero=$(this);
						log('numero ',$numero);
						item = {};
						item.id=$numero.attr("id");
						item.min=parseInt($numero.attr("min"));
						item.max=parseInt($numero.attr("max"));
						item.valorSpec=$numero.attr("valor"); // Generador para el valor
						item.valor=getValor(item.min,item.max,item.valorSpec); // Valor numerico
						variables.push(item);
					}); 
					log('variables[]=');
					console.log(variables);
					//PREGUNTANUEVA.enunciado=pregunta.enunciado.html();
					PREGUNTANUEVA.enunciado=resolverEnunciadoNumerico(pregunta.enunciado.html(),variables);
					PREGUNTANUEVA.respuesta=resolverExpresionNumerica(pregunta.responder.attr("correcta"),variables);
					respuesta.push(PREGUNTANUEVA);
				}
				else if (pregunta.isAlternativas) {
					// En cada pregunta hay que generar cierta cantidad de alternativas, segun
					//    <alternativas generar="4" grupo="alumnos" filtro="sexo:f" >
					log('Pregunta es de tipo alternativas');
					PREGUNTANUEVA = {};
					PREGUNTANUEVA.tipo="alternativas";
					N=pregunta.opcionesParaAlternativas.generar;
					//Subconjunto = randomizar(arr,N); // Escoge N indices de arr en un arreglo como [4,1,8,3]
					Subconjunto = matriz[k]; // Escoge N indices de arr en un arreglo como [4,1,8,3]
					//laCorrecta = Math.floor(Math.random()*N); // Una de las N será la correcta, como 2 => el escogido es 8 => arr[8]
					laCorrecta = 0; // La primera del conjunto
					indiceCorrecto = Subconjunto[laCorrecta];
					elementoCorrecto=arr[indiceCorrecto];
					log('Subconjunto:'+Subconjunto+' correcta:'+indiceCorrecto);
					// Resolver el enunciado. Depende de cuál es la alternativa correcta.
					PreguntaBase = $('<x>'+pregunta.html+'</x>');
					
					// Mapear desde xrecurso a recurso.
					recursoEnPool = MEMORIA.recursos.find(item => {return item.id==elementoCorrecto.id});
					log('Recurso correcto: '+recursoEnPool.caption+' (id='+recursoEnPool.id+')');
					
					// Mapear los <math id="A" min="1" max="5" valor="@random" />
					
					//PREGUNTANUEVA.indice 	= indiceCorrecto; // Este no sirve para nada, no usar.
					PREGUNTANUEVA.correcta= laCorrecta; // Esta es la posicion de la alternativa correcta 0,1,2,...
					PREGUNTANUEVA.caption	=	recursoEnPool.caption;
					PREGUNTANUEVA.id			=	recursoEnPool.id;
					escogido = recursoEnPool;
					switch (renderas) {
						case "caption": PREGUNTANUEVA.reemplazo = recursoEnPool.caption;break;
						case "img": PREGUNTANUEVA.reemplazo = recursoEnPool.resuelto; break;
						default: 
								// en renderas viene  un nombre de atributo
								PREGUNTANUEVA.reemplazo = recursoEnPool[renderas];
								break;
					}
					PreguntaBase.find('recurso').replaceWith(PREGUNTANUEVA.reemplazo);
					PREGUNTANUEVA.enunciado = PreguntaBase.html();
					PREGUNTANUEVA.enunciadoHeight = pregunta.enunciadoHeight;
					//log('El texto de reemplazo es '+PREGUNTANUEVA.reemplazo);
					log('Enunciado: '+PREGUNTANUEVA.enunciado);
					
					// Generar las alternativas si hay <alternativas>
					PREGUNTANUEVA.alternativas = [];
					PREGUNTANUEVA.alternativasHeight = pregunta.opcionesParaAlternativas.height;
					renderAlternativa=pregunta.opcionesParaAlternativas.renderas; // img,caption o el nombre de un atributo
					if (renderAlternativa==null) renderAlternativa="caption";
					
					// Revolver las alternativas.
					shuffleArray(Subconjunto);
	
					for(var i=0;i<Subconjunto.length;i++) {
						ALTERNATIVANUEVA = [];
						log('Generando alternativa '+(i+1)+'/'+N+', correcta es:'+laCorrecta+' para renderizar uso '+renderAlternativa);
						escogida = MEMORIA.recursos.find(item => {return item.id==arr[Subconjunto[i]].id});
						//ALTERNATIVANUEVA.puntaje = (i==laCorrecta? 1 : 0 ); //indiceCorrecto
						ALTERNATIVANUEVA.puntaje = (Subconjunto[i]==indiceCorrecto? 1 : 0 ); //indiceCorrecto
						if (ALTERNATIVANUEVA.puntaje>0) {
							log('Puntaje de '+i+' v/s '+laCorrecta+' quedo en '+ALTERNATIVANUEVA.puntaje);
							console.log(escogida);
						}
						ALTERNATIVANUEVA.indice = Subconjunto[i];
						switch (renderAlternativa) {
							case "img" : ALTERNATIVANUEVA.render=escogida.resuelto; break;
							case "caption" : ALTERNATIVANUEVA.render=escogida.caption; break;
							default: // Para tipo=
								// en renderAlternativa viene un nombre de atributo
								ALTERNATIVANUEVA.render = escogida[renderAlternativa];
								break;
						}
						//ALTERNATIVANUEVA.render=renderas=='caption'?escogida.resuelto:escogida.caption;
						PREGUNTANUEVA.alternativas.push(ALTERNATIVANUEVA);
					}
					respuesta.push(PREGUNTANUEVA);
				}
				else {
					log('No se sabe el tipo de pregunta (alternativas/responder)');
				}
			}
		} else { // Pregunta no es repetible
			console.log("PROCESAR PREGUNTA...");
			PREGUNTANUEVA = {};
			PreguntaBase = $('<x>'+pregunta.html+'</x>');
			PreguntaBase.find("recurso").each(function() {
				itemEnPool=MEMORIA.recursos.find( item => {return item.id==$(this).attr("id");} );
				resuelto=itemEnPool.resuelto;
			  $(this).replaceWith(resuelto);
			});
			expandido=resolverRecurso(pregunta.enunciado);
			console.log('**EXPANDIDO**');
			console.log(expandido);
			PREGUNTANUEVA.enunciado=PreguntaBase.html();
			PREGUNTANUEVA.enunciadoHeight = pregunta.enunciadoHeight;
			PREGUNTANUEVA.alternativas=[];
			PREGUNTANUEVA.alternativasHeight = complementa(pregunta.enunciadoHeight);
			PREGUNTANUEVA.tipo="alternativas"; // todo: no siempre es alternativas
			for (var i=0;i<pregunta.alternativas.length;i++) {
				log('VER LAS ALTERNATIVAS EN...',pregunta.alternativas[i]);
				ALTERNATIVANUEVA = {};
				//expandido=recurso_expandir($('<x>'+pregunta.alternativas[i].html+'</x>'),null); // sin global
				ALTERNATIVANUEVA.puntaje = pregunta.alternativas[i].puntaje;
				ALTERNATIVANUEVA.indice = i;
				Resuelto=resolverRecurso(pregunta.alternativas[i].html);
				if (typeof Resuelto=='string')
					newrender=Resuelto;
				else	
					newrender=Resuelto;
				log('Se determino newrender========>',newrender);
				ALTERNATIVANUEVA.render=newrender; 
				if (ALTERNATIVANUEVA.puntaje>0) PREGUNTANUEVA.correcta=i;
				PREGUNTANUEVA.alternativas.push(ALTERNATIVANUEVA);
			}
			respuesta.push(PREGUNTANUEVA);
		}
	});
	
	return respuesta;
}

// Generador de un número entre min y max
function getValor(minimo,maximo,spec) {
	if (spec=="@random") {
		aux = minimo + Math.floor(Math.random()*(maximo-minimo+1));
		return aux;
	}
	else
		return null;
}

/*
enunciado es un trozo xml para <enunciado>...</enunciado>. Puede contener <recurso> y <numero>.

Devuelve expansion con reemplazos para <recurso> y <numero>

todo: por ahora solo <numero>. Falta <recurso>.
*/
function resolverEnunciadoNumerico(enunciado,variables) {
	$enunciado = $("<x>" + enunciado + "</x>");
	$enunciado.find("numero").each(function() {
		itemEnPool=variables.find( item => {return item.id==$(this).attr("id");} );
		resuelto=itemEnPool.valor;
	  $(this).replaceWith(resuelto);
	});
	return $enunciado.html();
}

// Entrega el 100%-xx% porcentaje de algo
function complementa(porcentaje) {
	// Todo. Ver los casos cuando no es %
	s=Number(porcentaje.replace("%",""));
	if (isNaN(s))
		return ""; // Ante cualquier error va a quedar vacio
	else	
		return (100-s)+"%";
}

/*
Dada una expresion algebraica, reemplaza las variables {xx} y evalua.
Devuelve el valor numerico de la expresion.
Caso: expresion={A}*{B}
*/
function resolverExpresionNumerica(expresion,variables) {
	match=expresion.match(/({[A-Z|0-9|_]*})/gi);
	// en match quedan las porciones {xx} a reemplazar
	
	new_expr=expresion;
	match.forEach(function(item) { // Para cada {xx}
		// Obtener la parte dentro de las {}, que es el id de la variable ==> xx
		id = item.substr(1,item.length-2);
		// Buscar la variable con ese id==>  variables.id==xx
		variable = variables.find(v=>{return v.id==id});

		// Preparar regexp para buscar todas las {xx} en la expresion.		
		var re=new RegExp(item,'g');
		
		// Cambiar {xx} por variable.valor
		new_expr=new_expr.replace(re,variable.valor);
		//console.log('Queda '+new_expr);
	});
	
	// Evaluar numericamente
	return eval(new_expr);
}

/* Despues de determinar los objetos MEMORIA y FULLTEST, y luego de repartidos en la GUI,
se llama a esta función para presentar la pagina #about ocultando todas las demas.
*/
function presentarTest() {
	$("#about").show();
	$("#testarea").hide();
	$("#progress").hide();
	revisando=0; // Cuantas veces ha apretado el botón evaluar. 
}

/* Resuelve estos casos
 <recurso id="inti" ... />
*/ 
function resolverRecurso($recurso) {
	
	log('\n\nResolverRecurso()',$recurso);
	// Resolver <recurso id="inti" />
	if (typeof $r=="undefined") $r=$recurso; // Guardar el primero para debug
	
	$analizar=$recurso;	
	envuelto=false; // Si es un string se envolverá en <x>...</x>
	if (typeof $recurso=="string") {
		envuelto=true;
		$analizar=$("<x>"+$recurso+"</x>");
		log("El recurso es un string");
	} else
		log("El recurso es un jQuery");
	
	log('====>Analizar',$analizar); // falta el replaceWith

	$analizar.find("recurso").each(function() {
		itemEnPool=MEMORIA.recursos.find( item => {return item.id==$(this).attr("id");} );
		resuelto=itemEnPool.resuelto;
	  $(this).replaceWith(resuelto);
	});
	
	log('  >> RESULTA',$analizar[0].innerHTML);
	if (envuelto)
		return $analizar.html(); // Sin el <x>...</x> que se uso para envolver
	else
		return $analizar;	

}

/* Agrega todos los recursos img, en forma desparramada, en el área de about 
Se puede evitarveste comportamiento con <about mosaico="no">
*/
function agregarFotosAlAbout() {
	if (MEMORIA.about.attr("mosaico")=="no") return;
	$("#about").append(canvas=$("<div />"));
	canvas.css("position","absolute");
	maximo=20;
	WW=$(window).width();
	MEMORIA.recursos.forEach(function(item) {
		maximo--;
		if (maximo<0) return;
		
		if (item.tipo=="img") {
			xsize= 100 + Math.floor(Math.random()*50); //Entre 100 y 150
			xleft=Math.floor(Math.random()*WW*0.6); //Entre 0 y 70%
			xtop =30+Math.floor(Math.random()*WW*0.5);  //Entre 0 y 90%
			xrot =-15 + Math.floor(Math.random()*30);  //Entre -20 y 20%

			im=$("<img />").attr("src",item.imgurl).css("position","absolute");
			im.css("height",xsize+"px");
			im.css("left",xleft+"px");
			im.css("top",xtop+"px");
			//im.css("transform","rotate("+xrot+"deg)");
			im.css("border","1px solid gray");
			canvas.append(im);
		}
	});
}

/* deprecado ??? */
function definirEventos() {
	return;
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

</script>
		
</head>
<body>
	<form id="form_volver" target="" method="post" action="gocat.php">
		<input type="hidden" name="categoria" id="categoria" value="<?=$categoria?>" />
	</form>
	<div id="container">
		<h1>
			<a id="volver" href="javascript:form_volver.submit();">
				<img border="0" src="img/atras.png" height="70%" align="absmiddle" /></a>
			&nbsp;|&nbsp;
			<?= substr(basename($archivo),0,-4) ?>
			<!--<a style='float:right;' target="_new" href="<?=$archivo?>">Xml</a>-->
			<div id="autoavanzar">
				<input type="checkbox" id="chk_autoavanzar" checked="checked" />
				<span id="msg_autoavanzar">Auto-avanzar</span>
			</div>
			<div id="logros">
				<table cellpadding="2" cellspacing="0">
					<tr>
						<td>#</td>
						<td id="ibart"><img src="img/bart.jpg" height="32" /></td>
						<td id="isanta"><img src="img/santa.png" height="32" /></td>
					</tr>
					<tr>
						<td id="lugar_intentos">0</td>
						<td id="lugar_bart">-</td>
						<td id="lugar_santa">-</td>
					</tr>	
				</table>	
			</div>
			</h1>
	
		<div id="about">
			<div id="btn_comenzar">Comenzar &gt;&gt;</div>
		</div>
		
	
		<div id="progress"></div>
		<div id="testarea">
			<div id="evaluacion">
				<div id="estadisticas"></div>
				<center>
					<img id="btn_evaluar" src="img/evaluar.png" />
				</center>
				<div id="resultados"></div>
			</div>
		</div>
		
		<div id="aviso"></div>

		<div id="footer"></div>	
</div>

	<script type="text/javascript">
		//$("h1").fadeOut();
		$("#aviso").hide();
		traerArchivo(xmlfile);
		leerPremios('test/'+categoria+'/'+testname);
		setTimeout(function(){
			log('Termine el timeout para '+xmlfile);
			//traerArchivo(xmlfile);
		},1000);
		footerPut();
</script>
</body>
<!-- TODO:
X * Auto avanzar luego de responder
X   |___ Poner un Timeout: Relojito + que se alcance a ver la respuesta + sacar relojito + avanzar.
X * Mostrar la alternativa escogida actualmente al navegar por las preguntas. Actualmente se sabe que esta respondida pero no se muestra cual.
X * Mejorar la seleccion random de preguntas. Que no se repitan.
X * Agregar en el render de <recurso> el atributo title. Mostrar la imagen y el title. Aparte existe el Caption en la dualidad imagen/respuesta.
X * Agregar en el render de <recurso> un atributo cualquiera.
X * En las imagenes se usa el caption, revelando la respuesta correcta. Cambiarlo por el title.
X * Agregar pantalla About con un botón comenzar.
X * Agregar pantalla Resultados con el puntaje obtenido
  * Mecanismo de identificacion de jugador y almacenamiento de historial.
  * Mecanismo de premios (estrellas, diamantes y esas cosas). 
- * Opcion para evaluar inmediatamente luego de responder, o evaluar todo el conjunto al final.
- * Opcion estudio, que muestre las alternativas correctas.
  * Confirmación ante abandono durante test incompleto.
X * Asunto acentos, eñes y ¿¿¿   (OK: era encode="iso-8859-1" en vez de UTF-8)
  * Despues de evaluar: 
    X los botones izq/der solo avanzan dentro de las preguntas erroneas y no contestadas
    + en caso de 100% hacer algo: felicitaciones, fireworks, etc.
X * En el about incluir las fotos en los recursos, tirandolas todas en desorden.
-->
</html>