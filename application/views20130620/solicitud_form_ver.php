<?php
$this->load->view("header");
function nombre_tipo($tipo, $sug=''){
switch($tipo){
	case 'MAGISTRAL':
		$nombre_tipo = $sug=='' ? ' Magistral' : ' Complementaria';
		break;
	case 'COMPLEMENTARIA':
		$nombre_tipo = $sug=='' ? ' Complementaria' : ' Magistral';
		break;
	default:
	   $nombre_tipo = '';
	}
	return $nombre_tipo;
}

if(substr_count($sol_id, '-') < 2){
	$filas = explode(';',$ordenfilas);
	foreach($filas as $indice=>$fila){
		if(str_replace('-', '', $sol_id)==$fila){		
			$sol_id_anterior = $indice == 0 ? $filas[count($filas) - 1] : $filas[$indice - 1];
			$sol_id_siguiente = $indice == count($filas) - 1 ? $filas[0] : $filas[$indice + 1];		
		}	
	}
	//echo "sol_id $sol_id<br>$ordenfilas - $sol_id $sol_id_anterior - $sol_id_siguiente";
	//echo "<br>ordenfilas $ordenfilas,<br> ordenfilas_paginado $ordenfilas_paginado";	
}
?>
<script type="text/javascript">
function enviar_cancelados(){
   if($('#est_id').val()!='' && $('#com_texto').val()!=''){
	$('#cancelaform').submit();
   }else{
	alert('Debe ingresar un comentario y elegir un estado');	
   }    
}
</script>
<a class="volver" href="<?php echo base_url()?>index.php/solicitud/">Volver</a>
<?php if(substr_count($sol_id, '-') < 2 && $rol_botones!=3){ ?>
<div style="text-align:center">
	<a href="<?php echo base_url()?>index.php/solicitud/comentario/<?php echo $sol_id; ?>">Comentarios</a> | 
	<a href="<?php echo base_url()?>index.php/solicitud/formaestado/<?php echo $sol_id; ?>">Cambiar Estado</a>
	
	<br><a href="<?php echo base_url()?>index.php/solicitud/ver/<?php echo $sol_id_anterior; ?>">Anterior</a> | 
        <a href="<?php echo base_url()?>index.php/solicitud/ver/<?php echo $sol_id_siguiente; ?>">Siguiente</a><br>
</div>
<?php } ?>

