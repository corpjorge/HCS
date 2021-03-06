<?php
/**
 * This is the model class for table "Solicitud".
 *
 * The followings are the available columns in table 'Solicitud':
 * @property integer $dep_id
 * @property string $dep_nombre
 * @property integer $lim_id
 * @property string $dep_externo
 */
class Solicitud_model extends CI_Model 
{
	var $sol_id; 	
	var $dep_id;//se obtiene del estudiante que crea la solicitud
	var $sol_ticket;
	var $sol_descripcion;
	var $sol_mag_crn_ins;//magistral a inscribir
	var $sol_mag_crn_ret;//magistral a retirar
	var $sol_com_crn_ins;//complementaria que se arrastra para inscribir
	var $sol_com_crn_ret;//complementaria que se arrastra al retirar
	var $sol_login;
	var $sol_email;
	var $sol_ip;
	var $sol_fec_creacion;
	var $sol_fec_actualizacion;
	var $tip_id;
	var $mov_id;
	var $est_id;
	
	var $sol_ins_seccion;
	var $sol_ins_instructor;
	var $sol_ins_tipo;
        
	/**
	 * Returns the static model of the specified AR class.
	 * @return Solicitud the static model class
	 */
	function __construct(){
		parent::__construct();
		$this->load->library('integracion');
	}
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ch_solicitud';
	}
	public function tableLlave()
	{
		return 'sol_id';
	}
	
	public function insert($data){				
		return $this->db->insert($this->tableName(), $data); 
	}
	/*input: id - id de la fila a actualizar
	  input: $data - array info de los campos
	  input: $campo - campo de la tabla con el que se
			  hace la comparacion.
	*/
	public function update($id,$data,$campo=''){
		if(empty($campo)){
			$campo = $this->tableLlave();	
		}
		$this->db->where($campo,$id);
		return $this->db->update($this->tableName(),$data);		
	}
	public function delete($id,$campo=''){
		if(empty($campo)){
			$campo = $this->tableLlave();
		}
		$this->db->where($campo,$id);
		return $this->db->delete($this->tableName());		
	}
	public function get_count($qtype='',$qcampo='',$where='',$campo_where='',$qtype2='',$qcampo2=''){
	      $this->db->join('ch_estado E_', 'E_.est_id = ch_solicitud.est_id','inner');
          $this->db->join('ch_tipo T_', 'T_.tip_id = ch_solicitud.tip_id','inner');
          $this->db->join('ch_motivo M_', 'M_.mov_id = ch_solicitud.mov_id','inner');
            
		  if(!empty($qtype)&&!empty($qcampo)){
            $this->db->like($qtype,$qcampo);
          }
          if(!empty($qtype2)&&!empty($qcampo2)){
            $this->db->like($qtype2,$qcampo2);
          }
		  if(!empty($where)&&!empty($campo_where)){
			$this->db->where($campo_where,$where);
		  }
		  return $this->db->count_all_results($this->tableName());
		//si tiene filtro adiciona BINARY para b�squedas con tildes
		//if(!empty($qtype)&&!empty($qcampo)&&!empty($qtype2)&&!empty($qcampo2)){
          //  $query = $this->db->get($this->tableName());
           // $query = $this->db->last_query();
            //echo $query;
            //$query = str_replace(array ("á","é","í","ó","ú","Á","É","Í","Ó","Ú"), array ("a","e","i","o","u","A","E","I","O","U"), $query);
            /*$query = str_replace("LIKE '%", "LIKE BINARY UCASE('%", $query);
            $query = str_replace($qcampo."%'", $qcampo."%')", $query);
            $query = str_replace($qcampo2."%'", $qcampo2."%')", $query);*/
            //$query = str_replace("`$qtype`", "UCASE($qtype)", $query);
            //$query = str_replace("`$qtype2`", "UCASE($qtype2)", $query);
            //$query = str_replace("SELECT *", "SELECT COUNT(*) AS CONTEO", $query);
            //$res = $this->db->query($query);
            //$conteo = $res->result_array();
          //  return $conteo[0]['CONTEO'];
        //}else
			//return $this->db->count_all_results($this->tableName());	
	}
	public function get_count_coordinador($qtype='',$qcampo='',$where=array(),$campo_where='',$arr_prog,$arr_nivel,$qtype2='',$qcampo2=''){
		/*if(!empty($qtype)&&!empty($qcampo)){
			$this->db->like($qtype,$qcampo);
			//var_dump($this->db);
		  }
		  $count = 0;		  		  
		  foreach($where as $item){
			
			if($count==0)
				$this->db->where($campo_where,$item);
			else
				$this->db->or_where($campo_where,$item);
			//var_dump($this->db);
			$count++;
		  }	
		return $this->db->count_all_results($this->tableName());*/
		return count($this->get_all_coordinador('','','','',$qtype,$qcampo,$where,$campo_where='',$arr_prog,$arr_nivel));
	}
	public function get_all($page='',$total='',$order='',$campo_order='',$qtype='',$qcampo='',$where='',$campo_where='',$qtype2='',$qcampo2=''){
		if($page>=0&&!empty($total)&&!empty($order)&&!empty($campo_order)){
		  //$this->db->order_by($this->tableLlave(), 'ASC');
		  //busca	
		  $this->db->join('ch_estado E_', 'E_.est_id = ch_solicitud.est_id','inner');
          $this->db->join('ch_tipo T_', 'T_.tip_id = ch_solicitud.tip_id','inner');
          $this->db->join('ch_motivo M_', 'M_.mov_id = ch_solicitud.mov_id','inner');	  
		  if(!empty($qtype)&&!empty($qcampo)){
            $this->db->like($qtype,$qcampo);
          }
          if(!empty($qtype2)&&!empty($qcampo2)){
            $this->db->like($qtype2,$qcampo2);
          }
		  if(!empty($where)&&!empty($campo_where)){
			$this->db->where($campo_where,$where);
			//var_dump($this->db);
		  }
		  $this->db->order_by($campo_order, $order);
		  $query = $this->db->get($this->tableName(),$total,$page);
          //echo $this->db->last_query();		  
		}else{
		  $query = $this->db->get($this->tableName());
        }
		  //var_dump($this->db);
		
		//si tiene filtro adiciona BINARY para b�squedas con tildes
		if(!empty($qtype)&&!empty($qcampo)&&!empty($qtype2)&&!empty($qcampo2)){
			$query = $this->db->last_query();
			//$query = str_replace("LIKE '%", "LIKE BINARY UCASE('%", $query);
            //$query = str_replace(array ("á","é","í","ó","ú","Á","É","Í","Ó","Ú"), array ("a","e","i","o","u","A","E","I","O","U"), $query);
			//$query = str_replace($qcampo, $qcampo."%')", $query);
            //$query = str_replace($qcampo2, $qcampo2."%')", $query);
			//$query = str_replace("`$qtype`", "UCASE($qtype)", $query);
            //$query = str_replace("`$qtype2`", "UCASE($qtype2)", $query);
			//echo $query;
			$res = $this->db->query($query);		
			return $res->result_array();
		}else{
			return $query->result_array();
        }
	}
	/*Adapta la funcion get all para que traiga las solicitudes
	de todos los programas a los que pertenece el coordinador*/
	public function get_all_coordinador($page='',$total='',$order='',$campo_order='',$qtype='',$qcampo='',$where=array(),$campo_where='',$arr_prog,$arr_nivel,$qtype2='',$qcampo2=''){
		
		$niveles= $this->integracion->programasActivosNiveles();
		//print_r($niveles);
		$band = FALSE;
        $this->db->join('ch_estado E_', 'E_.est_id = ch_solicitud.est_id','inner');
          $this->db->join('ch_tipo T_', 'T_.tip_id = ch_solicitud.tip_id','inner');
          $this->db->join('ch_motivo M_', 'M_.mov_id = ch_solicitud.mov_id','inner');
		if($page>=0&&!empty($total)&&!empty($order)&&!empty($campo_order))	{			
		  //$this->db->order_by($this->tableLlave(), 'ASC');
		  //busca		  
		  if(!empty($qtype)&&!empty($qcampo)){
			$this->db->like($qtype,$qcampo);
			$band = TRUE;
		  }
          if(!empty($qtype2)&&!empty($qcampo2)){
            $this->db->like($qtype2,$qcampo2);
          }
		  $count = 0;
		  		  
		  /*foreach($where as $item){
			
			if($count==0){
				$this->db->where($campo_where,$item);
				$band = TRUE;
			}				
			else{
				$this->db->or_where($campo_where,$item);
				$band = TRUE;
			}
			//var_dump($this->db);
			$count++;
		  }*/		  		  
		  $this->db->order_by($campo_order, $order);
		  $query = $this->db->get($this->tableName(),$total,$page);		  
		}
		else {
			if(!empty($qtype)&&!empty($qcampo)){
				$this->db->like($qtype,$qcampo);
				$band = TRUE;
			}
			$query = $this->db->get($this->tableName());
		}
		//var_dump($this->db);
		
		//separa la consulta e inserta los filtros para coordinador
		if(!empty($arr_prog) || !empty($arr_nivel)) {
			$query = $this->db->last_query();
			$querys = explode('ORDER BY', $query);
			$query = $querys[0];
		}
		
		//filtros para coordinador------------------------------------------------------------------------------------------------------------------------------------		
		if(!empty($arr_prog)){
			//if(empty($programa)||$programa==' '){
				$conector = ($band)?' AND':' WHERE';
				$query .= $conector.' (';
				$band2 = FALSE;			
				foreach($arr_prog as $item){
					$nivel = $niveles[$item];
					switch (strtoupper($nivel)) {
						case 'PR':
							$sol_nivel = '4';
							break;
						case 'MA':
							$sol_nivel = '4';
							break;
						case 'ES':
							$sol_nivel = '5';
							break;
						case 'DO':
							$sol_nivel = '6';
							break;
						default:
							$sol_nivel = '';
					}
					//elimina prefijos para hacer igual MATERIA y programa
					$items = explode('-', $item);
					$item2 = $items[count($items) - 1];					
					$conector2 = ($band2)?' OR':'';
					$condicional = strtoupper($nivel)=='PR' ? '<' : '=';
					$query .= $conector2.' (dep_id ="'.$item2.'" AND sol_nivel '.$condicional.'"'.$sol_nivel.'") ';			
					$band2 = TRUE;
				}
				$query .= ') ';
				$band = TRUE;	
			//}
		}
		//en solicitud esta digito de la materia
		if(!empty($arr_nivel)){
			$conector = ($band)?' AND':' WHERE';
			$query .= $conector.' (';
			$band2 = FALSE;
			foreach($arr_nivel as $item){
				switch ($item) {
					case '1': //Pregrado
						$sol_nivel = '4';
						break;
					case '2': //Especializaci�n
						$sol_nivel = '5';
						break;
					case '3': //Maestr�a
						$sol_nivel = '4';
						break;
					case '4': //Doctorado
						$sol_nivel = '6';
						break;
					default:
						$sol_nivel = '';
				}				
				$conector2 = ($band2)?' OR':'';
				$query .= $item=='1' ? $conector2.' sol_nivel <'.$sol_nivel.' ' : $conector2.' sol_nivel ='.$sol_nivel.' ';
				$band2 = TRUE;
			}
			$query .= ')';
			$band = TRUE;			
		}
		//------------------------------------------------------------------------------------------------------------------------------------------------------------
		if(count($querys) > 1){
			$query .= 'ORDER BY'.$querys[1];
		}
		//si tiene filtro adiciona BINARY para b�squedas con tildes
		if(!empty($qtype)&&!empty($qcampo)){
			$query = str_replace("LIKE '%", "LIKE BINARY UCASE('%", $query);
			$query = str_replace($qcampo."%'", $qcampo."%')", $query);
			$query = str_replace("`$qtype`", "UCASE($qtype)", $query);
			//echo $query;
		}
		$res = $this->db->query($query);
		return $res->result_array();
		
		//return $query->result_array();
	}
	
	public function get_item($id,$campo=''){
		if(empty($campo)){
			$campo = $this->tableLlave();
		}
		//$this->db->where($campo,$id);		
		//$query = $this->db->query('SELECT * FROM '.$this->tableName());
		$query = $this->db->get_where($this->tableName(), array($campo => $id));
		$resultado = $query->result_array();
		//print_r($resultado);
		$resultado[0]['sol_fec_creacion'] = substr($resultado[0]['sol_fec_creacion'], 0, -3);
		return $resultado;
	}	
	public function get_dropdown(){		
		$lista = $this->get_all();
		$options = array('' => 'Seleccione');
		foreach($lista as $key=>$value){
			$options[$value[$this->tableLlave()]] = utf8_decode($value['sol_ticket']);
		}
		return $options;	
	}
	public function insert_id(){				
		return $this->db->insert_id(); 
	}
	
	public function reporte_solicitudes($conlimit,$programa='',$estado=0,$fec_ini='',$fec_fin='',$inicio=0,$cantidad=20,$order='',$ordertype='ASC',$arr_prog,$arr_nivel){
		
		$niveles= $this->integracion->programasActivosNiveles();
		//print_r($niveles);
		$band = FALSE;
		$query = 'SELECT sol_id,
				 dep_id,
				 dep_id_sec,
				 sol_ticket,
				 sol_descripcion,
				 sol_ins_crn,
				 sol_ins_seccion,
				 sol_ins_instructor,
				 sol_ins_tipo,
				 sol_ret_crn,
				 sol_ins_des,
				 sol_ret_des,
				 sol_ins_mat,
				 sol_ret_mat,
				 sol_sug_ret_crn,
				 sol_sug_ins_crn,
				 sol_sug_ins_des,
				 sol_sug_ret_des,
				 sol_sug_ins_mat,
				 sol_sug_ret_mat,
				 sol_login,
				 sol_email,
				 sol_nombre,
				 sol_apellido,
				 sol_pidm,
				 sol_uidnumber,
				 sol_ip,
				 sol_fec_creacion,
				 sol_fec_actualizacion,
				 tip_id,
				 mov_id,
				 a.est_id,
				 b.est_descripcion
			  FROM ch_solicitud a
			  JOIN ch_estado b ON a.est_id = b.est_id';
		if(!empty($programa)&&$programa!=' '){
			$nivel = $niveles[$programa];
			switch (strtoupper($nivel)) {
				case 'PR':
					$sol_nivel = '4';
					break;
				case 'MA':
					$sol_nivel = '4';
					break;
				case 'ES':
					$sol_nivel = '5';
					break;
				case 'DO':
					$sol_nivel = '6';
					break;
				default:
					$sol_nivel = '';
			}
			//elimina prefijos para hacer igual MATERIA y programa
			$programas = explode('-', $programa);
			$programa2 = $programas[count($programas) - 1];
			$condicional = strtoupper($nivel)=='PR' ? '<' : '=';
			$query .=' WHERE (a.dep_id ="'.$programa2.'" AND a.sol_nivel '.$condicional.'"'.$sol_nivel.'")';
			$band = TRUE;
		}
		if($estado>0){
			$conector = ($band)?'AND':'WHERE';
			$query .=' '.$conector.' a.est_id ="'.$estado.'"';
			$band = TRUE;
		}
		if(!empty($fec_ini)&&$fec_ini!=' '&&$fec_fin!=' '&&!empty($fec_fin)){
			$conector = ($band)?'AND':'WHERE';
			$fec_ini2 = explode(' ', $fec_ini);
			$fec_ini2 = $fec_ini2[0];
			$fec_fin2 = explode(' ', $fec_fin);
			$fec_fin2 = $fec_fin2[0];
			$query .= $fec_ini2==$fec_fin2 ? ' '.$conector.' a.sol_fec_creacion like "'.$fec_ini2.'%"' : ' '.$conector.' a.sol_fec_creacion >="'.$fec_ini.'" AND a.sol_fec_creacion<="'.$fec_fin.'"';
			$band = TRUE;
		}
		
		//filtros para coordinador------------------------------------------------------------------------------------------------------------------------------------		
		if(!empty($arr_prog)){
			if(empty($programa)||$programa==' '){
				$conector = ($band)?' AND':' WHERE';
				$query .= $conector.' (';
				$band2 = FALSE;			
				foreach($arr_prog as $item){
					$nivel = $niveles[$item];
					switch (strtoupper($nivel)) {
						case 'PR':
							$sol_nivel = '4';
							break;
						case 'MA':
							$sol_nivel = '4';
							break;
						case 'ES':
							$sol_nivel = '5';
							break;
						case 'DO':
							$sol_nivel = '6';
							break;
						default:
							$sol_nivel = '';
					}
					//elimina prefijos para hacer igual MATERIA y programa
					$items = explode('-', $item);
					$item2 = $items[count($items) - 1];					
					$conector2 = ($band2)?' OR':'';
					$condicional = strtoupper($nivel)=='PR' ? '<' : '=';
					$query .= $conector2.' (a.dep_id ="'.$item2.'" AND a.sol_nivel '.$condicional.'"'.$sol_nivel.'") ';			
					$band2 = TRUE;
				}
				$query .= ') ';
				$band = TRUE;	
			}
		}
		//en solicitud esta digito de la materia
		if(!empty($arr_nivel)){
			$conector = ($band)?' AND':' WHERE';
			$query .= $conector.' (';
			$band2 = FALSE;
			foreach($arr_nivel as $item){
				switch ($item) {
					case '1': //Pregrado
						$sol_nivel = '4';
						break;
					case '2': //Especializaci�n
						$sol_nivel = '5';
						break;
					case '3': //Maestr�a
						$sol_nivel = '4';
						break;
					case '4': //Doctorado
						$sol_nivel = '6';
						break;
					default:
						$sol_nivel = '';
				}				
				$conector2 = ($band2)?' OR':'';
				$query .= $item=='1' ? $conector2.' a.sol_nivel <'.$sol_nivel.' ' : $conector2.' a.sol_nivel ='.$sol_nivel.' ';
				$band2 = TRUE;
			}
			$query .= ')';
			$band = TRUE;			
		}
		//------------------------------------------------------------------------------------------------------------------------------------------------------------
		
		if($order=='')//if($order!=' ')
			$order = $this->tableLlave();
		$query .=' ORDER BY '.$order.' '.$ordertype;
		
		if($conlimit)
			$query .=' LIMIT '.$inicio.','.$cantidad;
		
		//echo $query;		
		$res = $this->db->query($query);		
		return $res->result_array();		
	}
	
	public function reporte_solicitud_estado($conlimit,$programa='',$estado=0,$fec_ini='',$fec_fin='',$inicio=0,$cantidad=20,$order='',$ordertype='ASC',$arr_prog,$arr_nivel){
		
		$niveles= $this->integracion->programasActivosNiveles();
		$band = FALSE;
		$query = 'SELECT a.dep_id, a.est_id, b.est_descripcion, a.sol_fec_creacion, count(sol_id) as total
			  FROM `ch_solicitud` a
			  JOIN ch_estado b ON a.est_id = b.est_id';
		if(!empty($programa)&&$programa!=' '){
			$nivel = $niveles[$programa];
			switch (strtoupper($nivel)) {
				case 'PR':
					$sol_nivel = '4';
					break;
				case 'MA':
					$sol_nivel = '4';
					break;
				case 'ES':
					$sol_nivel = '5';
					break;
				case 'DO':
					$sol_nivel = '6';
					break;
				default:
					$sol_nivel = '';
			}
			//elimina prefijos para hacer igual MATERIA y programa
			$programas = explode('-', $programa);
			$programa2 = $programas[count($programas) - 1];
			$condicional = strtoupper($nivel)=='PR' ? '<' : '=';
			$query .=' WHERE (a.dep_id ="'.$programa2.'" AND a.sol_nivel '.$condicional.'"'.$sol_nivel.'")';
			$band = TRUE;
		}
		if($estado>0){
			$conector = ($band)?'AND':'WHERE';
			$query .=' '.$conector.' a.est_id ="'.$estado.'"';
			$band = TRUE;
		}
		if(!empty($fec_ini)&&$fec_ini!=' '&&$fec_fin!=' '&&!empty($fec_fin)){
			$conector = ($band)?'AND':'WHERE';
			$fec_ini2 = explode(' ', $fec_ini);
			$fec_ini2 = $fec_ini2[0];
			$fec_fin2 = explode(' ', $fec_fin);
			$fec_fin2 = $fec_fin2[0];
			$query .= $fec_ini2==$fec_fin2 ? ' '.$conector.' a.sol_fec_creacion like "'.$fec_ini2.'%"' : ' '.$conector.' a.sol_fec_creacion >="'.$fec_ini.'" AND a.sol_fec_creacion<="'.$fec_fin.'"';
			$band = TRUE;			
		}
		
		//filtros para coordinador------------------------------------------------------------------------------------------------------------------------------------		
		if(!empty($arr_prog)){
			if(empty($programa)||$programa==' '){
				$conector = ($band)?' AND':' WHERE';
				$query .= $conector.' (';
				$band2 = FALSE;			
				foreach($arr_prog as $item){
					$nivel = $niveles[$item];
					switch (strtoupper($nivel)) {
						case 'PR':
							$sol_nivel = '4';
							break;
						case 'MA':
							$sol_nivel = '4';
							break;
						case 'ES':
							$sol_nivel = '5';
							break;
						case 'DO':
							$sol_nivel = '6';
							break;
						default:
							$sol_nivel = '';
					}
					//elimina prefijos para hacer igual MATERIA y programa
					$items = explode('-', $item);
					$item2 = $items[count($items) - 1];					
					$conector2 = ($band2)?' OR':'';
					$condicional = strtoupper($nivel)=='PR' ? '<' : '=';
					$query .= $conector2.' (a.dep_id ="'.$item2.'" AND a.sol_nivel '.$condicional.'"'.$sol_nivel.'") ';			
					$band2 = TRUE;
				}
				$query .= ') ';
				$band = TRUE;	
			}
		}
		//en solicitud esta digito de la materia
		if(!empty($arr_nivel)){
			$conector = ($band)?' AND':' WHERE';
			$query .= $conector.' (';
			$band2 = FALSE;
			foreach($arr_nivel as $item){
				switch ($item) {
					case '1': //Pregrado
						$sol_nivel = '4';
						break;
					case '2': //Especializaci�n
						$sol_nivel = '5';
						break;
					case '3': //Maestr�a
						$sol_nivel = '4';
						break;
					case '4': //Doctorado
						$sol_nivel = '6';
						break;
					default:
						$sol_nivel = '';
				}				
				$conector2 = ($band2)?' OR':'';
				$query .= $item=='1' ? $conector2.' a.sol_nivel <'.$sol_nivel.' ' : $conector2.' a.sol_nivel ='.$sol_nivel.' ';
				$band2 = TRUE;
			}
			$query .= ')';
			$band = TRUE;			
		}
		//------------------------------------------------------------------------------------------------------------------------------------------------------------
		
		if($order=='')//if($order!=' ')
			$order = $this->tableLlave();
		$query .=' GROUP BY a.dep_id, b.est_descripcion';	
		$query .=' ORDER BY '.$order;
		
		if($conlimit)
		$query .=' LIMIT '.$inicio.','.$cantidad;
		
		//echo $query;
		$res = $this->db->query($query);		
		return $res->result_array();
		
	}
	
	public function reporte_solicitud_crn($conlimit,$programa='',$estado=0,$fec_ini='',$fec_fin='',$inicio=0,$cantidad=20,$order='',$ordertype='ASC',$arr_prog,$arr_nivel){
		
		$niveles= $this->integracion->programasActivosNiveles();	
		$band = FALSE;
		$query = 'SELECT a.sol_ins_crn, a.dep_id, a.est_id, b.est_descripcion, a.sol_fec_creacion, count(sol_id) as total
			  FROM `ch_solicitud` a
			  JOIN ch_estado b ON a.est_id = b.est_id';
		if(!empty($programa)&&$programa!=' '){
			$nivel = $niveles[$programa];
			switch (strtoupper($nivel)) {
				case 'PR':
					$sol_nivel = '4';
					break;
				case 'MA':
					$sol_nivel = '4';
					break;
				case 'ES':
					$sol_nivel = '5';
					break;
				case 'DO':
					$sol_nivel = '6';
					break;
				default:
					$sol_nivel = '';
			}
			//elimina prefijos para hacer igual MATERIA y programa
			$programas = explode('-', $programa);
			$programa2 = $programas[count($programas) - 1];
			$condicional = strtoupper($nivel)=='PR' ? '<' : '=';
			$query .=' WHERE (a.dep_id ="'.$programa2.'" AND a.sol_nivel '.$condicional.'"'.$sol_nivel.'")';
			$band = TRUE;
		}
		if($estado>0){
			$conector = ($band)?'AND':'WHERE';
			$query .=' '.$conector.' a.est_id ="'.$estado.'"';
			$band = TRUE;
		}
		if(!empty($fec_ini)&&$fec_ini!=' '&&$fec_fin!=' '&&!empty($fec_fin)){
			$conector = ($band)?'AND':'WHERE';
			$fec_ini2 = explode(' ', $fec_ini);
			$fec_ini2 = $fec_ini2[0];
			$fec_fin2 = explode(' ', $fec_fin);
			$fec_fin2 = $fec_fin2[0];
			$query .= $fec_ini2==$fec_fin2 ? ' '.$conector.' a.sol_fec_creacion like "'.$fec_ini2.'%"' : ' '.$conector.' a.sol_fec_creacion >="'.$fec_ini.'" AND a.sol_fec_creacion<="'.$fec_fin.'"';
			$band = TRUE;
		}
		
		//filtros para coordinador------------------------------------------------------------------------------------------------------------------------------------		
		if(!empty($arr_prog)){
			if(empty($programa)||$programa==' '){
				$conector = ($band)?' AND':' WHERE';
				$query .= $conector.' (';
				$band2 = FALSE;			
				foreach($arr_prog as $item){
					$nivel = $niveles[$item];
					switch (strtoupper($nivel)) {
						case 'PR':
							$sol_nivel = '4';
							break;
						case 'MA':
							$sol_nivel = '4';
							break;
						case 'ES':
							$sol_nivel = '5';
							break;
						case 'DO':
							$sol_nivel = '6';
							break;
						default:
							$sol_nivel = '';
					}
					//elimina prefijos para hacer igual MATERIA y programa
					$items = explode('-', $item);
					$item2 = $items[count($items) - 1];					
					$conector2 = ($band2)?' OR':'';
					$condicional = strtoupper($nivel)=='PR' ? '<' : '=';
					$query .= $conector2.' (a.dep_id ="'.$item2.'" AND a.sol_nivel '.$condicional.'"'.$sol_nivel.'") ';			
					$band2 = TRUE;
				}
				$query .= ') ';
				$band = TRUE;	
			}
		}
		//en solicitud esta digito de la materia
		if(!empty($arr_nivel)){
			$conector = ($band)?' AND':' WHERE';
			$query .= $conector.' (';
			$band2 = FALSE;
			foreach($arr_nivel as $item){
				switch ($item) {
					case '1': //Pregrado
						$sol_nivel = '4';
						break;
					case '2': //Especializaci�n
						$sol_nivel = '5';
						break;
					case '3': //Maestr�a
						$sol_nivel = '4';
						break;
					case '4': //Doctorado
						$sol_nivel = '6';
						break;
					default:
						$sol_nivel = '';
				}				
				$conector2 = ($band2)?' OR':'';
				$query .= $item=='1' ? $conector2.' a.sol_nivel <'.$sol_nivel.' ' : $conector2.' a.sol_nivel ='.$sol_nivel.' ';
				$band2 = TRUE;
			}
			$query .= ')';
			$band = TRUE;			
		}
		//------------------------------------------------------------------------------------------------------------------------------------------------------------
		
		if($order=='')//if($order!=' ')
			$order = $this->tableLlave();
		$query .=' GROUP BY a.sol_ins_crn';	
		$query .=' ORDER BY '.$order;
		
		if($conlimit)
		$query .=' LIMIT '.$inicio.','.$cantidad;
		
		//echo $query;
		$res = $this->db->query($query);		
		return $res->result_array();
		
	}
	
	public function count_reporte_solicitudes($programa='',$estado=0,$fec_ini='',$fec_fin='',$inicio=0,$cantidad=20,$order='',$ordertype='ASC',$arr_prog,$arr_nivel){
		/*$band = FALSE;
		$query = 'SELECT sol_id,
				 dep_id,
				 dep_id_sec,
				 sol_ticket,
				 sol_descripcion,
				 sol_ins_crn,
				 sol_ins_seccion,
				 sol_ins_instructor,
				 sol_ins_tipo,
				 sol_ret_crn,
				 sol_ins_des,
				 sol_ret_des,
				 sol_ins_mat,
				 sol_ret_mat,
				 sol_sug_ret_crn,
				 sol_sug_ins_crn,
				 sol_sug_ins_des,
				 sol_sug_ret_des,
				 sol_sug_ins_mat,
				 sol_sug_ret_mat,
				 sol_login,
				 sol_email,
				 sol_nombre,
				 sol_apellido,
				 sol_pidm,
				 sol_uidnumber,
				 sol_ip,
				 sol_fec_creacion,
				 sol_fec_actualizacion,
				 tip_id,
				 mov_id,
				 a.est_id,
				 b.est_descripcion
			  FROM ch_solicitud a
			  JOIN ch_estado b ON a.est_id = b.est_id';
		if(!empty($programa)&&$programa!=' '){
			$query .=' WHERE a.dep_id ="'.$programa.'"';
			$band = TRUE;
		}
		if($estado>0){
			$conector = ($band)?'AND':'WHERE';
			$query .=' '.$conector.' a.est_id ="'.$estado.'"';
			$band = TRUE;
		}
		if(!empty($fec_ini)&&$fec_ini!=' '&&$fec_fin!=' '&&!empty($fec_fin)){
			$conector = ($band)?'AND':'WHERE';
			$query .=' '.$conector.' a.sol_fec_creacion >="'.$fec_ini.'" AND a.sol_fec_creacion<="'.$fec_fin.'"';			
		}
		if($order!=' ')
			$order = $this->tableLlave();
		$query .=' ORDER BY '.$order.' '.$ordertype;				
		
		$res = $this->db->query($query);
		
		return count($res->result_array());*/
		return count($this->reporte_solicitudes(false,$programa,$estado,$fec_ini,$fec_fin,$inicio,$cantidad,$order,$ordertype,$arr_prog,$arr_nivel));
	}
	
	public function count_reporte_solicitud_estado($programa='',$estado=0,$fec_ini='',$fec_fin='',$inicio=0,$cantidad=20,$order='',$ordertype='ASC',$arr_prog,$arr_nivel){
		
		/*$band = FALSE;
		$query = 'SELECT a.dep_id, a.est_id, b.est_descripcion, a.sol_fec_creacion, count(sol_id) as total
			  FROM `ch_solicitud` a
			  JOIN ch_estado b ON a.est_id = b.est_id';
		if(!empty($programa)&&$programa!=' '){
			$query .=' WHERE a.dep_id ="'.$programa.'"';
			$band = TRUE;
		}
		if($estado>0){
			$conector = ($band)?'AND':'WHERE';
			$query .=' '.$conector.' a.est_id ="'.$estado.'"';
			$band = TRUE;
		}
		if(!empty($fec_ini)&&$fec_ini!=' '&&$fec_fin!=' '&&!empty($fec_fin)){
			$conector = ($band)?'AND':'WHERE';
			$query .=' '.$conector.' a.sol_fec_creacion >="'.$fec_ini.'" AND a.sol_fec_creacion<="'.$fec_fin.'"';			
		}
		if($order!=' ')
			$order = $this->tableLlave();
		$query .=' GROUP BY b.est_descripcion';	
		$query .=' ORDER BY '.$order;				
		
		//echo $query;
		$res = $this->db->query($query);
		
		return count($res->result_array());*/
		return count($this->reporte_solicitud_estado(false,$programa,$estado,$fec_ini,$fec_fin,$inicio,$cantidad,$order,$ordertype,$arr_prog,$arr_nivel));
	}
	
	public function count_reporte_solicitud_crn($programa='',$estado=0,$fec_ini='',$fec_fin='',$inicio=0,$cantidad=20,$order='',$ordertype='ASC',$arr_prog,$arr_nivel){
		
		/*$band = FALSE;
		$query = 'SELECT a.sol_ins_crn, a.dep_id, a.est_id, b.est_descripcion, a.sol_fec_creacion, count(sol_id) as total
			  FROM `ch_solicitud` a
			  JOIN ch_estado b ON a.est_id = b.est_id';
		if(!empty($programa)&&$programa!=' '){
			$query .=' WHERE a.dep_id ="'.$programa.'"';
			$band = TRUE;
		}
		if($estado>0){
			$conector = ($band)?'AND':'WHERE';
			$query .=' '.$conector.' a.est_id ="'.$estado.'"';
			$band = TRUE;
		}
		if(!empty($fec_ini)&&$fec_ini!=' '&&$fec_fin!=' '&&!empty($fec_fin)){
			$conector = ($band)?'AND':'WHERE';
			$query .=' '.$conector.' a.sol_fec_creacion >="'.$fec_ini.'" AND a.sol_fec_creacion<="'.$fec_fin.'"';			
		}
		if($order!=' ')
			$order = $this->tableLlave();
		$query .=' GROUP BY a.sol_ins_crn';	
		$query .=' ORDER BY '.$order;		
		
		$res = $this->db->query($query);
		
		return count($res->result_array());*/
		return count($this->reporte_solicitud_crn(false,$programa,$estado,$fec_ini,$fec_fin,$inicio,$cantidad,$order,$ordertype,$arr_prog,$arr_nivel));
	}
}