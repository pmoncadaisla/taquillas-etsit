<?php

/***********************************************************************************
/*
/* Sistema de taquillas ETSIT UPM
/* @author Pablo Moncada Isla pmoncadaisla@gmail.com
/* @version 09/2013
/*
/***********************************************************************************/

ini_set('log_errors', true);
ini_set('error_log', dirname(__FILE__).'/taquillas.functions_errors.log');


function getCursoAnterior(){ return "2012"; }
function getCursoActual(){ return "2013"; }

require_once("bbdd.php");


function isTaquillaAlquilada($numero){
	$cursoAnterior = getCursoAnterior();
	$numero = intval($numero);
	
	$query = "SELECT COUNT(id) as count FROM operaciones WHERE taquilla='$numero' AND curso='$cursoAnterior' AND pagado='1' ";
	
	$doQuery = mysql_query($query);	
	$fetch = mysql_fetch_array($doQuery);
	
	if($fetch['count'] == 1)
		return true;
	else{
		error_log("La taquilla $numero no esta alquilada actualmente");
		false;
	}
}

function isTaquillaVacia($taquilla,$curso){

	$taquilla = intval($taquilla);
	$curso = intval($curso);
	
	$query = "SELECT COUNT(id) as count FROM operaciones WHERE taquilla='$taquilla' AND curso='$curso' ";
	
	$doQuery = mysql_query($query);	
	$fetch = mysql_fetch_array($doQuery);
	
	if($fetch['count'] == 1)
		return false;
	else
		return true;
	

}



function isArrendatario($numero,$arrendatario){
	$numero = intval($numero);
	$operacion = getOperacion($numero,getCursoAnterior());
	
	if(checkEmail($arrendatario)){
		$email = mysql_real_escape_string($arrendatario);
		$query = "SELECT id FROM personas WHERE email='$email' AND operacion='$operacion'";
		$doQuery = mysql_query($query);
		
		if(mysql_num_rows($doQuery) > 0)
			return true;		
	
	}else if(ctype_digit($arrendatario)){
		$dni = mysql_real_escape_string($arrendatario);
		$query = "SELECT id FROM personas WHERE dni='$dni' AND operacion='$operacion'";
				
		$doQuery = mysql_query($query);
		
		if(mysql_num_rows($doQuery) > 0)
			return true;
	}
	else{
		error_log("No es arrendatario: $arrendatario valido para taquilla $numero");
		return false;
	}    

}

function getOperacion($numero,$curso){
	$numero = intval($numero);
	$curso = intval($curso);
	
	$query = "SELECT id FROM operaciones WHERE taquilla='$numero' AND curso='$curso'";
	$doQuery = mysql_query($query);
	
	if(mysql_num_rows($doQuery) == 0)
		return false;
	
	$fetch = mysql_fetch_array($doQuery);
	
	return intval($fetch['id']);
}

function getOperaciones($numero){
	$numero = intval($numero);
	
	$query = "SELECT id,curso,tipo,timestamp FROM operaciones WHERE taquilla='$numero' ORDER BY id DESC";
	$doQuery = mysql_query($query);
	
	if(mysql_num_rows($doQuery) == 0)
		return false;
		
	
	while($fetch = mysql_fetch_array($doQuery))
		$operaciones[] = $fetch;
		
	return $operaciones;
	
}

function isOperacionPagada($operacion){
	$operacion = intval($operacion);
	$query = "SELECT pagado FROM operaciones WHERE id='$operacion'";
	$doQuery= mysql_query($query);
	
	$fetch = mysql_fetch_array($doQuery);
	if(intval($fetch['pagado']) == 1)
		return true;
	else
		return false;
	
}

function checkEmail($email) {
	$regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';
	if (preg_match($regex, $email))
		return true;
	else
		return false;
}

function txn_idExists($txn_id){
	$txn_id = mysql_real_escape_string($txn_id);
	$query = "SELECT id FROM operaciones WHERE txn_id='$txn_id'";
	if(mysql_num_rows(mysql_query($query)) == 0)
		return false;
	else{
		error_log("Ya existe el txn_id en la base de datos");
		return true;
	}
}

function isValidPersonasId($personasId,$taquilla){	
	$id = intval($personasId);
	$taquilla = intval($taquilla);
	$query = "SELECT id FROM json_personas WHERE id='$id' AND taquilla='$taquilla'";
	if(mysql_num_rows(mysql_query($query)) == 0)
		return false;
	else{
		error_log("No existe dicho personasId");
		return true;
	}
}