<?php if(validation_errors('<p class="error">','</p>')!=''){
echo form_open();
echo str_replace('&lt;', '<', str_replace('&gt;', '>', htmlentities(validation_errors('<p class="error">','</p>'), ENT_NOQUOTES)));
echo form_close();
} ?>
	<form>
	<h1><?php echo $titulo; ?> solicitud</h1>
	<table class='formtable'>
	<tr>
		<td class="tdlabel">Solicitud de  <?php echo $sol_nombre;?> <?php echo $sol_apellido;?></td>
		<td class="tdlabel">C&oacute;digo <?php echo @$sol_uidnumber;?></td>
	</tr>
	<tr>
		<td class="tdlabel">Login :  </td>
		<td><?php echo @$sol_login;?></td>
	</tr>
	<tr>
		<td class="tdlabel">Programa : </td>
		<td><?php echo $prog;?></td>
	</tr>
	<tr>
		<td class="tdlabel">Doble programa : </td>
		<td><?php echo $doble_prog;?></td>
	</tr>
	<tr>
		<td class="tdlabel">Cr&eacute;ditos :  </td>
		<td><?php echo $creditos;?></td>
	</tr>
	<tr>
		<td class="tdlabel">Opci&oacute;n :  </td>
		<td><?php echo $opcion;?></td>
	</tr>
	<tr>
		<td class="tdlabel">SSC :</td>
		<td><?php echo $ssc;?></td>
	</tr>
	<tr>
		<td class="tdlabel">Fecha: </td>
		<td><?php echo $sol_fec_creacion;?></td>
	</tr>	
        <tr>
		<td class="tdlabel">Tipo de Solicitud: </td>
		<td><?php echo $tipo;?></td>	
	</tr>
        <tr>
		<td class="tdlabel">Motivo: </td>
		<td><?php echo $motivo;?></td>	
	</tr>
	<!--<? if(@$sol_mag_crn_ret_des!='') { ?>
	<tr id='crn_mag_1' >
		<td class="tdlabel">CRN (origen) : </td>
		<td><?php echo  @$sol_mag_crn_ret_des;?></td>
	</tr>
	<? } ?>
	<? if(@$sol_mag_crn_ins_des!='') { ?>
	<tr id='crn_mag_2' >
		<td class="tdlabel">CRN (destino): </td>
		<td><?php echo @$sol_mag_crn_ins_des;?></td>
	</tr>
	<? } ?>
	<? if(@$sol_com_crn_ret_des!='') { ?>
	<tr id='crn_com_1' >
		<td class="tdlabel">CRN (complementario origen): </td>
		<td><?php echo @$sol_com_crn_ret_des?></td>
	</tr>
	<? } ?>
	<? if(@$sol_com_crn_ins_des!='') { ?>
	<tr id='crn_com_2' >
		<td class="tdlabel">CRN (complementario destino): </td>
		<td><?php	echo @$sol_com_crn_ins_des; ?></td>
	</tr>
	<? } ?>-->
	<? if(@$sol_ins_des!='') { ?>
	<tr id='crn_mag_1' >
		<td class="tdlabel">CRN Inscripci&oacute;n<?php echo nombre_tipo(@$sol_ins_tipo); ?>: </td>
		<td><?php echo @$sol_ins_crn.' - '. @$sol_ins_des;?></td>
	</tr>
	<? } ?>
	<? if(@$sol_ins_mat!='') { ?>
	<tr>
		<td class="tdlabel">&nbsp;&nbsp;&nbsp;&nbsp;C&oacute;digo: </td>
		<td><?php echo  @$sol_ins_mat;?></td>
	</tr>
	<? } ?>
	<? if(@$sol_ins_seccion!='') { ?>
	<tr>
		<td class="tdlabel">&nbsp;&nbsp;&nbsp;&nbsp;Secci&oacute;n: </td>
		<td><?php echo  @$sol_ins_seccion;?></td>
	</tr>
	<? } ?>
	<? if(@$sol_ins_instructor!='') { ?>
	<tr>
		<td class="tdlabel">&nbsp;&nbsp;&nbsp;&nbsp;Instructor: </td>
		<td><?php echo  @$sol_ins_instructor;?></td>
	</tr>
	<? } ?>
	<? if(@$sol_ins_tipo!='') { ?>
	<tr>
		<td class="tdlabel">&nbsp;&nbsp;&nbsp;&nbsp;Tipo: </td>
		<td><?php echo  @$sol_ins_tipo;?></td>
	</tr>
	<? } ?>
	<? if(@$sol_ret_des!='') { ?>
	<tr id='crn_mag_2' >
		<td class="tdlabel">CRN Retiro<?php echo nombre_tipo(@$sol_ins_tipo); ?>: </td>
		<td><?php echo @$sol_ret_crn.' - '.@$sol_ret_des;?></td>
	</tr>
	<? } ?>
	<? if(@$sol_ret_mat!='') { ?>
	<tr>
		<td class="tdlabel">&nbsp;&nbsp;&nbsp;&nbsp;C&oacute;digo: </td>
		<td><?php echo  @$sol_ret_mat;?></td>
	</tr>
	<? } ?>
	<? if(@$sol_ret_seccion!='') { ?>
	<tr>
		<td class="tdlabel">&nbsp;&nbsp;&nbsp;&nbsp;Secci&oacute;n: </td>
		<td><?php echo  @$sol_ret_seccion;?></td>
	</tr>
	<? } ?>
	<? if(@$sol_ret_instructor!='') { ?>
	<tr>
		<td class="tdlabel">&nbsp;&nbsp;&nbsp;&nbsp;Instructor: </td>
		<td><?php echo  @$sol_ret_instructor;?></td>
	</tr>
	<? } ?>
	<? if(@$sol_ret_tipo!='') { ?>
	<tr>
		<td class="tdlabel">&nbsp;&nbsp;&nbsp;&nbsp;Tipo: </td>
		<td><?php echo  @$sol_ret_tipo;?></td>
	</tr>
	<? } ?>
	<? if(@$sol_sug_ins_des!='') { ?>
	<tr id='crn_com_1' >
		<td class="tdlabel">CRN Sugerencia Inscripci&oacute;n<?php echo nombre_tipo(@$sol_ins_tipo, 'sug'); ?>: </td>
		<td><?php echo @$sol_sug_ins_crn.' - '.@$sol_sug_ins_des;?></td>
	</tr>
	<? } ?>
	<? if(@$sol_sug_ins_mat!='') { ?>
	<tr>
		<td class="tdlabel">&nbsp;&nbsp;&nbsp;&nbsp;C&oacute;digo: </td>
		<td><?php echo  @$sol_sug_ins_mat;?></td>
	</tr>
	<? } ?>
	<? if(@$sol_sug_ins_seccion!='') { ?>
	<tr>
		<td class="tdlabel">&nbsp;&nbsp;&nbsp;&nbsp;Secci&oacute;n: </td>
		<td><?php echo  @$sol_sug_ins_seccion;?></td>
	</tr>
	<? } ?>
	<? if(@$sol_sug_ins_instructor!='') { ?>
	<tr>
		<td class="tdlabel">&nbsp;&nbsp;&nbsp;&nbsp;Instructor: </td>
		<td><?php echo  @$sol_sug_ins_instructor;?></td>
	</tr>
	<? } ?>
	<? if(@$sol_sug_ins_tipo!='') { ?>
	<tr>
		<td class="tdlabel">&nbsp;&nbsp;&nbsp;&nbsp;Tipo: </td>
		<td><?php echo  @$sol_sug_ins_tipo;?></td>
	</tr>
	<? } ?>
	<? if(@$sol_sug_ret_des!='') { ?>
	<tr id='crn_com_2' >
		<td class="tdlabel">CRN Sugerencia Retiro<?php echo nombre_tipo(@$sol_ins_tipo, 'sug'); ?>: </td>
		<td><?php echo @$sol_sug_ret_crn.' - '.@$sol_sug_ret_des; ?></td>
	</tr>
	<? } ?>
	<? if(@$sol_sug_ret_mat!='') { ?>
	<tr>
		<td class="tdlabel">&nbsp;&nbsp;&nbsp;&nbsp;C&oacute;digo: </td>
		<td><?php echo  @$sol_sug_ret_mat;?></td>
	</tr>
	<? } ?>
	<? if(@$sol_sug_ret_seccion!='') { ?>
	<tr>
		<td class="tdlabel">&nbsp;&nbsp;&nbsp;&nbsp;Secci&oacute;n: </td>
		<td><?php echo  @$sol_sug_ret_seccion;?></td>
	</tr>
	<? } ?>
	<? if(@$sol_sug_ret_instructor!='') { ?>
	<tr>
		<td class="tdlabel">&nbsp;&nbsp;&nbsp;&nbsp;Instructor: </td>
		<td><?php echo  @$sol_sug_ret_instructor;?></td>
	</tr>
	<? } ?>
	<? if(@$sol_sug_ret_tipo!='') { ?>
	<tr>
		<td class="tdlabel">&nbsp;&nbsp;&nbsp;&nbsp;Tipo: </td>
		<td><?php echo  @$sol_sug_ret_tipo;?></td>
	</tr>
	<? } ?>
	<tr>
		<td class='tdlabel'>Descripci&oacute;n: </td>
		<td><?php echo $sol_descripcion?></td>
	</tr>	
        <?$labelEstado=($estadoPadre)?"Sub Estado":"Estado Principal";?>
        <?if($estadoPadre){?>
        <tr>		
		<td class="tdlabel">Estado Principal: </td>
		<td><?php echo $estadoPadre; ?></td>
	</tr>
        <?}?>
	<tr>		
		<td class="tdlabel"><? echo $labelEstado?>: </td>
		<td><?php echo $estado; ?></td>
	</tr>
	</table>	
	</form>	
<?php
$this->load->view("footer");
?>