<!DOCTYPE html>
    <script type="text/javascript" src="js/jquery.js"></script>
    <script type="text/javascript" src="js/jquery_tablas.js"></script>
<script>
    
	 function excel(tipo) {
        alert('hola');
		
		if (tipo==1)
            nuevoAjax('div_reporte', 'POST', 'reporte_usuarios_excel.php', 'tipo=xls');
        else
		{
			alert (tipo);	
			nuevoAjax('div_reporte', 'POST', 'reporte_instituciones.php', 'tipo=pdf');
			
		}
            
		
		//
    }
    	
</script>


<body>
       
        <table width='100%'>
		<tr><td>hola mundo!</td></tr>
		<tr><td>
                    <?php
                    echo '<input type="button" name="btn_buscar" class="botones_largo" value="Exportar a XLS" onclick="excel(2);" title="Exporta todas las instituciones">';?>
                    <?php
                    //echo '<input type="button" name="btn_buscar" class="botones_largo" value="Exportar a PDF" onclick="excel(1);" title="Exporta todas las instituciones">';?>
                    
                </td></tr>
        <tr><td><div id='div_reporte' style="width: 99%"></div>
            </td></tr></table>
        <?php 
        //include "reporte_instituciones.php";
        ?>
    </body>
</html>