function isRenovacionPaypalValida($txn_id,$taquilla,$arrendatario){
	if(isTaquillaAlquilada($taquilla) && isArrendatario($taquilla,$arrendatario) && !txn_idExists($txn_id))
		return true;
	else
		return false;
	
}

function isCambioPaypalValido($txn_id,$nueva,$antigua,$arrendatario){
	if(isTaquillaAlquilada($antigua) && isArrendatario($antigua,$arrendatario) && !txn_idExists($txn_id))
		return true;
	else
		return false;
	
	
}

function isNuevaPaypalValida($txn_id,$taquilla,$personasId){
	if(!isTaquillaRenovada($taquilla) && !txn_idExists($txn_id) && isValidPersonasId($personasId,$taquilla))
		return true;
	else
		return false;
}

function isNuevaIngresoValida($taquilla,$personasId){
	if(!isTaquillaRenovada($taquilla) && isValidPersonasId($personasId,$taquilla))
		return true;
	else
		return false;
}

function isRenovacionIngresoValida($taquilla,$arrendatario){
	if(isTaquillaAlquilada($taquilla) && isArrendatario($taquilla,$arrendatario) && !getOperacion($taquilla,getCursoActual()))
		return true;
	else
		return false;
}

function isCambioIngresoValido($nueva,$antigua,$arrendatario){
	if(isTaquillaAlquilada($antigua) && isArrendatario($antigua,$arrendatario) && !getOperacion($antigua,getCursoActual()))
		return true;
	else
		return false;
}

function getArrendatarios($taquilla,$curso){
	$operacion = getOperacion($taquilla,$curso);	
	$query = "SELECT * FROM personas WHERE operacion='$operacion'";
	$doQuery = mysql_query($query);
	$personas = array();
	while($fetch = mysql_fetch_array($doQuery))
		$personas[] = $fetch;
		
	return $personas;
}

function getTaquilla($operacion){
	$operacion = intval($operacion);
	$query = "SELECT taquilla FROM operaciones WHERE id='$operacion'";
	$doQuery = mysql_query($query);
	if(!$doQuery)
		error_log("Error getTaquilla($operacion): $query");
	$fetch = mysql_fetch_array($doQuery);
	
	return $fetch['taquilla'];
}

function isTaquillaRenovada($numero){
	$numero = intval($numero);
	$curso = getCursoActual();
	$operacion = getOperacion($numero,$curso);
	if(!$operacion)
		return false;
	
	//return isOperacionPagada($operacion);
	
	return true;
	
}


function insertaPersonas($personas,$operacion){

	$operacion = intval($operacion);
	$taquilla = getTaquilla($operacion);
	$correcto = true;
	foreach($personas as $persona){
		$nombre = utf8_encode($persona['nombre']);
		$apellidos = utf8_encode($persona['apellidos']);
		$dni = $persona['dni'];
		$telefono = $persona['telefono'];
		$email = $persona['email'];
		
		$query = "INSERT INTO personas (nombre,apellidos,dni,telefono,email,taquilla,operacion)
			VALUES ('$nombre','$apellidos','$dni','$telefono','$email','$taquilla','$operacion')";
		mysql_set_charset('utf8'); 
		$doQuery = mysql_query($query);		
		if(!$doQuery){
			$correcto = false;
			error_log("ERROR SQL: $query");
		}
	
	}
	return $correcto;
}

function mandarCorreos($taquilla,$curso,$asunto,$mensaje,$cc=false){
	$personas = getArrendatarios($taquilla,$curso);
	$destinatarios = "";
	foreach($personas as $key => $persona){
		$destinatarios .= $persona['email'].", ";
	}
	$mensaje = str_replace("\n","<br/>",$mensaje);
	$cabeceras  = 'MIME-Version: 1.0' . "\r\n";
	$cabeceras .= 'Content-type: text/html; charset=utf-8' . "\r\n";
	$cabeceras .= 'From: Taquillas <taquillas@dat.etsit.upm.es>' . "\r\n";
	if($cc)	$cabeceras .= 'Cc: taquillas@dat.etsit.upm.es' . "\r\n";
	$asunto = "[Taquillas DAT] ".$asunto;
	$mensaje .= "<p>--<br/>";
	$mensaje .= utf8_encode("<p>Este es un mensaje acerca de la taquilla n�mero <b>$taquilla</b> con la que tienes alguna relaci�n durante el curso <b>$curso</b></p>");
	return mail($destinatarios, $asunto, $mensaje, $cabeceras);
	
}

