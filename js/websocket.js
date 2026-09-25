/**
 * *       Programa para recibir peticiones de archivos por firmar, sirve para consultar el estado del documento si está firmado o no.
 * *       Código basado de: https://www.flynsarmy.com/2012/02/php-websocket-chat-application-2-0/
 * *       Desarrollado y modificado por la Subsecretaría de Gobierno Electrónico del Ecuador
 * *------------------------------------------------------------------------------
 * *    This program is free software: you can redistribute it and/or modify
 * *    it under the terms of the GNU Affero General Public License as
 * *    published by the Free Software Foundation, either version 3 of the
 * *    License, or (at your option) any later version.
 * *    This program is distributed in the hope that it will be useful,
 * *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 * *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * *    GNU Affero General Public License for more details.
 * *
 * *    You should have received a copy of the GNU Affero General Public License
 * *    along with this program.  If not, see http://www.gnu.org/licenses. 
 * *------------------------------------------------------------------------------
 * **/
/**
 * *       Modificado por          Iniciales               Fecha (dd/mm/aaaa)
 * *       David Gamboa            DG                      15-11-2017
 * *       josedavo@gmail.com
 * *
 * *       Comentado por           Iniciales               Fecha (dd/mm/aaaa)
 * *       David Gamboa            DG                      15-11-2017
 * **/

var Server;//servidor de conexion que interactua con Quipux
var radicadosGlobal;
var intentos;
var resultado;
/*Funcion que permite enviar mensajes al cliente*/
function log(text) {
	$log = $('#log');
	texto = text;

	res = texto.split('::');

	if (res[0] == 0) {
		docnofirmados = res[1];
		docfirmados = res[2];

		intentos++;
		if (intentos >= 5) {
			send('Cerrar conexion');
			$('#log').text('');
			resultado = "<font color='red'>Favor intente nuevamente.<font><br> <font color='blue'> Documentos firmados (" + docfirmados + ") </font> <br> <font color='red'> Documentos no firmados (" + docnofirmados + ").</i></font>";
			$log.append(($log.val() ? "\n" : '') + resultado);
			document.getElementById('div_link_token').style.display = '';
		} else {
			send(radicadosGlobal + ',1');
			$('#log').text('');
			verificando = "<font><i>Verificando Documento(s), documentos firmados (" + docfirmados + "), por firmar (" + docnofirmados + "), espere unos segundos...</i></font>";
			$log.append(($log.val() ? "\n" : '') + verificando);
		}
	}
	else {
		numero = text.length;
		if (numero == 2 || numero == 1) {
			resultado = "<font  color='blue' size='2'><i>Se ha firmado el/los documento(s) satisfactoriamente. <br> Documentos firmados (" + text + ")</i></font>";
			$('#log').text('');
			$log.append(($log.val() ? "\n" : '') + resultado);
			document.getElementById('div_link_token').style.display = 'none';
		}
		else {
			$('#log').text('');
			$log.append(($log.val() ? "\n" : '') + text);
		}

	}//else
	$log[0].scrollTop = $log[0].scrollHeight - $log[0].clientHeight;
}
/*Envia mensajes al cliente*/
function send(text) {
	Server.send('message', text);
}

/*Abre la comunicación*/
function abrirSocket(text) {
	//log('Conneictandose con firma electrónica...');
	Server = new FancyWebSocket('ws://websocket.gestiondocumental.gob.ec:8080');

	//Let the user know we're connected
	Server.bind('open', function () {
		log("Iniciando aplicativo de firma.");//conectado
		send(text);
	});

	//OH NOES! Disconnection occurred.
	Server.bind('close', function (data) {
		//log( "Cerrando la aplicación de firma." );//desconectado
		log(resultado);
	});

	//Log any messages sent from server
	Server.bind('message', function (payload) {
		log(payload);
	});

	Server.connect();
}

/*Propio de Quipux para abrir la conexión con la aplicación de firma*/
function token(tokencer, tipo_certificado, radicados, api_key_token) {
	//      url = 'firmaec://quipux/firmar?token='+tokencer+'&tipo_certificado='+tipo_certificado+'&llx=222&lly=85&urx=422&ury=49';
	//	url = 'firmaec://quipux/firmar?token='+tokencer+'&tipo_certificado='+tipo_certificado+'&llx=222&lly=85&urx=422&ury=49&url=https%3A%2F%2Ffirmadigital-pruebas.ucuenca.edu.ec%2Fapi';

	//url = 'firmaec://quipux/firmar?token='+tokencer+'&tipo_certificado='+tipo_certificado+'&llx=222&lly=85&urx=422&ury=49';
	// url = 'firmaec://quipux/firmar?token='+tokencer+'&tipo_certificado='+tipo_certificado+'&llx=222&lly=85&urx=422&ury=49&url=https%3A%2F%2Ffirmadigital.ucuenca.edu.ec%2Fapi'; //ESTE VALE, ANTERIOR
	url = 'firmaec://quipux/firmar?token=' + tokencer +
		'&tipo_certificado=' + tipo_certificado +
		'&llx=222&lly=85&urx=422&ury=49' +
		'&url=http%3A%2F%2F127.0.0.1%3A8085%2Fservicio';
	//url = 'firmaec://quipux/firmar?token='+tokencer+'&tipo_certificado='+tipo_certificado+'&llx=222&lly=85&urx=422&ury=49&url=http%3A%2F%2F172.16.1.76%3A8090%2Fapi';
	//      alert(url);

	windowFirma = window.open(url, 'Firma Electrónica', 'addressbar=no,toolbar=0,scrollbars=0,location=no,statusbar=0,menubar=0,resizable=0,width=500px,height=250px,left = 390,top = 100');
	setTimeout("windowFirma.close()", 6500);
	radicadosGlobal = radicados;
	intentos = 1;
}
