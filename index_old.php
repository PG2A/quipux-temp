<!DOCTYPE html>
<html lang="en">
<head>
 <!---->
 <?php
$ruta_raiz = ".";

require_once(__DIR__.'/config/autoload.php');
include_once(__DIR__.'/config.php');
include_once(__DIR__.'/funciones_interfaz.php');
include_once(__DIR__.'/include/db/ConnectionHandler.php');

$db = new ConnectionHandler(__DIR__, '');

if (!$db || !isset($db->conn) || !$db->conn) {
    die("Error: conexión a BD no creada. Revisa config.php/config.local.php y la conectividad con PostgreSQL.");
}
$db->conn->SetFetchMode(ADODB_FETCH_ASSOC);

$implanta = "";
$titulo_implanta = "";
$procedimiento = "";
$titulo_procedimiento = "";
$soporte = "";
$titulo_soporte = "";
            
//Se consulta contenido de catalogos de la pagina Index
$sql = "select * from contenido c
left outer join contenido_tipo  ct
on c.cont_tipo_codi = ct.cont_tipo_codi
where ct.funcionalidad = 'Index'";
$rs = $db->conn->query($sql);
if($rs){
    while (!$rs->EOF) {    
        $cont_tipo_codo = $rs->fields['CONT_TIPO_CODI'];
        switch ($cont_tipo_codo) {
            case "1": 
                $desc_implanta = $rs->fields['TEXTO'];
                $titulo_implanta = $rs->fields['DESCRIPCION']; 
                break;
            case "2": 
                $desc_procedimiento = $rs->fields['TEXTO'];
                $titulo_procedimiento = $rs->fields['DESCRIPCION'];
                break;
            case "3":
                $desc_soporte = $rs->fields['TEXTO'];
                $titulo_soporte = $rs->fields['DESCRIPCION'];
                break;
            default:
                break;
        }   
        $rs->MoveNext();
    }
}


include_once __DIR__."/config_title.php";
 ?>
   <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <link rel="stylesheet" href="estilos/navbar.css">
  
  <script src="js/jquery.min.js"></script>    <!-- jsindex -->
<!--  <script src="js/bootstrap.min.js"></script>-->
  <style>
    /* Add a gray background color and some padding to the footer */
    footer {
      background-color: #f2f2f2;
      padding: 25px;
    }

    .carousel-inner img {
      width: 100%; /* Set width to 100% */
      min-height: 200px;
    }

    /* Hide the carousel text when the screen is less than 600 pixels wide */
    @media (max-width: 600px) {
      .carousel-caption {
        display: none; 
      }
    }
  </style>
  <script type="text/JavaScript">
            function irLogin(admin) {
                try{
                var x = screen.width - 20;
                var y = screen.height - 80;
                var param = "";
                if (admin == 1) param = "?txt_administrador=1";
                ventana=window.open("./login.php"+param,"QUIPUX","toolbar=no,directories=no,menubar=no,status=no,scrollbars=yes, width="+x+", height="+y);
                ventana.focus();
                ventana.moveTo(10, 40);
                }
                catch(e){
                    
                }
            }
        </script>
</head>
<body>

<nav class="navbar navbar-inverse" style="height: 70px">
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>                        
      </button>
     
      
    </div>
    <div class="collapse navbar-collapse" id="myNavbar">
      <ul class="nav navbar-nav">
      
        <li class="active"> <div style="position:absolute; margin-top:-10px" >
      <a class="navbar-brand" href="#"><img src='imagenes/escudo_blanco.png' height='60' width='190'></a>
      </div>
     
      </li>
     
      <div style="position:absolute; margin-left:250px; margin-top:25px" >
      <font color='white'><b><i>Sistema de Gestión Documental</i> </b></font>
        <!--div style="position:absolute; margin-left:120px; margin-top:0px" >
        <font color='white' size='1'><b><i>Versión Libre</i> </b></font>
        </div-->
      </div>
     
     
       
        
      </ul>
      <ul class="nav navbar-nav navbar-right">
       
        <li><a href="javascript: void(0);" onclick="irLogin(1);" style="color: none; margin-top:10px"<span class="glyphicon glyphicon-log-in"></span><font color='white'><b>Ingresar al Sistema </b></font> </a></li>
        
      </ul>
    </div>
  </div>
</nav>

<div class="container">
<div class="row">
  <div class="col-sm-8">
    <div id="myCarousel" class="carousel slide" data-ride="carousel">
      <!-- Indicators -->
      <ol class="carousel-indicators">
        <li data-target="#myCarousel" data-slide-to="0" class="active"></li>
        <li data-target="#myCarousel" data-slide-to="1"></li>
      </ol>

      <!-- Wrapper for slides -->
      <div class="carousel-inner" role="listbox">
        <div class="item active">
          <img src="<?=$banner1?>" alt="Image">
          <div class="carousel-caption">
            
            <p><a href=<?=$linkBanner1?>><?=$nombreLinkBanner1?></a></p>
          </div>      
        </div>

        <!--
	<div class="item">
          <img src="<?=$banner2?>" alt="Image">
          <div class="carousel-caption">
            
          <p><a href=<?=$linkBanner2?>><?=$nombreLinkBanner2?></a></p>
          </div>      
        </div>-->
		
	<!--<div class="item">
          <img src="<?=$banner3?>" alt="Image">
          <div class="carousel-caption">
            
          <p><a href=<?=$linkBanner3?>><?=$nombreLinkBanner3?></a></p>
          </div>      
        </div>-->
		
      </div>

      <!-- Left and right controls -->
      <a class="left carousel-control" href="#myCarousel" role="button" data-slide="prev">
        <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
        <span class="sr-only">Previous</span>
      </a>
      <a class="right carousel-control" href="#myCarousel" role="button" data-slide="next">
        <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
        <span class="sr-only">Next</span>
      </a>
    </div>
  </div>
  <div class="col-sm-4">
    <div class="well">
      <p><b><?=$titulo_implanta?></b></p>
	  <p><?=$desc_implanta?></p>
    </div>
    <div class="well">
       <p><b><?=$titulo_soporte?></b></p>
	  <p><?=$desc_soporte?></p>
    </div>
    <!--div class="well">
       <p>Visit Our Blog</p>
    </div-->
  </div>
</div>
<hr>
</div>

<div class="container text-center">    
	<?php 
	if ($institucionNombre == "institucionNombre" ){
		echo "<font color='red' size='3'>Reemplace el archivo example.config_title.php por config_title.php y configure los nombres de su institución</font>";
	} 
	?>
  <h3 class="azulUcuenca"><?=$institucionNombre?></h3>
  <br>

  <hr>
</div>

<br>

<footer class="container-fluid text-center">
  <p class="text-center" ><?=$footerText?></p>
</footer>

</body>
</html>