function renovarPaypal($txn_id,$taquilla,$arrendatario){
	error_log("[INFO] renovar $txn_id,$taquilla,$arrendatario");
	$curso = getCursoActual();
	if(isRenovacionPaypalValida($txn_id,$taquilla,$arrendatario)){
		$query = "INSERT INTO operaciones (curso,pagado,taquilla,tipo,txn_id)
			VALUES ('$curso','1','$taquilla','renovacion','$txn_id')";
		$doQuery = mysql_query($query);
		$operacion = mysql_insert_id();
		
		if(!$doQuery){
			error_log("Error SQL: $query");
			return false;
		}
		
		
		$personas = getArrendatarios($taquilla,getCursoAnterior());
		$inserta = insertaPersonas($personas,$operacion);
		if(!$inserta)
			return false;
		
		return true;
	
	}else{
		error_log("No cumple requisitos de validacion PayPal");
		return false;
	}

}

function cambiarPaypal($txn_id,$nueva,$antigua,$arrendatario){
	error_log("[INFO] cambiar $txn_id,$nueva,$antigua,$arrendatario");
	$curso = getCursoActual();
	if(isCambioPaypalValido($txn_id,$nueva,$antigua,$arrendatario)){
		$query = "INSERT INTO operaciones (curso,pagado,taquilla,tipo,txn_id)
			VALUES ('$curso','1','$nueva','cambio','$txn_id')";
		$doQuery = mysql_query($query);
		$operacion = mysql_insert_id();
		
		if(!$doQuery){
			error_log("Error SQL: $query");
			return false;
		}
		
		
		$personas = getArrendatarios($antigua,getCursoAnterior());
		$inserta = insertaPersonas($personas,$operacion);
		if(!$inserta)
			return false;
		
		insertarCambio($nueva,$antigua,getCursoActual(),$operacion);
		return true;
	
	}else{
		error_log("No cumple requisitos de validacion PayPal");
		return false;
	}

}


function nuevaPaypal($txn_id,$taquilla,$personasId){
	error_log("[INFO] renovar $txn_id,$taquilla,$personasId");
	$curso = getCursoActual();
	if(isNuevaPaypalValida($txn_id,$taquilla,$personasId)){
		$query = "INSERT INTO operaciones (curso,pagado,taquilla,tipo,txn_id)
			VALUES ('$curso','1','$taquilla','nueva','$txn_id')";
		$doQuery = mysql_query($query);
		$operacion = mysql_insert_id();
		
		if(!$doQuery){
			error_log("Error SQL: $query");
			return false;
		}		

		$json_personas = get_json_personas($personasId);
		$personas = json_personas2array($json_personas);

		
		$inserta = insertaPersonas($personas,$operacion);
		if(!$inserta)
			return false;
		
		return true;
	
	}else{
		error_log("No cumple requisitos de validacion PayPal");
		return false;
	}

}

function get_info_taquilla($taquilla,$curso){
	$operacion = getOperacion($taquilla,$curso);
	$arrendatarios = getArrendatarios($taquilla,$curso);
	if($operacion)
		$pagada = isOperacionPagada($operacion);
	else
		$pagada = false;
	
	$datos['curso'] = $curso;
	$datos['operacion'] = $operacion;
	$datos['arrendatarios'] = $arrendatarios;
	$datos['pagada'] = $pagada;
	$datos['tipo'] = getTipoOperacion($operacion);
	$datos['zona'] = getZonaTaquilla($taquilla);
	
	
	return $datos;

}

function getZonaTaquilla($taquilla){
	$query = "SELECT zona FROM taquillas WHERE numero='$taquilla'";
	$doQuery = mysql_query($query);	
	$fetch = mysql_fetch_array($doQuery);
	return $fetch['zona'];
}

function getTipoOperacion($operacion){
	$operacion = intval($operacion);
	$query = "SELECT tipo FROM operaciones WHERE id='$operacion'";
	$doQuery = mysql_query($query);	
	$fetch = mysql_fetch_array($doQuery);
	return $fetch['tipo'];
}

function get_taquillas_alquiladas($curso, $zona = false){
	$curso = intval($curso);
	$query = "SELECT o.id, o.taquilla, o.pagado, o.tipo, o.timestamp, t.zona FROM operaciones as o, taquillas as t WHERE o.curso='$curso' AND t.numero = o.taquilla;";
	if($zona) $query.= " AND t.zona = '".intval($zona)."'"; 
		
	$taquillas = array();
	$doQuery = mysql_query($query);
	
	while($fetch = mysql_fetch_array($doQuery))
		$taquillas[] = $fetch;
		
	return $taquillas;

}

function get_taquillas_no_alquiladas($curso, $zona = false){
	$curso = intval($curso);
	$query = "SELECT * FROM taquillas as t WHERE t.numero  NOT IN (SELECT taquilla FROM operaciones WHERE curso='$curso');";
	
	if($zona) $query.= " AND t.zona = '".intval($zona)."'"; 
		
	$taquillas = array();
	$doQuery = mysql_query($query);
	
	while($fetch = mysql_fetch_array($doQuery))
		$taquillas[] = $fetch;
		
	return $taquillas;
}

function get_taquillas_canceladas($curso){
	$curso = intval($curso);
	$query = "SELECT taquilla FROM cancelaciones WHERE curso='$curso';";
	
	$taquillas = array();
	
	$doQuery = mysql_query($query);
	while($fetch = mysql_fetch_array($doQuery))
		$taquillas[]['taquilla'] = $fetch['taquilla'];
	
		
	return $taquillas;
}

function get_taquillas_alquilables($curso, $zona = false){
	$curso = intval($curso);
	$time = time() - 60*30;
	$query = "SELECT * FROM taquillas as t WHERE t.numero  NOT IN (SELECT taquilla FROM operaciones WHERE curso='$curso') AND t.numero  NOT IN (SELECT taquilla FROM bloqueadas WHERE time > $time) ";
	
	if($zona) $query.= " AND t.zona = '".intval($zona)."'";
			
	$taquillas = array();
	$doQuery = mysql_query($query);
	
	while($fetch = mysql_fetch_array($doQuery))
		$taquillas[] = $fetch;
		
	return $taquillas;
}


/**
/* Devuelve todas las taquillas que estuvieron alquiladas el a�o pasado pero este a�o no han sido renovadas.
/* Incluye las canceladas y las que son por ingreso bancario y aun no se han pagado.
/* @return $taquillas Las taquillas solicitadas
*/
function get_taquillas_no_renovadas($curso, $zona = false){
	$curso = intval($curso);
	$anterior = $curso-1;
	$query = "SELECT *, t.numero as taquilla FROM taquillas as t WHERE t.numero IN (SELECT taquilla FROM operaciones WHERE curso='$anterior') AND t.numero NOT IN (SELECT taquilla FROM operaciones WHERE curso='$curso' AND pagado='1')";
	
	if($zona) $query.= " AND t.zona = '".intval($zona)."'";
	
	$taquillas = array();
	$doQuery = mysql_query($query);
	
	while($fetch = mysql_fetch_array($doQuery))
		$taquillas[] = $fetch;
		
	return $taquillas;
}

function get_info_operacion($operacion){
	$operacion = intval($operacion);
	$query = "SELECT * FROM operaciones WHERE id='$operacion'";
	$doQuery = mysql_query($query);
	
	return mysql_fetch_array($doQuery);
}
function renovarIngreso($taquilla,$arrendatario){

	error_log("[INFO] reservar $taquilla,$arrendatario");
	$curso = getCursoActual();
	if(isRenovacionIngresoValida($taquilla,$arrendatario)){
	
		$query = "INSERT INTO operaciones (curso,pagado,taquilla,tipo,txn_id)
			VALUES ('$curso','0','$taquilla','renovacion','Ingreso_$taquilla')";
		$doQuery = mysql_query($query);
		$operacion = mysql_insert_id();
		
		if(!$doQuery){
			error_log("Error SQL: $query");
			return false;
		}		
		
		$personas = getArrendatarios($taquilla,getCursoAnterior());
		$inserta = insertaPersonas($personas,$operacion);
		if(!$inserta)
			return false;
		
		return true;
	
	}else{
		error_log("No cumple requisitos de validacion de reserva");
		return false;
	}	

}

function cambioIngreso($nueva,$antigua,$arrendatario){

	error_log("[INFO] reservar $nueva,$arrendatario");
	$curso = getCursoActual();
	if(isCambioIngresoValido($nueva,$antigua,$arrendatario)){
	
		$query = "INSERT INTO operaciones (curso,pagado,taquilla,tipo,txn_id)
			VALUES ('$curso','0','$nueva','cambio','Ingreso_$nueva')";
		$doQuery = mysql_query($query);
		$operacion = mysql_insert_id();
		
		if(!$doQuery){
			error_log("Error SQL: $query");
			return false;
		}		
		
		$personas = getArrendatarios($antigua,getCursoAnterior());
		$inserta = insertaPersonas($personas,$operacion);
		if(!$inserta)
			return false;
			
		insertarCambio($nueva,$antigua,getCursoActual(),$operacion);
		return true;
	
	}else{
		error_log("No cumple requisitos de validacion de reserva");
		return false;
	}	

}

function nuevaIngreso($taquilla,$personasId){

	error_log("[INFO] reservar $taquilla,$personasId");
	$curso = getCursoActual();
	if(isNuevaIngresoValida($taquilla,$personasId)){
	
		$query = "INSERT INTO operaciones (curso,pagado,taquilla,tipo,txn_id)
			VALUES ('$curso','0','$taquilla','nueva','Nueva_$taquilla')";
		$doQuery = mysql_query($query);
		$operacion = mysql_insert_id();
		
		if(!$doQuery){
			error_log("Error SQL: $query");
			return false;
		}		
		
		$json_personas = get_json_personas($personasId);
		$personas = json_personas2array($json_personas);
		
		$inserta = insertaPersonas($personas,$operacion);
		if(!$inserta)
			return false;
		
		return true;
	
	}else{
		error_log("No cumple requisitos de validacion de reserva");
		return false;
	}	

}

function registrarCambio($nueva,$antigua,$operacion, $curso){
	$nueva = intval($nueva);
	$antigua = intval($antigua);
	$operacion = intval($operacion);
	
	$query = "INSERT INTO cambios (nueva,antigua,operacion,curso) VALUES ('$nueva','$antigua','$operacion','$curso')";
	
	$doQuery = mysql_query($query);
	
	return $doQuery;
	
}

function get_cursos(){
	$query = "SELECT DISTINCT curso FROM `operaciones` ORDER BY curso DESC";
	$doQuery = mysql_query($query);
	$cursos = array();
	while($fetch = mysql_fetch_array($doQuery)){
		$cursos[] = $fetch['curso'];
	}
	return $cursos;
}

function insert_json_personas($personas,$taquilla){
	$taquilla = intval($taquilla);
	$query = "INSERT INTO json_personas (personas,taquilla) VALUES ('$personas','$taquilla')";
	$doQuery = mysql_query($query);
	return mysql_insert_id();
}

function get_json_personas($id){
	$id = intval($id);
	$query = "SELECT personas FROM json_personas WHERE id='$id'";
	$doQuery = mysql_query($query);
	$fetch = mysql_fetch_array($doQuery);
	return $fetch['personas'];
}

function json_personas2array($json_personas){
	return object2array(json_decode(base64_decode($json_personas)));
}

function object2array($object){
    $return = NULL;
      
    if(is_array($object))
    {
        foreach($object as $key => $value)
            $return[$key] = object2array($value);
    }
    else
    {
        $var = get_object_vars($object);
          
        if($var)
        {
            foreach($var as $key => $value)
                $return[$key] = ($key && !$value) ? NULL : object2array($value);
        }
        else return $object;
    }

    return $return;
}

function isTaquillaBloqueada($taquilla){
	$taquilla = intval($taquilla);
	$time = time();
	$query = "SELECT time FROM bloqueadas WHERE taquilla='$taquilla' AND ";
	$doQuery = mysql_query($query);
	if(mysql_num_rows($doQuery) == 0)
		return false;
	else{
		$fetch = mysql_fetch_array($doQuery);
		if( ($fetch['time'] + 60*30) < $time)
			return false;
		else
			return ($fetch['time'] - $time);
	}
}

function bloquearTaquilla($taquilla){
	require_once("ip.php");
	$taquilla = intval($taquilla);
	$time = time();
	$ip = ip();
	
	if(!isTaquillaBloqueada($taquilla)){
	
		$eliminar = "DELETE FROM bloqueadas WHERE ip='$ip'";
		$doEliminar = mysql_query($eliminar);
		
		$query = "INSERT INTO bloqueadas (taquilla,time,ip) VALUES ('$taquilla','$time','$ip')";
		$doQuery = mysql_query($query);
		if($doQuery)
			return true;
		else
			return false;
	}else{
		return false;	
	}

} 

function isTaquillaCambiada($antigua,$curso){
	$query = "SELECT id FROM cambios WHERE antigua='$antigua' AND curso='$curso'";
	$doQuery = mysql_query($query);
	
	if(mysql_num_rows($doQuery) == 0)
		return false;
	else
		return true;
}

function insertarCambio($nueva,$antigua,$curso,$operacion){
	$query = "INSERT INTO cambios (nueva,antigua,curso,operacion) VALUES ('$nueva','$antigua','$curso','$operacion')";
	$doQuery = mysql_query($query);
	
	if($doQuery)
		return false;
	else
		return true;
	
}

function cancelarTaquilla($taquilla,$arrendatario){
	if(isArrendatario($taquilla,$arrendatario)){
		$curso = getCursoActual();
		$taquilla = intval($taquilla);
		$query = "INSERT INTO cancelaciones (taquilla,curso) VALUES ('$taquilla','$curso')";
		$doQuery = mysql_query($query);
		if($doQuery)
			return true;
		else
			return false;
	}else{
		return false;
	}

}

function emailMasivo($tipo,$curso,$asunto,$mensaje){
	$curso = intval($curso);
	if($tipo == "renovadas"){
		$taquillas = get_taquillas_alquiladas($curso);
		$campo = "taquilla";
	}
	else if($tipo == "cambios"){
		$taquillas = getCambios($curso);
		$campo = "taquilla";
	}
	else if($tipo == "norenovadas"){
		$taquillas = get_taquillas_no_renovadas($curso);
		$campo = "taquilla";
		$curso--;
		
	}
	else if($tipo == "canceladas"){
		$taquillas = get_taquillas_canceladas($curso);
		$campo = "taquilla";
		$curso--;
	}
	else
		return false;
		
	
	
	
	foreach($taquillas as $taquilla){
		$estado = mandarCorreos($taquilla[$campo],$curso,$asunto,$mensaje);
	}
	return $estado;

}

function aviso($mensaje){
	echo '<div class="ui-body ui-body-e"><p>'.$mensaje.'</p></div>';

}

function pagarOperacion($operacion){
	$operacion = intval($operacion);
	if(isOperacionPagada($operacion))
		return false;
	$admin = $_SERVER['PHP_AUTH_USER'];
	
	$query = "UPDATE operaciones SET pagado='1' WHERE id='$operacion'";	
	$query2 = "INSERT INTO pagos_manuales (operacion,admin) VALUES ('$operacion','$admin')";
	
	$doQuery = mysql_query($query);
	$doQuery2 = mysql_query($query2);
	
	return ($doQuery && $doQuery2);	

}

function isTaquillaCancelada($taquilla,$curso){
	$taquilla = intval($taquilla);
	$curso = intval($curso);
	$query = "SELECT id FROM cancelaciones WHERE taquilla='$taquilla' AND curso='$curso'";
	$doQuery = mysql_query($query);
	return (mysql_num_rows($doQuery) > 0);
	
}

function getRazonNoRenovada($taquilla,$curso){
	if(isTaquillaCancelada($taquilla,$curso))
		return "Cancelada";
	if(!isTaquillaRenovada($taquilla))
		return "No renovada";	
	if(!isOperacionPagada(getOperacion($taquilla,$curso)))
		return "Impagada";

}

function getNombreZona($id){
	$query = "SELECT nombre FROM zonas WHERE id='$id'";
	$doQuery = mysql_query($query);	
	$fetch = mysql_fetch_array($doQuery);
	return utf8_encode($fetch['nombre']);
}

function insertarComentario($taquilla,$curso,$comentario,$persistente){
	
	$admin = $_SERVER['PHP_AUTH_USER'];
	if(trim($comentario) == "")
		return false;
	$persistente = intval($persistente);
	
	$query = "INSERT INTO comentarios (taquilla,admin,curso,comentario,persistente) VALUES ('$taquilla','$admin','$curso','$comentario','$persistente')";
	$doQuery = mysql_query($query);
	return $doQuery;	
}

function getComentarios($taquilla,$curso){
	$taquilla = intval($taquilla);
	$curso = intval($curso);
	
	$query = "SELECT admin, timestamp, comentario, persistente FROM comentarios WHERE taquilla='$taquilla' AND ( (curso='$curso') OR (persistente='1') )";
	$doQuery = mysql_query($query);
	
	if(mysql_num_rows($doQuery) == 0) return false;
	
	$i = 0;
	while($fetch = mysql_fetch_array($doQuery)){
		$comentarios[$i]['admin'] = $fetch['admin'];
		$comentarios[$i]['timestamp'] = $fetch['timestamp'];
		$comentarios[$i]['comentario'] = $fetch['comentario'];
		$comentarios[$i]['persistente'] = $fetch['persistente'];
		$i++;
	}
	return $comentarios;

}

function getZonas(){
	$query = "SELECT id FROM zonas";
	$doQuery = mysql_query($query);
	while($fetch = mysql_fetch_array($doQuery)){
		$zona[] = $fetch['id'];
	}
	return $zona;
}

function getCambioOrigen($operacion){
	$operacion = intval($operacion);
	$query = "SELECT antigua FROM cambios WHERE operacion='$operacion'";
	$doQuery = mysql_query($query);
	$fetch = mysql_fetch_array($doQuery);
	
	return $fetch['antigua'];

}

function getTareasPendientes(){
	$curso = getCursoActual();
	$query = "SELECT * FROM operaciones WHERE (tipo = 'cambio' OR tipo = 'nueva') AND tarea_realizada = '0' AND curso='$curso' ORDER BY pagado DESC, timestamp ASC";
	$doQuery = mysql_query($query);
	
	while($fetch = mysql_fetch_array($doQuery)){
		$operaciones[] = $fetch;
	}
	return $operaciones;

}

function setTareaRealizada($operacion,$estado){
	$estado = intval($estado);
	$operacion = intval($operacion);
	
	$query = "UPDATE operaciones SET tarea_realizada='$estado' WHERE id='$operacion'";
	$doQuery = mysql_query($query);
	return $doQuery;
	
}

function countTareas(){
	$tareas = getTareasPendientes();
	return count($tareas);
}

function getCancelaciones($fianza = false){
	$curso = getCursoActual();
	$query = "SELECT id, taquilla, curso, fianza_devuelta FROM cancelaciones WHERE curso='$curso'";
	if($fianza) $query .= " AND fianza_devuelta='0'";
	$doQuery = mysql_query($query);
	
	while($fetch = mysql_fetch_array($doQuery)){
		$cancelaciones[] = $fetch;
	}
	return $cancelaciones;

}

function getCancelacion($cancelacion){	
	$query = "SELECT * FROM cancelaciones WHERE id='$cancelacion'";
	$doQuery = mysql_query($query);	
	return mysql_fetch_array($doQuery);	
}

function countCancelaciones(){
	return count(getCancelaciones(true));
}

function setFianzaDevuelta($cancelacion, $quien){
	$estado = intval($estado);
	$cancelacion = intval($cancelacion);
	$time = time();
	
	$query = "UPDATE cancelaciones SET fianza_devuelta='1', quien_recoge='$quien', hora_recogida='$time' WHERE id='$cancelacion'";
	$doQuery = mysql_query($query);
	return $doQuery;
	
}

function getCambios($curso){
	$curso = intval($curso);
	
	$query = "SELECT taquilla FROM operaciones WHERE tipo='cambio' AND curso='$curso'";
	$doQuery = mysql_query($query);
	
	while($fetch = mysql_fetch_array($doQuery)){
		$taquillas[] = $fetch;
	}
	return $taquillas;

}

function imprimir($url){
	
	$connection = ssh2_connect('cuarzo', 22);
	ssh2_auth_password($connection, 'taquillasweb', 'cisneros1029');
	ssh2_exec($connection, 'cd /tmp');
	$stream = ssh2_exec($connection, 'html2ps -r utf-8 "'.$url.'" |lpr');
	stream_set_blocking($stream, true);
  
	// The command may not finish properly if the stream is not read to end
	return stream_get_contents($stream);
	

}

function detectUTF8($string){
        return preg_match('%(?:
        [\xC2-\xDF][\x80-\xBF]        # non-overlong 2-byte
        |\xE0[\xA0-\xBF][\x80-\xBF]               # excluding overlongs
        |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
        |\xED[\x80-\x9F][\x80-\xBF]               # excluding surrogates
        |\xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
        |[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
        |\xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
        )+%xs', $string);
}

function get_utf8($str){
	if(!detectUTF8($str))
		return utf8_encode($str);
	else
		return $str;
	
}



 
 
?>
