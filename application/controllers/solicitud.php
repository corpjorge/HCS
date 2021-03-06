<?php

class Solicitud extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('Solicitud_model', '', TRUE);
        $this->load->helper('url');
        $this->load->library('form_validation');
        $this->load->library('Auth_Ldap');
        $this->load->library('session');
        $this->load->library('integracion');
        $this->load->library('email');
        date_default_timezone_set("America/Bogota");
        $this->load->model('Menu_model', '', TRUE);
        $this->config->set_item("sess_expiration", 5);
        
        if (!$this->session->userdata('logged_in')) {
            if ($this->isAjax()) {
                echo "expired";
                exit;
            } else
                redirect('auth/index?e=1');
        }
    }

    function index() {
        $menu = $this->Menu_model->_getmenu();
        $data = array('titulo' => 'ADMINISTRADOR DE SOLICITUDES', 'menu' => $menu);

        $data['otro'] = 'n';
        if (!$this->session->userdata('cantpag')) {
            $datos_sesion = array('cantpag' => 20);
            $this->session->set_userdata($datos_sesion);
        }
        if (!$this->session->userdata('numpag')) {
            $datos_sesion = array('numpag' => 1);
            $this->session->set_userdata($datos_sesion);
        } else {
            $data['otro'] = 's';
        }



        //$this->_prepare_list($data);
        if ($this->session->userdata('rol') == 3) {
            $this->load->view('solicitud_listado', $data);
        } else {
            //var_dump($this->session->userdata('numpag'));
            $columnas = $this->session->userdata('colocultas');
            if ($columnas) {
                $data['ocultas'] = explode(';', $columnas);
            } else {
                $data['ocultas'] = array();
            }
            $squery = $this->session->userdata('query');
            if (empty($squery)) {
                $this->load->model('Coordinador_model', '', TRUE);
                $this->session->set_userdata('query', $this->Coordinador_model->BDFiltros());
                //var_dump($data['query']);exit;
            }
            $data['rp'] = $this->session->userdata('cantpag');

            $data['ordencol'] = $this->session->userdata('ordencol');
            $data['ordencol_label'] = $this->session->userdata('ordencol_label');
            $data['ordencol_width'] = $this->session->userdata('ordencol_width');
            $data['sortname'] = $this->session->userdata('sortname');
            $data['sortorder'] = $this->session->userdata('sortorder');

            $data['qtype'] = $this->session->userdata('qtype');
            $data['query'] = $this->session->userdata('query');
            $data['qtype2'] = $this->session->userdata('qtype2');
            $data['query2'] = $this->session->userdata('query2');
            $data['query3'] = $this->session->userdata('query3');

            $estados_sol = $this->Solicitud_model->getEstados();
            $data["estados_sol"] = $estados_sol;
            $listProgramas = $this->Solicitud_model->getProgramas();
            $strProgramas = "";
            foreach ($listProgramas as $key => $value) {
                $strProgramas .= "'" . $value['swtprnl_enfasis_desc'] . "',";
            }
            $strProgramas = trim($strProgramas, ",");
            // $data['listEstado'] = "'En revisión','Solicitudes No Exitosas','Solicitudes Exitosas','En espera de respuesta del estudiante','En espera de respuesta del coordinador','En espera de respuesta del coordinador','Solicitudes Ignoradas','Lista de Espera'";
            $data['listEstado'] = "'En revisión','Solicitudes No Exitosas','Solicitudes Exitosas','En espera de respuesta del estudiante','Solicitudes Ignoradas','Lista de Espera'";
            $data['listEstadoColor'] = "'En revisión','Solicitudes No Exitosas','Solicitudes Exitosas','En espera de respuesta del estudiante','Solicitudes Ignoradas','Lista de Espera'";
            //$data['listEstado'] = "En revisión,Solicitudes No Exitosas,Solicitudes Exitosas,En espera de respuesta del estudiante,En espera de respuesta del coordinador,Solicitudes Ignoradas,Lista de Espera";
            $data['listTipo'] = "'Inscribir Materia','Cambio de Sección ','Inscribir y Retirar Materia ','Cambio de sección de laboratorio o complementaria'";
            $data['listMotivo'] = "'Sección Llena ','Restricciones (nivel, semestre, programa, opción)','Cruce de horario','Fuera de turno','Cambio de Sección'";
            $data["listProgramas"] = $strProgramas;
            $this->load->view('solicitud_listado_admin', $data);
        }
    }

    public function crearadm() {
        $menu = $this->Menu_model->_getmenu();
        $data = array('titulo' => 'Crear',
            'accion' => 'crear',
            'menu' => $menu
        );

        $this->load->view('solicitud_form_admin', $data);
    }

    public function crear($no_validar = '') { //para usuarios no estudiantes la primera vez no debe validar
//
        $menu = $this->Menu_model->_getmenu();
        $this->load->model('Tipo_model', '', TRUE);
        $options_tipo = $this->Tipo_model->get_dropdown();
        unset($this->Tipo_model);
        $this->load->model('Motivo_model', '', TRUE);
        $options_motivo = $this->Motivo_model->get_dropdown();
        $this->load->model('Parametro_model', '', TRUE);
        $periodo = $this->Parametro_model->get_item('periodo', 'par_nombre');
        $_periodo = $periodo;
        $dperiodo = $this->Parametro_model->get_item('dperiodo', 'par_nombre');
        $_dperiodo = $dperiodo;
        unset($this->Motivo_model);

        if ($no_validar == '')
            $validacion = $this->validar();
        else
            $validacion = false;


        if ($validacion) {
            //si tipo es Inscribir Materia en los campos ins se guarda el valor de los post ret
            $sol_ins_crn = $this->input->post('tip_id') != '1' ? $this->input->post('sol_disp_crn_ins') : $this->input->post('sol_disp_crn_ret');
            $sol_ret_crn = $this->input->post('tip_id') != '1' ? $this->input->post('sol_disp_crn_ret') : '';
            $sol_ins_des = $this->input->post('tip_id') != '1' ? $this->input->post('sol_disp_crn_ins_des') : $this->input->post('sol_disp_crn_ret_des');
            $sol_ret_des = $this->input->post('tip_id') != '1' ? $this->input->post('sol_disp_crn_ret_des') : '';
            $sol_ins_mat = $this->input->post('tip_id') != '1' ? $this->input->post('sol_disp_crn_ins_materia') : $this->input->post('sol_disp_crn_ret_materia');
            $sol_ret_mat = $this->input->post('tip_id') != '1' ? $this->input->post('sol_disp_crn_ret_materia') : '';
            $sol_sug_ins_crn = $this->input->post('tip_id') != '1' ? $this->input->post('sol_sug_crn_ins') : $this->input->post('sol_sug_crn_ret');
            $sol_sug_ret_crn = $this->input->post('tip_id') != '1' ? $this->input->post('sol_sug_crn_ret') : '';
            $sol_sug_ins_des = $this->input->post('tip_id') != '1' ? $this->input->post('sol_sug_crn_ins_des') : $this->input->post('sol_sug_crn_ret_des');
            $sol_sug_ret_des = $this->input->post('tip_id') != '1' ? $this->input->post('sol_sug_crn_ret_des') : '';
            $sol_sug_ins_mat = $this->input->post('tip_id') != '1' ? $this->input->post('sol_sug_crn_ins_materia') : $this->input->post('sol_sug_crn_ret_materia');
            $sol_sug_ret_mat = $this->input->post('tip_id') != '1' ? $this->input->post('sol_sug_crn_ret_materia') : '';

            $sol_ins_seccion = $this->input->post('tip_id') != '1' ? $this->input->post('sol_disp_crn_ins_seccion') : $this->input->post('sol_disp_crn_ret_seccion');
            $sol_ins_instructor = $this->input->post('tip_id') != '1' ? $this->input->post('sol_disp_crn_ins_instructor') : $this->input->post('sol_disp_crn_ret_instructor');
            $sol_ins_tipo = $this->input->post('tip_id') != '1' ? $this->input->post('sol_disp_crn_ins_tipo') : $this->input->post('sol_disp_crn_ret_tipo');

            $sol_ret_seccion = $this->input->post('tip_id') != '1' ? $this->input->post('sol_disp_crn_ret_seccion') : '';
            $sol_ret_instructor = $this->input->post('tip_id') != '1' ? $this->input->post('sol_disp_crn_ret_instructor') : '';
            $sol_ret_tipo = $this->input->post('tip_id') != '1' ? $this->input->post('sol_disp_crn_ret_tipo') : '';

            $sol_sug_ins_seccion = $this->input->post('tip_id') != '1' ? $this->input->post('sol_sug_crn_ins_seccion') : $this->input->post('sol_sug_crn_ret_seccion');
            $sol_sug_ins_instructor = $this->input->post('tip_id') != '1' ? $this->input->post('sol_sug_crn_ins_instructor') : $this->input->post('sol_sug_crn_ret_instructor');
            $sol_sug_ins_tipo = $this->input->post('tip_id') != '1' ? $this->input->post('sol_sug_crn_ins_tipo') : $this->input->post('sol_sug_crn_ret_tipo');

            $sol_sug_ret_seccion = $this->input->post('tip_id') != '1' ? $this->input->post('sol_sug_crn_ret_seccion') : '';
            $sol_sug_ret_instructor = $this->input->post('tip_id') != '1' ? $this->input->post('sol_sug_crn_ret_instructor') : '';
            $sol_sug_ret_tipo = $this->input->post('tip_id') != '1' ? $this->input->post('sol_sug_crn_ret_tipo') : '';

		$sol_lista_cruzada =  $this->input->post('sol_disp_lista_cruzada');

            $prog = substr($sol_ins_mat, 0, 4);
            //$prog = 'PRUEBA'; ////////////////////////////////////////////prueba eliminar
            if (!$this->_validar_crear2($prog)) {
                redirect('solicitud/mensaje/4/' . $prog);
            } else {
                if (!$this->_validar_crear($prog)) {
                    $this->load->model('Limite_model', 'Limite_model_validar_crear', TRUE);
                    $limites_dep = $this->Limite_model_validar_crear->get_item($prog, 'dep_id');

                    unset($this->Limite_model_validar_crear);
                    if (empty($limites_dep))
                        redirect('solicitud/mensaje/3/' . $prog);
                    else
                        redirect('solicitud/mensaje/1');
                }
            }
            /* obtengo el nivel de la materia de inscripcion */
            $nivel_ins_mat = substr($sol_ins_mat, 4, 1);

            $sol_uidnumber = $this->session->userdata('UACarnetEstudiante') != '' ? $this->session->userdata('UACarnetEstudiante') : $this->session->userdata('uidNumber');
            // $periodo = $periodo[0]['par_valor'];
            $periodo = $this->input->post("periodo");
            //
            $datos_estudiante = $this->integracion->datosEstudiante($this->input->post('sol_pidm'), $periodo);

            $doblePrograma = $datos_estudiante['DOBLE_PROGRAMA'];
            $programa = $datos_estudiante['PROGRAMA'];
            $creditos = $datos_estudiante['CRED_INS'];
            $max_creditos = $datos_estudiante['CRED_MAX'];
            // //Validamos  los cruzes de horarios 
            // $HorarioCRN = $this->Solicitud_model->conultarHorarioCRN($sol_ins_crn,$periodo);
            // echo "<pre>aca: ";
            // print_r($HorarioCRN);
            // exit;
            // if ($codigo<1){
            // $codigo = $this->session->userdata('UACarnetEstudiante');
            // }
            // $existeCruceH = $this->hayCruceHorario($crn,$HorarioCRN,$codigo,$periodo);
            // $max_creditos= 10;
            //validamos el numero de creditos inscritos versus el maximo de creditos disponibles para el estudiante
            $tip_id = $this->input->post('tip_id');

            // echo '$creditos: ' . $creditos . ', $max_creditos: ' . $max_creditos . ', $tip_id: ' . $tip_id; exit;

            if ($max_creditos > 0) {
                if (($creditos >= $max_creditos) && ($tip_id != 3 && $tip_id != 2)) { // LS: Se agrega condicion para que se valide diferente de tipo "retirar y inscribir " y "cambio de seccion"
                    redirect('solicitud/mensaje/5/' . $prog);
                }
            }



            $attr_curso = $this->input->post('attr_curso');

            $attr_curso_value = $this->input->post('attr_curso_value');
            $attr_curso = ($this->input->post('attr_curso') != "") ? $this->input->post('attr_curso_value') : "No";

            if (strtolower(trim($attr_curso)) == "no") {
                $attr_curso = "";
            }

            $datos = array('sol_descripcion' => $this->input->post('sol_descripcion'),
                'sol_nivel' => $nivel_ins_mat,
                'tip_id' => $this->input->post('tip_id'),
                'mov_id' => $this->input->post('mov_id'),
                'sol_login' => $this->session->userdata('login'),
                'sol_ip' => $this->session->userdata('ip_address'),
                'sol_fec_creacion' => date("Y-m-d H:i:s"),
                'sol_ticket' => time() . '-' . $this->session->userdata('programas'),
                'dep_id' => $prog, /* $this->session->userdata('programas'), */
                'dep_id_sec' => $this->session->userdata('programa_sec'),
                'sol_email' => $this->session->userdata('login') . '@uniandes.edu.co',
                'sol_nombre' => $this->session->userdata('nombres'),
                'sol_apellido' => $this->session->userdata('apellidos'),
                'sol_pidm' => $this->session->userdata('pidm'),
                'sol_uidnumber' => $sol_uidnumber,
                'sol_ins_crn' => trim($sol_ins_crn),
                'sol_ret_crn' => trim($sol_ret_crn),
                'sol_ins_des' => trim($sol_ins_des),
				'sol_lista_cruzada' => trim($sol_lista_cruzada),
                'sol_ret_des' => trim($sol_ret_des),
                'sol_ins_mat' => trim($sol_ins_mat),
                'sol_ret_mat' => trim($sol_ret_mat),
                'sol_sug_ins_crn' => trim($sol_sug_ins_crn),
                'sol_sug_ret_crn' => trim($sol_sug_ret_crn),
                'sol_sug_ins_des' => trim($sol_sug_ins_des),
                'sol_sug_ret_des' => trim($sol_sug_ret_des),
                'sol_sug_ins_mat' => trim($sol_sug_ins_mat),
                'sol_sug_ret_mat' => trim($sol_sug_ret_mat),
                'sol_sug_crns_cc' => trim($this->input->post('sol_sug_crns_cc')),
                'sol_sug_crns_cc_materia' => trim($this->input->post('sol_sug_crns_cc_materia')),
                'sol_sug_crns_cc_instructor' => trim($this->input->post('sol_sug_crns_cc_instructor')),
                'sol_sug_crns_cc_seccion' => trim($this->input->post('sol_sug_crns_cc_seccion')),
                'sol_ins_seccion' => trim($sol_ins_seccion),
                'sol_ins_instructor' => trim($sol_ins_instructor),
                'sol_ins_tipo' => trim($sol_ins_tipo),
                'sol_ret_seccion' => trim($sol_ret_seccion),
                'sol_ret_instructor' => trim($sol_ret_instructor),
                'sol_ret_tipo' => trim($sol_ret_tipo),
                'sol_sug_ins_seccion' => trim($sol_sug_ins_seccion),
                'sol_sug_ins_instructor' => trim($sol_sug_ins_instructor),
                'sol_sug_ins_tipo' => trim($sol_sug_ins_tipo),
                'sol_sug_ret_seccion' => trim($sol_sug_ret_seccion),
                'sol_sug_ret_instructor' => trim($sol_sug_ret_instructor),
                'sol_sug_ret_tipo' => trim($sol_sug_ret_tipo),
                'sol_prog' => trim($programa),
                'sol_doble_prog' => trim($doblePrograma),
                'sol_creditos' => trim($creditos),
                'sol_periodo' => trim($periodo),
                'sol_attr_curso' => trim($attr_curso),
                'sol_attr_curso_value' => trim($attr_curso_value),
                'sol_ssc' => trim($this->input->post("sol_ssc")),
                'sol_opcion_estud' => trim($this->input->post("sol_opcion_estud")),
                'sol_primer_sem' => trim($this->input->post('sol_primer_sem')),
                'sol_primer_semes_msg' => trim($this->input->post('sol_primer_semes_msg')),
                'sol_tienecrucehorario' => trim($this->input->post('cruceh'))
            );
            if ($this->session->userdata('rol') != 3) { //si no es estudiante obtiene los datos del formulario y no de la sesion
                //$datos['dep_id'] = $this->input->post('dep_id');
                //$datos['dep_id_sec'] = $this->input->post('dep_id_sec');
                $datos['sol_email'] = $this->input->post('sol_email');
                $datos['sol_login'] = $this->input->post('sol_login');
                $datos['sol_nombre'] = $this->input->post('sol_nombre');
                $datos['sol_apellido'] = $this->input->post('sol_apellido');
                $datos['sol_uidnumber'] = $this->input->post('sol_uidnumber');
                $datos['sol_pidm'] = $this->input->post('sol_pidm');
                $datos['sol_ticket'] .= '-C'; //indica que fue creada por un coordinador
            }
            //validamos esi el crn se encuentra bloqueado para el periodo seleccionado por el estudiante
            $solicitud_crn_bloqueado = 0;
            $crn_bloqueado = $this->Solicitud_model->validarCrnBloqueado($sol_ins_crn);
            foreach ($crn_bloqueado as $crn_bloqs) {
                if ($crn_bloqs['periodo'] == trim($periodo)) {
                    //creo la solicitud pero con el estado Solciitudes no exitosas
                    $datos['est_id'] = 2;
                    $solicitud_crn_bloqueado = 1;
                }
            }

            //echo $this->input->post('sol_uidnumber')."  ".$sol_ins_crn."  ".$periodo;
            $validaCrn = $this->Solicitud_model->validarItemCodigoCrn($datos['sol_uidnumber'], $sol_ins_crn, $periodo, $this->input->post('tip_id'));
            //$validaCrn=$this->Solicitud_model->validarItemCodigoCrn($this->input->post('sol_uidnumber'),$sol_ins_crn,$periodo);
            /* print_r($validaCrn);
              echo "   diferenciando   ";
              print_r(count($validaCrn));
              echo "     este es el nuevo count               ";
              //print_r($validaCrn1);
              echo "   diferenciando   "; */
            //print_r(count($validaCrn1));
            if (count($validaCrn) > 0) {
                $msj = "Ya existe un registro en el sistema para el CRN " . $sol_ins_crn;
            } else {
                //ALTER TABLE ch_solicitud ADD sol_alternativas VARCHAR( 255 ) after sol_ticket;
                $alternativas = $this->input->post('alternativa');
                $instructores = $this->input->post('profAlter');
                $secciones = $this->input->post('seccionAlter');

                $alternativasColumn = array();
                foreach ($alternativas as $key => $value) {
                    if (empty($alternativas[$key])) {
                        unset($alternativas[$key]);
                    } else {

                        $alternativasColumn[$key] = array(
                            'crn' => $alternativas[$key],
                            'instructor' => $instructores[$key],
                            'secciones' => $secciones[$key]);
                    }
                }

                if (count($alternativas) > 0) {
                    $datos['sol_alternativas'] = json_encode($alternativasColumn);
                }

                if ($this->Solicitud_model->insert($datos)) {
                    $id_sol = $this->Solicitud_model->insert_id();
                    if ($solicitud_crn_bloqueado == 1) {
                        $this->load->model('Comentario_model', '', TRUE);
                        //asocio el comentario
                        $datac = array(
                            'com_texto' => "Solicitud cancelada automaticamente, debido a que el CRN se encuentra bloqueado para el período " . (trim($periodo)),
                            'com_login' => $this->session->userdata('login'),
                            'com_nombre' => $this->session->userdata('nombres'),
                            'sol_id' => $id_sol,
                            'rol_id' => $this->session->userdata('rol'),
                        );

                        if ($datac['com_texto'] != '') {
                            $this->Comentario_model->insert($datac);
                        }
                        $msj = "Su solicitud de Conflicto Horario ha sido enviada. Sin embargo ésta fue cancelada automáticamente, debido a que no se están tramitando solicitudes por el momento para este CRN.";
                    } else {
                        $msj = 'Su solicitud de conflicto de Horario se ha enviado con éxito. ' . $this->input->post('cruceh_msg');
                    }
                };
                $this->enviarCorreo('crear', $datos['sol_email'], 'Creación solicitud', $id_sol);
                // $msj='Su solicitud de conflicto de Horario se ha enviado con éxito. ' . $this->input->post('cruceh_msg');
            }


            /* if($this->session->userdata('rol')!=3){ //si no es estudiante destruye el pidm en la sesion
              $dato_sesion = array('pidm' => '');
              $this->session->unset_userdata($dato_sesion);
              } */
            $data = array('titulo' => 'ADMINISTRADOR DE Solicitudes', 'mensaje' => $msj, 'menu' => $menu);

            if ($this->session->userdata('rol') != 3) {
                echo $data['mensaje'];
                //echo 'OK';
            } else
                $this->load->view('solicitud_listado', $data);
        }else {


            $_per = explode(",", $_periodo[0]["par_valor"]);
            $_per = max($_per);
            $datos_estudiante = $this->integracion->datosEstudiante($this->session->userdata('pidm'), $_per);
            $primiparo = $this->integracion->esPrimiparo($this->session->userdata('pidm'), $_per);
            $sol_ssc = @$datos_estudiante['SSC'];
            $sol_primer_sem = @$primiparo['PRIMIPARO'];
            $sol_primer_semes_msg = @$primiparo['MSG'];
            $sol_opcion_estud = @$datos_estudiante['OPCION'];
            $data = array('sol_id' => $this->input->post('sol_id'),
                'sol_descripcion' => $this->input->post('sol_descripcion'),
                'tip_id' => $this->input->post('tip_id'),
                'mov_id' => $this->input->post('mov_id'),
                'attr_curso' => $this->input->post('attr_curso'),
                'attr_curso_value' => $this->input->post('attr_curso_value'),
                'accion' => 'crear',
                'mensaje' => 'Su solicitud de conflicto de Horario se ha enviado con \u00e9xito',
                'titulo' => 'Crear',
                'options_tipo' => $options_tipo,
                'options_motivo' => $options_motivo,
                'pidm' => $this->session->userdata('pidm'),
                'sol_pidm' => $this->session->userdata('pidm'),
                'rol' => $this->session->userdata('rol'),
                'menu' => $menu,
                'periodos' => explode(",", trim($_periodo[0]["par_valor"])),
                'dperiodos' => explode(":=:", trim($_dperiodo[0]["par_valor"])),
                'sol_disp_crn_ret' => $this->input->post('sol_disp_crn_ret'),
                'sol_disp_crn_ret_tipo' => $this->input->post('sol_disp_crn_ret_tipo'),
                'sol_disp_crn_ret_des' => $this->input->post('sol_disp_crn_ret_des'),
                'sol_disp_crn_ret_materia' => $this->input->post('sol_disp_crn_ret_materia'),
                'sol_disp_crn_ret_instructor' => $this->input->post('sol_disp_crn_ret_instructor'),
                'sol_disp_crn_ret_seccion' => $this->input->post('sol_disp_crn_ret_seccion'),
                'sol_disp_crn_ins' => $this->input->post('sol_disp_crn_ins'),
                'sol_disp_crn_ins_tipo' => $this->input->post('sol_disp_crn_ins_tipo'),               
				'sol_disp_crn_ins_des' =>$this->input->post('sol_disp_crn_ins_des'),
				'sol_disp_lista_cruzada' => $this->input->post('sol_disp_lista_cruzada'),
				
                'sol_disp_crn_ins_materia' => $this->input->post('sol_disp_crn_ins_materia'),
                'sol_disp_crn_ins_instructor' => $this->input->post('sol_disp_crn_ins_instructor'),
                'sol_disp_crn_ins_seccion' => $this->input->post('sol_disp_crn_ins_seccion'),
                'sol_sug_crn_ret' => $this->input->post('sol_sug_crn_ret'),
                'sol_sug_crn_ret_tipo' => $this->input->post('sol_sug_crn_ret_tipo'),
                'sol_sug_crn_ret_des' => $this->input->post('sol_sug_crn_ret_des'),
                'sol_sug_crn_ret_materia' => $this->input->post('sol_sug_crn_ret_materia'),
                'sol_sug_crn_ret_instructor' => $this->input->post('sol_sug_crn_ret_instructor'),
                'sol_sug_crn_ret_seccion' => $this->input->post('sol_sug_crn_ret_seccion'),
                'sol_sug_crn_ins' => $this->input->post('sol_sug_crn_ins'),
                'sol_sug_crn_ins_tipo' => $this->input->post('sol_sug_crn_ins_tipo'),
                'sol_sug_crn_ins_des' => $this->input->post('sol_sug_crn_ins_des'),
                'sol_sug_crn_ins_materia' => $this->input->post('sol_sug_crn_ins_materia'),
                'sol_sug_crn_ins_instructor' => $this->input->post('sol_sug_crn_ins_instructor'),
                'sol_sug_crn_ins_seccion' => $this->input->post('sol_sug_crn_ins_seccion'),
                //correquisitos
                'sol_sug_crns_cc' => $this->input->post('sol_sug_crns_cc'),
                'sol_sug_crns_cc_des' => $this->input->post('sol_sug_crns_cc_des'),
                'sol_sug_crns_cc_tipo' => $this->input->post('sol_sug_crns_cc_tipo'),
                'sol_sug_crns_cc_materia' => $this->input->post('sol_sug_crns_cc_materia'),
                'sol_sug_crns_cc_instructor' => $this->input->post('sol_sug_crns_cc_instructor'),
                'sol_sug_crns_cc_seccion' => $this->input->post('sol_sug_crns_cc_seccion'),
                'sol_sug_crn_ins' => $this->input->post('sol_sug_crn_ins'),
                'sol_ssc' => $sol_ssc,
                'sol_opcion_estud' => $sol_opcion_estud,
                'sol_primer_sem' => $sol_primer_sem,
                'sol_primer_semes_msg' => $sol_primer_semes_msg,
                'sol_tienecrucehorario' => $this->input->post('cruceh')
            );
            if ($this->session->userdata('rol') != 3) { //si no es estudiante obtiene los datos del formulario y no de la sesion
                $data['dep_id'] = $this->input->post('dep_id');
                $data['dep_id_sec'] = $this->input->post('dep_id_sec');
                $data['sol_email'] = $this->input->post('sol_email');
                $data['sol_login'] = $this->input->post('sol_login');
                $data['sol_nombre'] = $this->input->post('sol_nombre');
                $data['sol_apellido'] = $this->input->post('sol_apellido');
                $data['sol_uidnumber'] = $this->input->post('sol_uidnumber');
                $data['sol_pidm'] = $this->input->post('sol_pidm');
                $data['sol_nivel'] = $this->input->post('sol_nivel');
                $data['sol_opcion_estud'] = $this->input->post('sol_opcion_estud');
                $data['sol_ssc'] = $this->input->post('sol_ssc');
                $data['sol_primer_sem'] = $this->input->post('sol_primer_sem');
                $data['sol_primer_semes_msg'] = $this->input->post('sol_primer_semes_msg');
                $data['sol_tienecrucehorario'] = $this->input->post('cruceh');
                // JC
            }

            $this->load->view('solicitud_form', $data);
            //echo 'NO';
        }
    }

    public function historico($id) {
        $this->load->model('Parametro_model', '', TRUE);
        $periodo = $this->Parametro_model->get_item('periodo', 'par_nombre');
        $periodo = $periodo[0]['par_valor'];
        $menu = $this->Menu_model->_getmenu();
        $this->load->model('Solicitud_model', '', TRUE);
        $data = array('titulo' => 'Historico',
            'accion' => 'historico',
            'menu' => $menu,
            'items' => $this->Solicitud_model->cargarHistorico($id, $periodo),
        );
        $this->load->view('solicitud_form_historico', $data);
    }

    public function actualizar($id) {
        $menu = $this->Menu_model->_getmenu();
        $this->load->model('Tipo_model', '', TRUE);
        $options_tipo = $this->Tipo_model->get_dropdown();
        $this->load->model('Motivo_model', '', TRUE);
        $options_motivo = $this->Motivo_model->get_dropdown();
        $this->load->model('Estado_model', '', TRUE);

        if ($this->validar()) {
            $datos = array('sol_descripcion' => $this->input->post('sol_descripcion'),
                'tip_id' => $this->input->post('tip_id'),
                'mov_id' => $this->input->post('mov_id'),
                'sol_fec_actualizacion' => date("Y-m-d h:i:s"),
                'sol_mag_crn_ret_des' => $this->input->post('sol_mag_crn_ret_des'),
                'sol_mag_crn_ret' => $this->input->post('sol_mag_crn_ret'),
                'sol_mag_crn_ins_des' => $this->input->post('sol_mag_crn_ins_des'),
                'sol_mag_crn_ins' => $this->input->post('sol_mag_crn_ins'),
                'sol_com_crn_ret_des' => $this->input->post('sol_com_crn_ret_des'),
                'sol_com_crn_ret' => $this->input->post('sol_com_crn_ret'),
                'sol_com_crn_ins_des' => $this->input->post('sol_com_crn_ins_des'),
                'sol_com_crn_ins' => $this->input->post('sol_com_crn_ins'),
                'sol_fec_actualizacion' => date("Y-m-d H:i:s")
            );
            $this->Solicitud_model->update($id, $datos);
            $data = array('titulo' => 'ADMINISTRADOR DE SOLICITUDES', 'mensaje' => 'Se ha actualizado con \u00e9xito', 'menu' => $menu);
            //$this->_prepare_list($data);
            $this->load->view('solicitud_listado', $data);
        } else {
            $item = $this->Solicitud_model->get_item($id);
            //obtengo los comentarios si los hay
            $this->load->model('Comentario_model', '', TRUE);
            $comentario = $this->Comentario_model->get_item($id, 'sol_id');
            $this->_prepare_list_comentario($datac, $id, $this->Comentario_model);
            $comentario_listado = $this->load->view('comentario_listado', $datac, true);
            unset($this->Comentario_model);

            $data_comentario = array('sol_id' => $id,
                'com_texto' => @$comentario[0]['com_texto'],
                'com_nombre' => $this->session->userdata('nombres'),
                'rol_id' => $this->session->userdata('rol')
            );

            $comentario_form = $this->load->view('comentario_form', $data_comentario, true);

            //$rol_login='coordinador'; //PRUEBA
            if ((($item[0]['est_id'] == '1' || $item[0]['est_id'] == '5') && $rol_login == 'coordinador') || //en revisiï¿½n o En espera de respuesta del coordinador
                    (($item[0]['est_id'] == '4') && $rol_login == 'estudiante'))//En espera de respuesta del estudiante
                $puede_comentar = true;
            else
                $puede_comentar = false;

            $sol_ins_tipo = ($item[0]['sol_ins_tipo'] == 'mag' || $item[0]['sol_ins_tipo'] == 'com') ? $item[0]['sol_ins_tipo'] : 'NORMAL';
            if ($sol_ins_tipo != 'NORMAL')
                $sol_ins_tipo = ($item[0]['sol_ins_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
            else
                $sol_ins_tipo = $item[0]['sol_ins_crn'] != '' ? $sol_ins_tipo : '';
            $sol_ret_tipo = ($item[0]['sol_ret_tipo'] == 'mag' || $item[0]['sol_ret_tipo'] == 'com') ? $item[0]['sol_ret_tipo'] : 'NORMAL';
            if ($sol_ret_tipo != 'NORMAL')
                $sol_ret_tipo = ($item[0]['sol_ret_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
            else
                $sol_ret_tipo = $item[0]['sol_ret_crn'] != '' ? $sol_ret_tipo : '';
            $sol_sug_ins_tipo = ($item[0]['sol_sug_ins_tipo'] == 'mag' || $item[0]['sol_sug_ins_tipo'] == 'com') ? $item[0]['sol_sug_ins_tipo'] : 'NORMAL';
            if ($sol_sug_ins_tipo != 'NORMAL')
                $sol_sug_ins_tipo = ($item[0]['sol_sug_ins_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
            else
                $sol_sug_ins_tipo = $item[0]['sol_sug_ins_crn'] != '' ? $sol_sug_ins_tipo : '';
            $sol_sug_ret_tipo = ($item[0]['sol_sug_ret_tipo'] == 'mag' || $item[0]['sol_sug_ret_tipo'] == 'com') ? $item[0]['sol_sug_ret_tipo'] : 'NORMAL';
            if ($sol_sug_ret_tipo != 'NORMAL')
                $sol_sug_ret_tipo = ($item[0]['sol_sug_ret_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
            else
                $sol_sug_ret_tipo = $item[0]['sol_sug_ret_crn'] != '' ? $sol_sug_ret_tipo : '';
            $item[0]['sol_ins_tipo'] = $sol_ins_tipo;
            $item[0]['sol_ret_tipo'] = $sol_ret_tipo;
            $item[0]['sol_sug_ins_tipo'] = $sol_sug_ins_tipo;
            $item[0]['sol_sug_ret_tipo'] = $sol_sug_ret_tipo;
            $data = array(
                'sol_id' => $item[0]['sol_id'],
                'sol_descripcion' => $item[0]['sol_descripcion'],
                'tip_id' => $item[0]['tip_id'],
                'mov_id' => $item[0]['mov_id'],
                'est_id' => $item[0]['est_id'],
                'dep_id_sec' => $item[0]['dep_id_sec'],
                'sol_email' => $item[0]['sol_email'],
                'sol_nombre' => $item[0]['sol_nombre'],
                'sol_pidm' => $item[0]['sol_pidm'],
                'sol_uidnumber' => $item[0]['sol_uidnumber'],
                /* 'sol_mag_crn_ret_des' => $item[0]['sol_mag_crn_ret_des'],
                  'sol_mag_crn_ret' => $item[0]['sol_mag_crn_ret'],
                  'sol_mag_crn_ins_des' => $item[0]['sol_mag_crn_ins_des'],
                  'sol_mag_crn_ins' => $item[0]['sol_mag_crn_ins'],
                  'sol_com_crn_ret_des' => $item[0]['sol_com_crn_ret_des'],
                  'sol_com_crn_ret' => $item[0]['sol_com_crn_ret'],
                  'sol_com_crn_ins_des' => $item[0]['sol_com_crn_ins_des'],
                  'sol_com_crn_ins' => $item[0]['sol_com_crn_ins'], */
                'sol_ins_crn' => $item[0]['sol_ins_crn'],
                'sol_ret_crn' => $item[0]['sol_ret_crn'],
                'sol_ins_des' => $item[0]['sol_ins_des'],
				'sol_lista_cruzada' => $item[0]['sol_lista_cruzada'],
                'sol_ret_des' => $item[0]['sol_ret_des'],
                'sol_ins_mat' => $item[0]['sol_ins_mat'],
                'sol_ret_mat' => $item[0]['sol_ret_mat'],
                'sol_sug_ins_crn' => $item[0]['sol_sug_ins_crn'],
                'sol_sug_ret_crn' => $item[0]['sol_sug_ret_crn'],
                'sol_sug_ins_des' => $item[0]['sol_sug_ins_des'],
                'sol_sug_ret_des' => $item[0]['sol_sug_ret_des'],
                'sol_sug_ins_mat' => $item[0]['sol_sug_ins_mat'],
                'sol_sug_ret_mat' => $item[0]['sol_sug_ret_mat'],
                'sol_ins_seccion' => $item[0]['sol_ins_seccion'],
                'sol_ins_instructor' => $item[0]['sol_ins_instructor'],
                'sol_ins_tipo' => $item[0]['sol_ins_tipo'],
                'sol_ret_seccion' => $item[0]['sol_ret_seccion'],
                'sol_ret_instructor' => $item[0]['sol_ret_instructor'],
                'sol_ret_tipo' => $item[0]['sol_ret_tipo'],
                'sol_sug_ins_seccion' => $item[0]['sol_sug_ins_seccion'],
                'sol_sug_ins_instructor' => $item[0]['sol_sug_ins_instructor'],
                'sol_sug_ins_tipo' => $item[0]['sol_sug_ins_tipo'],
                'sol_sug_ret_seccion' => $item[0]['sol_sug_ret_seccion'],
                'sol_sug_ret_instructor' => $item[0]['sol_sug_ret_instructor'],
                'sol_sug_ret_tipo' => $item[0]['sol_sug_ret_tipo'],
                'accion' => 'actualizar',
                'titulo' => 'Actualizar',
                'options_tipo' => $options_tipo,
                'options_motivo' => $options_motivo,
                'comentario_form' => $comentario_form,
                'comentario_listado' => $comentario_listado,
                'puede_comentar' => $puede_comentar,
                'menu' => $menu
            );
            ;
            $this->_adicionar_foraneas($data, $this->Tipo_model, $this->Motivo_model, $this->Estado_model, false);
            $this->load->view('solicitud_form', $data);
        }
        unset($this->Tipo_model);
        unset($this->Motivo_model);
        unset($this->Estado_model);
    }

    public function comentario($id = '') {
        $menu = $this->Menu_model->_getmenu();
        //$this->load->model('Rol_model','',TRUE);
        $sol_id = $this->input->post('sol_id');
        if ($id == '') {
            $data = array('titulo' => 'ADMINISTRADOR DE SOLICITUDES', 'mensaje' => 'Se ha adicionado el comentario con \u00e9xito ', 'menu' => $menu);
            if ($this->session->userdata('rol') == '3') { //estudiante
                //$this->load->view('solicitud_listado', $data);
            } else {
                $columnas = $this->session->userdata('colocultas');
                if ($columnas) {
                    $data['ocultas'] = explode(';', $columnas);
                } else {
                    $data['ocultas'] = array();
                }
                //$this->load->view('solicitud_listado_admin', $data);
            }
            $this->index();
        } else {
            $item = $this->Solicitud_model->get_item($id);

//$item[0]['dep_id'] = 'PRUEBA'; ////////////////////////////////////////////prueba eliminar
            $hiddeActions = 0;
            if (!$this->_validar_gestion($item[0]['dep_id'])) {
                $hiddeActions = 1;
            }
//$limites_dep = $this->Limite_model->get_item($item[0]['dep_id'],'dep_id');
//$aviso = empty($limites_dep) ? 'No se encuentra registrado el programa con ID: '.$item[0]['dep_id'] : 'El Periodo de gesti&oacute;n de solicitudes ha finalizado.';
//              $this->load->view('solicitud_aviso', array('aviso'=>/*'Recuerde que con esta aplicaci&oacute;n solo se reciben solicitudes para las facultades de <strong>Derecho</strong> y <strong>Econom&iacute;a</strong>.'*/ $aviso/*'El Periodo de gesti&oacute;n de solicitudes ha finalizado.'*/,'menu'=>$menu,'titulo'=>'AVISO','no_header'=>'no','rol'=>$this->session->userdata('rol')));
//          }else{

            $this->load->model('Tipo_model', '', TRUE);
            $tipo = $this->Tipo_model->get_item($item[0]['tip_id']);
            unset($this->Tipo_model);
            $this->load->model('Motivo_model', '', TRUE);
            $motivo = $this->Motivo_model->get_item($item[0]['mov_id']);
            unset($this->Motivo_model);
            $this->load->model('Estado_model', '', TRUE);
            $estado = $this->Estado_model->get_item($item[0]['est_id']);
            $estadoPadre = $this->Estado_model->get_item($estado[0]['est_padre']);
            $estPadre = '';
            if (count($estadoPadre) > 0) {
                $estPadre = $estadoPadre[0]['est_descripcion'];
            }
            $options_estado = $this->Estado_model->get_dropdown();
            unset($this->Estado_model);
            $this->load->model('Comentario_model', '', TRUE);
            $this->load->model('Rol_model', '', TRUE);
            $comentario = $this->Comentario_model->get_item($id, 'sol_id');
            $this->_prepare_list_comentario($datac, $id, $this->Comentario_model);
            $comentario_listado = $this->load->view('comentario_listado', $datac, true);

            $this->load->model('Parametro_model', '', TRUE);
            $comentario_normal = $this->Parametro_model->get_item('comentario normal', 'par_nombre');
            $comentario_cancelar = $this->Parametro_model->get_item('comentario cancelar', 'par_nombre');
            $comentario_cambiar_estado = $this->Parametro_model->get_item('comentario cambiar estado', 'par_nombre');
            //unset($this->Parametro_model);
            $datac = array('sol_id' => $id,
                'com_texto' => @$comentario[0]['com_texto'],
                'com_nombre' => $this->session->userdata('nombres'),
                'rol_id' => $this->session->userdata('rol'),
                'accion' => 'comentario',
                'comentario_normal' => $comentario_normal[0]['par_valor'],
                'comentario_cancelar' => $comentario_cancelar[0]['par_valor'],
                'comentario_cambiar_estado' => $comentario_cambiar_estado[0]['par_valor'],
            );
            $this->_prepare_list_comentario($datac, $id, $this->Comentario_model, false);

            unset($this->Comentario_model);
            unset($this->Rol_model);
            //$this->_adicionar_foraneas_comentario($datac, $this->Rol_model, false);
            //unset($this->Rol_model);
            $comentario_form = $this->load->view('comentario_form', $datac, true);
            if ((($item[0]['est_id'] == '1'/* En revisiï¿½n */ || $item[0]['est_id'] == '5'/* En espera de respuesta del coordinador */) && ($this->session->userdata('rol') == '2'/* Coordinador */ || $this->session->userdata('rol') == '1'/* Administrador */)) ||
                    ($item[0]['est_id'] == '4'/* En espera de respuesta del estudiante */ && $this->session->userdata('rol') == '3'/* Estudiante */))
                $puede_comentar = true;
            else
                $puede_comentar = false;

            $sol_ins_tipo = ($item[0]['sol_ins_tipo'] == 'mag' || $item[0]['sol_ins_tipo'] == 'com') ? $item[0]['sol_ins_tipo'] : 'NORMAL';
            if ($sol_ins_tipo != 'NORMAL')
                $sol_ins_tipo = ($item[0]['sol_ins_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
            else
                $sol_ins_tipo = $item[0]['sol_ins_crn'] != '' ? $sol_ins_tipo : '';
            $sol_ret_tipo = ($item[0]['sol_ret_tipo'] == 'mag' || $item[0]['sol_ret_tipo'] == 'com') ? $item[0]['sol_ret_tipo'] : 'NORMAL';
            if ($sol_ret_tipo != 'NORMAL')
                $sol_ret_tipo = ($item[0]['sol_ret_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
            else
                $sol_ret_tipo = $item[0]['sol_ret_crn'] != '' ? $sol_ret_tipo : '';
            $sol_sug_ins_tipo = ($item[0]['sol_sug_ins_tipo'] == 'mag' || $item[0]['sol_sug_ins_tipo'] == 'com') ? $item[0]['sol_sug_ins_tipo'] : 'NORMAL';
            if ($sol_sug_ins_tipo != 'NORMAL')
                $sol_sug_ins_tipo = ($item[0]['sol_sug_ins_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
            else
                $sol_sug_ins_tipo = $item[0]['sol_sug_ins_crn'] != '' ? $sol_sug_ins_tipo : '';
            $sol_sug_ret_tipo = ($item[0]['sol_sug_ret_tipo'] == 'mag' || $item[0]['sol_sug_ret_tipo'] == 'com') ? $item[0]['sol_sug_ret_tipo'] : 'NORMAL';
            if ($sol_sug_ret_tipo != 'NORMAL')
                $sol_sug_ret_tipo = ($item[0]['sol_sug_ret_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
            else
                $sol_sug_ret_tipo = $item[0]['sol_sug_ret_crn'] != '' ? $sol_sug_ret_tipo : '';
            $item[0]['sol_ins_tipo'] = $sol_ins_tipo;
            $item[0]['sol_ret_tipo'] = $sol_ret_tipo;
            $item[0]['sol_sug_ins_tipo'] = $sol_sug_ins_tipo;
            $item[0]['sol_sug_ret_tipo'] = $sol_sug_ret_tipo;
            $data = array(
                'sol_id' => $item[0]['sol_id'],
                'sol_descripcion' => $item[0]['sol_descripcion'],
                'tip_id' => $item[0]['tip_id'],
                'mov_id' => $item[0]['mov_id'],
                'est_id' => $item[0]['est_id'],
                'dep_id_sec' => $item[0]['dep_id_sec'],
                'sol_email' => $item[0]['sol_email'],
                'sol_nombre' => $item[0]['sol_nombre'],
                'sol_apellido' => $item[0]['sol_apellido'],
                'sol_fec_creacion' => $item[0]['sol_fec_creacion'],
                'sol_pidm' => $item[0]['sol_pidm'],
                'sol_uidnumber' => $item[0]['sol_uidnumber'],
                /* 'sol_mag_crn_ret_des' => $item[0]['sol_mag_crn_ret_des'],
                  'sol_mag_crn_ret' => $item[0]['sol_mag_crn_ret'],
                  'sol_mag_crn_ins_des' => $item[0]['sol_mag_crn_ins_des'],
                  'sol_mag_crn_ins' => $item[0]['sol_mag_crn_ins'],
                  'sol_com_crn_ret_des' => $item[0]['sol_com_crn_ret_des'],
                  'sol_com_crn_ret' => $item[0]['sol_com_crn_ret'],
                  'sol_com_crn_ins_des' => $item[0]['sol_com_crn_ins_des'],
                  'sol_com_crn_ins' => $item[0]['sol_com_crn_ins'], */
                'sol_ins_crn' => $item[0]['sol_ins_crn'],
                'sol_ret_crn' => $item[0]['sol_ret_crn'],
                'sol_ins_des' => $item[0]['sol_ins_des'],
				'sol_lista_cruzada' => $item[0]['sol_lista_cruzada'],
                'sol_ret_des' => $item[0]['sol_ret_des'],
                'sol_ins_mat' => $item[0]['sol_ins_mat'],
                'sol_ret_mat' => $item[0]['sol_ret_mat'],
                'sol_sug_ins_crn' => $item[0]['sol_sug_ins_crn'],
                'sol_sug_ret_crn' => $item[0]['sol_sug_ret_crn'],
                'sol_sug_ins_des' => $item[0]['sol_sug_ins_des'],
                'sol_sug_ret_des' => $item[0]['sol_sug_ret_des'],
                'sol_sug_ins_mat' => $item[0]['sol_sug_ins_mat'],
                'sol_sug_ret_mat' => $item[0]['sol_sug_ret_mat'],
                'sol_ins_seccion' => $item[0]['sol_ins_seccion'],
                'sol_ins_instructor' => $item[0]['sol_ins_instructor'],
                'sol_ins_tipo' => $item[0]['sol_ins_tipo'],
                'sol_ret_seccion' => $item[0]['sol_ret_seccion'],
                'sol_ret_instructor' => $item[0]['sol_ret_instructor'],
                'sol_ret_tipo' => $item[0]['sol_ret_tipo'],
                'sol_sug_ins_seccion' => $item[0]['sol_sug_ins_seccion'],
                'sol_sug_ins_instructor' => $item[0]['sol_sug_ins_instructor'],
                'sol_sug_ins_tipo' => $item[0]['sol_sug_ins_tipo'],
                'sol_sug_ret_seccion' => $item[0]['sol_sug_ret_seccion'],
                'sol_sug_ret_instructor' => $item[0]['sol_sug_ret_instructor'],
                'sol_sug_ret_tipo' => $item[0]['sol_sug_ret_tipo'],
                'sol_attr_curso' => $item[0]['sol_attr_curso'],
                'accion' => 'comentario',
                'titulo' => 'Comentarios',
                'tipo_id' => $item[0]['est_id'],
                'tipo' => @$tipo[0]['tip_descripcion'],
                'motivo' => @$motivo[0]['mov_descripcion'],
                'estado' => @$estado[0]['est_descripcion'],
                'estadoPadre' => @$estPadre,
                'options_estado' => $options_estado,
                'comentario_form' => $comentario_form,
                'comentario_listado' => $comentario_listado,
                'puede_comentar' => $puede_comentar,
                'menu' => $menu,
                'rol_id' => $this->session->userdata('rol'),
            );
            //otros datos estudiante
            //obtengo el periodo actual
            $this->load->model('Parametro_model', '', TRUE);
            $periodo = $this->Parametro_model->get_item('periodo', 'par_nombre');
            $periodo = $periodo[0]['par_valor'];
            //
            $datos_estudiante = $this->integracion->datosEstudiante($item[0]['sol_pidm'], $periodo);
            $data['prog'] = $datos_estudiante['PROGRAMA'];
            $data['doble_prog'] = $datos_estudiante['DOBLE_PROGRAMA'];
            $data['creditos'] = $datos_estudiante['CRED_INS'];
            $data['opcion'] = $datos_estudiante['OPCION'];
            $data['ssc'] = $datos_estudiante['SSC'];
            $data['sol_primer_sem'] = $item[0]['sol_primer_sem'];
            $data['sol_primer_semes_msg'] = $item[0]['sol_primer_semes_msg'];

            //$data['sol_id'] = $sol_id;
            //obtiene una cadena 'anterior,actual,siguiente'-----------------------------------------------
            $filtros['sortorder'] = $this->session->userdata('sortorder');
            $filtros['sortname'] = $this->session->userdata('sortname');
            $filtros['qtype'] = $this->session->userdata('qtype');
            $filtros['query'] = $this->session->userdata('query');
            $filtros['qtype2'] = $this->session->userdata('qtype2');
            $filtros['query2'] = $this->session->userdata('query2');
            $filtros['query3'] = $this->session->userdata('query3');

            $ordenadas = $this->_get_filas($this->session->userdata('rol'), 0, 0, //$inicio,$this->session->userdata('cantpag'),
                    $filtros['sortorder'], $filtros['sortname'], $filtros['qtype'], $filtros['query'], $filtros['qtype2'], $filtros['query2'], $filtros['query3'], false, $item[0]['sol_id']); //imprimir

            $ordenfilas = '';
            /* echo 'sortorder '.$filtros['sortorder'].' sortname '.$filtros['sortname'];
              echo $filtros['qtype'].'<br>';
              echo $filtros['query'].'<br>';
              echo $filtros['qtype2'].'<br>';
              echo $filtros['query2'].'<br>'; */

            //print_r($ordenadas);
            // exit;
            foreach ($ordenadas as $indice => $fila) {
                //echo $fila['sol_id'].'<-<br>';
                if ($fila['sol_id'] == str_replace('-', '', $id)) {

                    $anterior = $indice == 0 ? $ordenadas[count($ordenadas) - 1]['sol_id'] : $ordenadas[$indice - 1]['sol_id'];
                    $siguiente = $indice == count($ordenadas) - 1 ? $ordenadas[0]['sol_id'] : $ordenadas[$indice + 1]['sol_id'];

                    $ordenfilas = $anterior . ';' . $fila['sol_id'] . ';' . $siguiente; //cadena 'anterior,actual,siguiente'
                }
            }
            //-----------------------------------------------------------------------------------------------
            $data['ordenfilas'] = $ordenfilas;
            $data['ordenfilas_paginado'] = $this->session->userdata('ordenfilas');
            $data['rol_botones'] = $this->session->userdata('rol');
            $data['hidde'] = ($hiddeActions) ? $hiddeActions : "";
            $this->load->view('solicitud_form_comentario', $data);
            //}
        }
    }

    public function borrar($id) {
        if ($this->Solicitud_model->delete($id)) {
            $data = array('titulo' => 'ADMINISTRADOR DE SOLICITUDES', 'mensaje' => 'Se ha borrado con \u00e9xito');
            //$this->_prepare_list($data);
            $this->load->view('solicitud_listado', $data);
        }
    }

    public function cancelar() {

        $this->load->model('Comentario_model', '', TRUE);
        $this->load->model('Rol_model', '', TRUE);
        $this->load->model('Estado_model', '', TRUE);
        $res = '';
        $sol_id = $this->input->post('sol_id');
        if ($sol_id) {
            $lista = trim($sol_id, ',');
            $lista = explode(',', $sol_id);
            foreach ($lista as $item) {
                $datosactuales = $this->Solicitud_model->get_item($item, 'sol_id');
                $estado_actual = $datosactuales[0]['est_id'];
                $texto_estado_actual = $this->Estado_model->get_item($estado_actual, 'est_id');
                $texto_estado_actual_padre = $this->Estado_model->get_item(@$texto_estado_actual[0]['est_padre'], 'est_id');
                $texto_estado_actual_padre = @$texto_estado_actual_padre[0]['est_descripcion'];
                $texto_estado_actual = ($texto_estado_actual_padre ? $texto_estado_actual_padre . " - " : "") . $texto_estado_actual[0]['est_descripcion'];

                if (!empty($item)) {

                    if ($this->session->userdata('rol') == 3) {
                        $data = array('est_id' => 23, 'sol_fec_actualizacion' => date("Y-m-d H:i:s"));
                        $name_estado = "Solicitudes canceladas por el Estudiante";
                    } else {
                        $data = array('est_id' => 6, 'sol_fec_actualizacion' => date("Y-m-d H:i:s"));
                        $name_estado = "Cancelado";
                    }
                    $item_data = $this->Solicitud_model->get_item($item);
                    $item_data_est_id = (int) @$item_data[0]["est_id"];
                    $estados_finales = array(6, 3, 14, 2, 17, 18, 19, 20, 21, 23); // LS: Validacion para las solicitudes no cancelen en estados finales.
                    $estaEnEstadoFinales = in_array($item_data_est_id, $estados_finales);
                    if (!$estaEnEstadoFinales) {
                        if ($this->Solicitud_model->update($item, $data)) {
                            $res = 'OK';
                        }
                    } else {
                        $res = '';
                    }

                    $text_cometario = ($this->input->post('com_texto') == "") ? "Sin comentario" : $this->input->post('com_texto');

                    $text_cometario .= "<br /> Se realizó un cambio de estado, se cambió de " . $texto_estado_actual . " a " . $name_estado;

                    //asocio el comentario
                    $datac = array(
                        'com_texto' => $text_cometario,
                        'com_login' => $this->session->userdata('login'),
                        'com_nombre' => $this->session->userdata('nombres'),
                        'sol_id' => $item,
                        'rol_id' => $this->session->userdata('rol'),
                    );

                    if ($datac['com_texto'] != '')
                        $this->Comentario_model->insert($datac);
                    $datos = $this->Solicitud_model->get_item($item, 'sol_id');
                    $this->enviarCorreo('cancelar', $datos[0]['sol_email'], 'Cancelación solicitud', $item);
                }
            }
        }
        $this->index();
    }

    public function estado() {

        $this->load->model('Comentario_model', '', TRUE);
        $this->load->model('Rol_model', '', TRUE);
        $this->load->model('Estado_model', '', TRUE);
        $res = '';
        $sol_id = $this->input->post('sol_id');
        $est_id = $this->input->post('est_id');
        if ($sol_id && $est_id) {
            $lista = trim($sol_id, ',');
            $lista = explode(',', $sol_id);
            foreach ($lista as $item) {
                if (!empty($item)) {
                    $data = array('est_id' => $est_id, 'sol_fec_actualizacion' => date("Y-m-d H:i:s"));
                    $data = array('est_id' => $est_id, 'sol_fec_est_actualiza' => date("Y-m-d H:i:s"));

                    $item_data = $this->Solicitud_model->get_item($item);
                    $estado_actual = $item_data[0]["est_id"];

                    $texto_estado_actual = $this->Estado_model->get_item($estado_actual, 'est_id');

                    $texto_estado_actual_padre = $this->Estado_model->get_item(@$texto_estado_actual[0]['est_padre'], 'est_id');
                    $texto_estado_actual_padre = @$texto_estado_actual_padre[0]['est_descripcion'];

                    $texto_estado_actual = ($texto_estado_actual_padre ? $texto_estado_actual_padre . " - " : "") . $texto_estado_actual[0]['est_descripcion'];

                    $texto_estado_nuevo = $this->Estado_model->get_item($est_id, 'est_id');

                    $texto_estado_nuevo_padre = $this->Estado_model->get_item(@$texto_estado_nuevo[0]['est_padre'], 'est_id');
                    $texto_estado_nuevo_padre = @$texto_estado_nuevo_padre[0]['est_descripcion'];

                    $texto_estado_nuevo = ($texto_estado_nuevo_padre ? $texto_estado_nuevo_padre . " - " : "") . $texto_estado_nuevo[0]['est_descripcion'];

                    if ($this->Solicitud_model->update($item, $data)) {
                        $res = 'OK';
                    }
                    //asocio el comentario
                    $datac = array(
                        'com_texto' => $this->input->post('com_texto'),
                        'com_login' => $this->session->userdata('login'),
                        'com_nombre' => $this->session->userdata('nombres'),
                        'sol_id' => $item,
                        'rol_id' => $this->session->userdata('rol'),
                    );

                    $text_cometario = empty($datac['com_texto']) ? "Sin comentario" : $datac['com_texto'];
                    $text_cometario .= "<br /> Se realizó un cambio de estado, se cambió de " . $texto_estado_actual . " a " . $texto_estado_nuevo;
                    $datac['com_texto'] = $text_cometario;
                    // echo "<pre>"; print_r($datac); exit;
                    if ($datac['com_texto'] != '') {
                        $this->Comentario_model->insert($datac);
                    }
                    $datos = $this->Solicitud_model->get_item($item, 'sol_id');
                    $this->enviarCorreo('estado', $datos[0]['sol_email'], 'Cambio de estado solicitud', $item);
                }
            }
        }
        //$this->index();
        if ($this->input->post('sol_id_siguiente')) {
            //$this->formaestado($this->input->post('sol_id_siguiente'));

            redirect('/solicitud/ver/' . $this->input->post('sol_id_siguiente'));
        } else {
            $this->index();
        }
    }

    /* Relaciona comentarios a una solicitud */

    public function relate($sol_id) {
        $this->load->model('Comentario_model', '', TRUE);
        $this->load->model('Rol_model', '', TRUE);

        $this->load->model('Parametro_model', '', TRUE);
        $comentario_normal = $this->Parametro_model->get_item('comentario normal', 'par_nombre');
        $comentario_cancelar = $this->Parametro_model->get_item('comentario cancelar', 'par_nombre');
        $comentario_cambiar_estado = $this->Parametro_model->get_item('comentario cambiar estado', 'par_nombre');
        unset($this->Parametro_model);
        $datac = array(
            'com_login' => $this->input->post('com_login'),
            'com_texto' => $this->input->post('com_texto'),
            'com_login' => $this->session->userdata('login'),
            'com_nombre' => $this->session->userdata('nombres'),
            'sol_id' => $sol_id,
            'rol_id' => $this->session->userdata('rol'),
            'estado' => ($this->session->userdata('rol') == "3" ? "1" : "0"),
        );
        if ($this->validar_comentario()) {
            if ($datac['com_texto'] != '')
                $this->Comentario_model->insert($datac);
            $est_id = $this->session->userdata('rol') == '3'/* estudiante */ ? '1'/* En espera de respuesta del coordinador */ : '4'; //En espera de respuesta del estudiante
            $datos = array('est_id' => $est_id, 'sol_fec_actualizacion' => date("Y-m-d H:i:s"));
            $this->Solicitud_model->update($sol_id, $datos);

            $datos = $this->Solicitud_model->get_item($sol_id, 'sol_id');
            $this->enviarCorreo('comentario', $datos[0]['sol_email'], 'Comentario solicitud', $sol_id);
            echo 'Comentario enviado correctamente.';
        }else {
            $accion = array(
                'accion' => $this->input->post('accion'),
                'comentario_normal' => $comentario_normal[0]['par_valor'],
                'comentario_cancelar' => $comentario_cancelar[0]['par_valor'],
                'comentario_cambiar_estado' => $comentario_cambiar_estado[0]['par_valor'],
            );
            $this->_prepare_list_comentario($datac, $sol_id, $this->Comentario_model, false);
            $datac = array_merge((array) $datac, $accion);
            $comentario = $this->Comentario_model->get_item($sol_id, 'sol_id');
            $this->load->view('comentario_form', $datac);
        }
        unset($this->Comentario_model);
        unset($this->Rol_model);
    }

    public function validar() {
        if ($this->session->userdata('rol') != 3) {
            $this->form_validation->set_rules('sol_login', 'Login', 'required');
            $this->form_validation->set_rules('sol_email', 'Email', 'required|valid_email');
        }
        $this->form_validation->set_rules('sol_descripcion', 'Descripción', 'max_length[300]');
        $this->form_validation->set_rules('tip_id', 'Tipo', 'required');
        $this->form_validation->set_rules('mov_id', 'Motivo', 'required');
        $this->form_validation->set_rules('sol_tyc', utf8_decode('Acepta términos y condiciones'), 'required');

        if ($this->input->post('tip_id') == '1') //inscribir materia
            $this->form_validation->set_rules('sol_disp_crn_ret_des', utf8_decode('Curso Inscripción'), 'required');
        elseif ($this->input->post('tip_id') != '') {
            $this->form_validation->set_rules('sol_disp_crn_ins_des', utf8_decode('Curso Inscripción'), 'required');
            $this->form_validation->set_rules('sol_disp_crn_ret_des', 'Curso Retiro', 'required');
        }
        return $this->form_validation->run();
    }

    public function validar_comentario() {
        $this->form_validation->set_rules('com_texto', 'Texto', 'required');
        return $this->form_validation->run();
    }

    public function page() {
        $this->load->model('Coordinador_model', '', TRUE);
        //recordar el orden---------------------------------
        $sortnames = array();

        $sortname = $this->input->post('sortname');
        $sortorder = $this->input->post('sortorder');
        $coord = new Coordinador_model();
        $columns = $coord->get_item($this->session->userdata("login"), 'coo_login');
        $currentUser = $columns[0]['coo_login'];
        $columns = $columns[0]['columnas'];
        $columns = json_decode($columns);

        if (!isset($columns->solicitud->customOrder)) {
            $columns->solicitud->customOrder = array();
        }

        $sortnames = $columns->solicitud->customOrder;

        if ($sortname != 'est_id') {
            # code...


            $insert = true;
            foreach ($sortnames as $key => &$sortnameSession) {

                if ($sortnameSession[0] == $sortname && $sortnameSession[1] != $sortorder) {
                    $sortnameSession[2] = $sortnameSession[2] + 1;

                    if ($sortnameSession[2] > 2) {

                        unset($sortnames[$key]);
                        $sortnames = array_values($sortnames);
                    } else {

                        $sortnameSession[1] = $sortorder;
                    }
                    $insert = false;
                } else if ($sortnameSession[0] == $sortname) {
                    $insert = false;
                }
            }
        } else {

            $insert = false;
        }
        if ($insert) {

            if (count($sortnames) > 2) {

                unset($sortnames[2]);
                $sortnames = array_values($sortnames);
            }
            $sortnames[] = array($sortname, $sortorder, 1);
        }

        $this->session->set_userdata('sortnames', $sortnames);
        $columns->solicitud->customOrder = $sortnames;
        $columns = json_encode($columns);
        $dataUpdate = $columns;



        $coord->updateCustomData($dataUpdate, $currentUser);

        $datos_sesion = array('sortname' => $sortname, 'sortorder' => $sortorder);
        $this->session->set_userdata($datos_sesion);
      

        //si viene algo por sesion en qtype lo pasa al post y lo elimina de la sesion
        $limpiar = false;
       

        if ($this->input->post("Val_limpiar") == "1") {
            $limpiar = true;
        }

        $qtype = ($this->input->post('qtype') ) ? $this->input->post('qtype') : $this->session->userdata('qtype');
        $query = $this->input->post('query') ? $this->input->post('query') : $this->session->userdata('query');
        $qtype2 = $this->input->post('qtype2') ? $this->input->post('qtype2') : $this->session->userdata('qtype2');
        $query2 = $this->input->post('query2') ? $this->input->post('query2') : $this->session->userdata('query2');
        $query3 = ($this->input->post('query3') && $this->input->post('query3') != null && $this->input->post('query3') != 'null') ? $this->input->post('query3') : $this->session->userdata('query3');
        
        $datos = array();
        if (empty($query)) {
            $query = $this->Coordinador_model->BDFiltros();
            $datos['bdfiltros'] = $query;
        }
        $datos_sesion = array(
            'qtype' => $qtype,
            'query' => $query,
            'qtype2' => $qtype2,
            'query2' => $query2,
            'query3' => $query3,
        );
        if ($limpiar) {
            $qtype = "sol_id";
            $query = "";
            $query2 = "";
            $query3 = "";
            $datos_sesion = array(
                'qtype' => '',
                'query' => '',
                'qtype2' => '',
                'query2' => '',
                'query3' => '',
            );
        }
        $this->session->set_userdata($datos_sesion);
        //Guarda los filtros del usuario
        $this->load->model('Coordinador_model', '', TRUE);
       
        //--------------------------------------------------

        $datos['debugfiltros'] = $this->Coordinador_model->updFiltros();
        $datos_sesion = array('cantpag' => $this->input->post('rp'));
        $this->session->set_userdata($datos_sesion);
        if ($this->input->post('otro') == 'n') {
            $datos_sesion = array('numpag' => $this->input->post('page'));
            $this->session->set_userdata($datos_sesion);
            $datos['page'] = $this->input->post('page');
        } else {
            $datos['page'] = $this->session->userdata('numpag');
        }
        $datos['otro'] = 'n';
        $this->load->model('Tipo_model', '', TRUE);
        $this->load->model('Motivo_model', '', TRUE);
        $this->load->model('Estado_model', '', TRUE);

        $datos['sortname'] = ($this->input->post('sortname') != '') ? $this->input->post('sortname') : $this->Solicitud_model->tableLlave();

        $datos['sortorder'] = ($this->input->post('sortorder') != '') ? $this->input->post('sortorder') : 'ASC';
     
        $datos['qtype'] = $qtype != '' ? $qtype : '';
        $datos['query'] = $query != '' ? $query : '';
        $datos['qtype2'] = $qtype2 != '' ? $qtype2 : '';
        $datos['query2'] = $query2 != '' ? $query2 : '';
        $datos['query3'] = $query3 != '' ? $query3 : '';

        $datos['total'] = $this->_get_count($this->session->userdata('rol'), $datos['qtype'], $datos['query'], $datos['qtype2'], $datos['query2'], $datos['query3']);


         if (($datos['page'] - 1) >= ((int) $datos['total'] / (int) $this->session->userdata('cantpag'))) {
            //$incio = 0;
            $datos['page'] = 1;
            $datos_sesion = array('numpag' => 1);
            $this->session->set_userdata($datos_sesion);
        }
        $inicio = ((int) ($datos['page']) - 1) * (int) $this->session->userdata('cantpag');
        $filas = $this->_get_filas($this->session->userdata('rol'), $inicio, $this->session->userdata('cantpag'), $datos['sortorder'], $datos['sortname'], $datos['qtype'], $datos['query'], $datos['qtype2'], $datos['query2'], $datos['query3'], false);

       
        //-------------------------------------------------------------------------
        foreach ($filas as $item) {

            $item['sol_fec_creacion'] = substr($item['sol_fec_creacion'], 0, -3);
            $profesores = '';
            $instructor = explode(',', $item['sol_ins_instructor']);
            foreach ($instructor as $unp) {
                $profesores .= $unp . '<br>';
            }
            $columnas = array();
          
            $this->_adicionar_foraneas($item, $this->Tipo_model, $this->Motivo_model, $this->Estado_model, false);
            $color = $this->_get_color($item['est_id']);
            $celda = array();
            $rolI = $this->session->userdata["rol"];

            $alternativa1 = "";
            $alternativa2 = "";
            
            $alternativas = json_decode($item["sol_alternativas"], TRUE);
            if (count($alternativas) > 0) {
                $alternativa1 = @$alternativas[0]["crn"];
                $alternativa2 = @$alternativas[1]["crn"];
            }


            $img = (AMBIENTE_PRUEBAS == '1') ? $_SERVER['SERVER_NAME'] . "/css/images/advertencia.png" : $_SERVER['SERVER_NAME'] . "/css/images/advertencia.png";
            $img_cruce = (AMBIENTE_PRUEBAS == '1') ? $_SERVER['SERVER_NAME'] . "/css/images/cruce.fw.png" : $_SERVER['SERVER_NAME'] . "/css/images/cruce.fw.png";
            $imgtag_cruce = ($item['sol_tienecrucehorario'] == 1) ? "<img src='http://" . $img_cruce . "' style='width: 21px !important; position: absolute; margin-left: -41px;margin-top: 1px; ' />" : "";
            if($rolI == 1 || $rolI == 2){
                $celda[] = "<input type='checkbox' onclick='marcar_registro(this)' class='marca_seg' value='" . $item['sol_id'] . "' " . ($item['sol_marca'] == "si" ? "checked" : "") . " />";
            }

            if (trim($item['sol_attr_curso_value']) == "") {
                $celda[] = $item['sol_id'];
            } else {
                $celda[] = "<div title='" . $item['sol_id'] . "' >" . $item['sol_id'] . (trim($item['sol_attr_curso']) != "" ? "<span class='circle-text'>" . $item['sol_attr_curso'] . "</span>" : "") . "</div>";
            }
            $celda[] = "<div title='" . $item['est_descripcion'] . "' style='position: relative; cursor: default'>" . $color . ( ((($item['est_id'] == '1')) && $this->session->userdata('rol') != 3) ? "<img src='http://" . $img . "' style='width: 13px !important; position: absolute; margin-left: -23px;margin-top: 1px; ' />" : "") . $imgtag_cruce . ($item['cta'] > 0 && $rolI != 3 ? "<div class='alert_msg_com' title='Comentarios sin leer'>" . ($item['cta'] > 99 ? "+" . $item['cta'] : $item['cta']) . "</div>" : "") . "</div>";

            $celda[] = $item['sol_fec_creacion'];

            $celda[] = $item['sol_uidnumber'];

            $celda[] = $item['sol_login'];

            $celda[] = $item['sol_nombre'];

            $celda[] = $item['sol_apellido'];

            $celda[] = $item['sol_prog'];

            $celda[] = $item['sol_doble_prog'];

            $celda[] = $item['sol_creditos'];

            $celda[] = $item['sol_periodo'];

            $celda[] = $item['sol_ins_mat'];

            $celda[] = $item['sol_ins_crn'];

            $celda[] = $item['sol_ins_des'];
			$celda[] = $item['sol_lista_cruzada'];

            $celda[] = $item['sol_ins_seccion'];

            $celda[] = $item['tip_descripcion'];

            $celda[] = $item['mov_descripcion'];
            $celda[] = $alternativa1;
            $celda[] = $alternativa2;
            $celda[] = $item['sol_descripcion'];
            $celda[] = $profesores;
            $celda[] = $item['sol_opcion_estud'];
            $celda[] = $item['sol_ssc'];
            $celda[] = $item['sol_primer_sem'];
            $celda[] = $item['sol_fec_est_actualiza'];

            $datos['rows'][] = array(
                'id' => $item['sol_id'],
                'cell' => $celda
            );
        }

        $datos["filters"] = $sortnames = $this->session->userdata("sortnames");
        ;
        echo json_encode($datos);
        unset($this->Tipo_model);
        unset($this->Motivo_model);
        unset($this->Estado_model);
    }

//test--------------------------------------------------------------------------------------------------------------------------
    public function page2() {
        //print_r($_POST);
        //recordar el orden---------------------------------
        $sortname = $this->input->post('sortname');
        $sortorder = $this->input->post('sortorder');
        $datos_sesion = array('sortname' => $sortname, 'sortorder' => $sortorder);
        $this->session->set_userdata($datos_sesion);
        //si viene algo por sesion en qtype lo pasa al post y lo elimina de la sesion
        $qtype = $this->session->userdata('qtype') != '' ? $this->session->userdata('qtype') : $this->input->post('qtype');
        $query = $this->session->userdata('qtype') != '' ? $this->session->userdata('query') : $this->input->post('query');
        $qtype2 = $this->session->userdata('qtype') != '' ? $this->session->userdata('qtype2') : $this->input->post('qtype2');
        $query2 = $this->session->userdata('qtype') != '' ? $this->session->userdata('query2') : $this->input->post('query2');
        $query3 = $this->session->userdata('query3') != '' ? $this->session->userdata('query3') : $this->input->post('query3');
        $datos_sesion = array(
            'qtype' => '',
            'query' => '',
            'qtype2' => '',
            'query2' => '',
            'query3' => '',
        );
        $this->session->set_userdata($datos_sesion);
        //--------------------------------------------------

        $datos = array();
        $datos_sesion = array('cantpag' => $this->input->post('rp'));
        $this->session->set_userdata($datos_sesion);
        if ($this->input->post('otro') == 'n') {
            $datos_sesion = array('numpag' => $this->input->post('page'));
            $this->session->set_userdata($datos_sesion);
            $datos['page'] = $this->input->post('page');
        } else {
            $datos['page'] = $this->session->userdata('numpag');
        }

        $datos['otro'] = 'n';
        $this->load->model('Tipo_model', '', TRUE);
        $this->load->model('Motivo_model', '', TRUE);
        $this->load->model('Estado_model', '', TRUE);

        $datos['sortname'] = ($this->input->post('sortname') != '') ? $this->input->post('sortname') : $this->Solicitud_model->tableLlave();
        $datos['sortorder'] = ($this->input->post('sortorder') != '') ? $this->input->post('sortorder') : 'ASC';
        /* $datos['qtype'] = ($this->input->post('qtype')!='')?$this->input->post('qtype'):'';
          $datos['query'] = ($this->input->post('query')!='')?$this->input->post('query'):'';
          $datos['qtype2'] = ($this->input->post('qtype2')!='')?$this->input->post('qtype2'):'';
          $datos['query2'] = ($this->input->post('query2')!='')?$this->input->post('query2'):''; */

        $datos['qtype'] = $qtype != '' ? $qtype : '';
        $datos['query'] = $query != '' ? $query : '';
        $datos['qtype2'] = $qtype2 != '' ? $qtype2 : '';
        $datos['query2'] = $query2 != '' ? $query2 : '';
        $datos['query3'] = $query3 != '' ? $query3 : '';

        $datos['total'] = $this->_get_count2(($this->input->post('imprimir_consulta') == '1' ? true : false), $this->session->userdata('rol'), $datos['qtype'], $datos['query'], $datos['qtype2'], $datos['query2'], $datos['query3']);

        $inicio = ((int) ($datos['page']) - 1) * (int) $this->session->userdata('cantpag');

//jmeter-----------------------------------------------------------------------
        $filas = $this->_get_filas2(($this->input->post('rol') != '' ? $this->input->post('rol') : $this->session->userdata('rol')), ($this->input->post('cantpag') != '' ? ((int) ($this->input->post('page')) - 1) * (int) $this->input->post('cantpag') : $inicio), ($this->input->post('cantpag') != '' ? $this->input->post('cantpag') : $this->session->userdata('cantpag')), $datos['sortorder'], $datos['sortname'], $datos['qtype'], $datos['query'], $datos['qtype2'], $datos['query2'], $datos['query3'], ($this->input->post('imprimir_consulta') == '1' ? true : false));


        //relaciona fila anterior y siguiente--------------------------------------
        $ordenfilas = '';
        foreach ($filas as $indice => $fila) {
            $ordenfilas .= $ordenfilas != '' ? ';' : '';
            $ordenfilas .= $fila['sol_id'];
            $datos_sesion = array('ordenfilas' => $ordenfilas);
            $this->session->set_userdata($datos_sesion);
            $filas[$indice]['sol_id_anterior'] = $indice == 0 ? $filas[count($filas) - 1]['sol_id'] : $filas[$indice - 1]['sol_id'];
            $filas[$indice]['sol_id_siguiente'] = $indice == count($filas) - 1 ? $filas[0]['sol_id'] : $filas[$indice + 1]['sol_id'];
        }
        //-------------------------------------------------------------------------


        $img = (AMBIENTE_PRUEBAS == '1') ? $_SERVER['SERVER_NAME'] . "/conflictohorario/css/images/advertencia.png" : $_SERVER['SERVER_NAME'] . "/css/images/advertencia.png";
        $img_cruce = (AMBIENTE_PRUEBAS == '1') ? $_SERVER['SERVER_NAME'] . "/conflictohorario/css/images/cruce.fw.png" : $_SERVER['SERVER_NAME'] . "/css/images/cruce.fw.png";


        foreach ($filas as $item) {
            $item['sol_fec_creacion'] = substr($item['sol_fec_creacion'], 0, -3);
            $profesores = '';
            $instructor = explode(',', $item['sol_ins_instructor']);
            foreach ($instructor as $unp) {
                $profesores .= $unp . '<br>';
            }
            $columnas = array();
            $ocultas = $this->session->userdata('colocultas');
            if ($ocultas) {
                $columnas = explode(';', $ocultas);
            }
            $this->_adicionar_foraneas($item, $this->Tipo_model, $this->Motivo_model, $this->Estado_model, false);
            $color = $this->_get_color($item['est_id']);
            $celda = array();

            $ordencols = explode(';', $this->session->userdata('ordencol'));
            if (is_array($ordencols) && count($ordencols) > 0 && $this->session->userdata('ordencol') != '') {
                foreach ($ordencols as $ordencol) {
                    switch ($ordencol) {
                        case 'est_id':
                            $contenidocelda = "<div title='" . $item['est_descripcion'] . "' style='cursor: default'>" . $color . ( ((($item['est_id'] == '1') || ($item['est_id'] == '5')) && $this->session->userdata('rol') != 3) ? "<img src='http://" . $img . "' style='width: 13px !important; position: absolute; margin-left: -23px;margin-top: 1px; ' />" : "") . "</div>";
                            break;
                        case 'tip_id':
                            $contenidocelda = $item['tip_descripcion'];
                            break;
                        case 'mov_id':
                            $contenidocelda = $item['mov_descripcion'];
                            break;
                        case 'sol_ins_instructor':
                            $contenidocelda = $profesores;
                        default:
                            $contenidocelda = array_key_exists($ordencol, $item) ? $item[$ordencol] : 'contenidocelda';
                    }
                    //if(!in_array($ordencol, $columnas))
                    $celda[] = $contenidocelda;
                }
            } else {
                //se deben enviar TODOS los datos en $celda ya que luego se ocultan las columnas
                //if(!in_array('sol_id',$columnas))
                $celda[] = $item['sol_id'];
                //if(!in_array('est_id',$columnas))
                $celda[] = "<div title='" . $item['est_descripcion'] . "' style='cursor: default'>" . $color . ( ((($item['est_id'] == '1') || ($item['est_id'] == '5')) && $this->session->userdata('rol') != 3) ? "<img src='http://" . $img . "' style='width: 13px !important; position: absolute; margin-left: -23px;margin-top: 1px; ' />" : "") . "</div>";
                //if(!in_array('sol_fec_creacion',$columnas))
                $celda[] = $item['sol_fec_creacion'];
                //if(!in_array('sol_uidnumber',$columnas))
                $celda[] = $item['sol_uidnumber'];
                //if(!in_array('sol_login',$columnas))
                $celda[] = $item['sol_login'];
                //if(!in_array('sol_nombre',$columnas))
                $celda[] = $item['sol_nombre'];
                //if(!in_array('sol_apellido',$columnas))
                $celda[] = $item['sol_apellido'];
                //if(!in_array('sol_ins_mat',$columnas))
                $celda[] = $item['sol_ins_mat'];
                //if(!in_array('sol_ins_crn',$columnas))
                $celda[] = $item['sol_ins_crn'];
                //if(!in_array('sol_ins_des',$columnas))
                $celda[] = $item['sol_ins_des'];
				$celda[] = $item['sol_lista_cruzada'];
                //if(!in_array('sol_ins_seccion',$columnas))
                $celda[] = $item['sol_ins_seccion'];
                //if(!in_array('tip_id',$columnas))
                $celda[] = $item['tip_descripcion'];
                //if(!in_array('mov_id',$columnas))
                $celda[] = $item['mov_descripcion'];
                //if(!in_array('sol_descripcion',$columnas))
                $celda[] = $item['sol_descripcion'];
                //if(!in_array('sol_ins_instructor',$columnas))
                $celda[] = $profesores;
            }
            $datos['rows'][] = array(
                'id' => $item['sol_id'],
                'cell' => $celda
            );
        }
        echo json_encode($datos);
        unset($this->Tipo_model);
        unset($this->Motivo_model);
        unset($this->Estado_model);
    }

//------------------------------------------------------------------------------------------------------------------------------

    private function _prepare_list(&$data) {
        $this->load->model('Tipo_model', '', TRUE);
        $this->load->model('Motivo_model', '', TRUE);
        $this->load->model('Estado_model', '', TRUE);
        $data['total_rows'] = $this->Solicitud_model->get_count();
        $filas = $this->Solicitud_model->get_all();
        $datos = array();
        foreach ($filas as $item) {
            $this->_adicionar_foraneas($item, $this->Tipo_model, $this->Motivo_model, $this->Estado_model, false);
            $datos['rows'][] = array(
                'id' => $item['sol_id'],
                'cell' => array($item['sol_id'], $item['sol_descripcion'], $item['tip_descripcion'], $item['mov_descripcion'], $item['est_descripcion'])
            );
        }
        $data['$filas'] = json_encode($datos);
        unset($this->Tipo_model);
        unset($this->Motivo_model);
        unset($this->Estado_model);
    }

    private function _prepare_list_comentario(&$datac, $id, $comentario_model, $filas = true) {
        $this->load->library('pagination');
        $config_page['base_url'] = '/index.php/comentario/page/';
        $config_page['total_rows'] = $this->Comentario_model->get_count();
        $config_page['per_page'] = 20;
        $this->pagination->initialize($config_page);
        $datac['filas'] = $this->Comentario_model->get_item($id, 'sol_id');
        $this->_adicionar_foraneas_comentario($datac, $this->Rol_model, $filas);
        $datac['paginacion'] = $this->pagination->create_links();
    }

    public function ayuda($id_input) {
        $data['secciones'] = array('3131823' => 'seccion 1', '3131923' => 'seccion 2', '3131423' => 'seccion 3', '3133123' => 'seccion 4', '31311   23' => 'seccion 5');
        $data['id_input'] = $id_input;
        $this->load->view('ayuda', $data);
    }

    public function ayudaMinicartelera($tip_id, $id_input, $valor = '', $tipo = '', $pidm = '', $codEstudiante = 0, $periodo, $crn_cc = "") {
        $this->load->model('Parametro_model', 'Parametro_model_crn', TRUE);
        // $periodo = $this->Parametro_model_crn->get_item('periodo','par_nombre');
        // $periodo = $periodo[0]['par_valor'];
        //print "tip_id $tip_id, id_input $id_input, valor $valor, tipo $tipo, pidm $pidm<br>";
        $data['titulo'] = 'Minicartelera';
        $data['tip_id'] = $tip_id;
        $data['tipo_solicitud'] = $solType;
        //$data['id_input'] = $id_input;
        $data['valor'] = $valor;
        $data['idEstudiante'] = (!isset($codEstudiante)) ? $this->session->userdata('UACarnetEstudiante') : $codEstudiante; //;
        $data['tipo'] = $tipo;
        $data['pidm'] = $pidm;
        $data['periodo'] = $periodo;
        $data['option_prog'] = array();
        //$data['secciones'] = array('19603'=>'seccion 1','18461'=>'seccion 2','3131423'=>'seccion 3','3133123'=>'seccion 4','3131123'=>'seccion 5');
        // si el tipo es complementaria solo muestra las de su magistral
        $data['secciones'] = ($tipo == 'com' && strpos($id_input, '_sug_') !== false) ? $this->integracion->esMagistral($valor, $periodo) : $this->integracion->materiasInscritas($pidm, $periodo);
        $data['secciones'] = ($tipo == 'com' && strpos($id_input, '_sug_') !== false && $id_input == 'sol_sug_crn_ret' && $tip_id != '1') ? array_intersect_assoc($this->integracion->esMagistral($valor, $periodo), $this->integracion->materiasInscritas($pidm, $periodo)) : $data['secciones'];

        //var_dump($crn_cc);

        if ($id_input == "sol_sug_crns_cc") {
            $listaCorrequisitos = $this->integracion->listarCorrequisitos($crn_cc);

            foreach ($listaCorrequisitos as $key => $value) {
                $correquisitos[$value['CRN']] = $value;
            }
            $data['secciones'] = $correquisitos;
        }


        //echo "<pre>";print_r($data['secciones']);exit;
        $data['tiposSecciones'] = array();
        if (is_array($data['secciones'])) {
            foreach ($data['secciones'] as $id => $dato) {
                $data['secciones'][$id] = $dato['TITULO'];
                $data['materias'][$id] = $dato['MATERIA'];
                $data['las_secciones'][$id] = $dato['SECCION'];
                $data['titulos'][$id] = $dato['TITULO'];
                $data['profesores'][$id] = $dato['PROFESORES'];
                $data['lab_comp'][$id] = $this->integracion->esComplementaria($id, $periodo) ? 1 : 0;
                $data['tiposSecciones'][$id] = $this->validar_crn($id, '', $periodo);

                if ($id_input == "sol_sug_crns_cc") {

                    $data['tiposSecciones'][$id] = "cc";
                }
                // echo "<pre>";
                // print_r($data['tiposSecciones']);
                // echo "</pre>";
                if (($data['tiposSecciones'][$id] == 'com' || $data['tiposSecciones'][$id] == 'mag') && strpos($id_input, '_disp_') !== false) { //si es disparador complementaria se adicionan los datos de su magistral key2 y seccion2
                    $secciones2 = $data['tiposSecciones'][$id] == 'com' ? $this->integracion->esComplementaria($id, $periodo) : array_intersect_assoc($this->integracion->esMagistral($id, $periodo), (array) $this->integracion->materiasInscritas($pidm, $periodo));
                    if (is_array($secciones2)) {
                        foreach ($secciones2 as $key2 => $valor2) {

                            $data['key2'][$id] = $key2;
                            $data['seccion2'][$id] = $valor2;
                            $_POST["periodo"] = $periodo;
                            $magistral = $this->integracion->vistaMinicartelera(array(OPCION1 => $key2)); //busca todos los datos de la magistral
                            $data['materias2'][$id] = $magistral[1]['MATERIA'];
                            $data['las_secciones2'][$id] = $magistral[1]['SECCION'];
                            $data['profesores2'][$id] = $magistral[1]['PROFESORES'];
                            if ($data['tiposSecciones'][$id] == 'mag')
                                $data['seccion2'][$id] = $magistral[1]['TITULO'];
                        }
                    }
                }
            }
        }

        $data['id_input'] = $id_input;
        /* $programas_activos = @$this->integracion->programasActivos();
          foreach($programas_activos as $llave => $valor) {
          $data['option_prog'] = array('dep_id'=>$llave, 'dep_nombre'=>utf8_encode($valor));
          } */
        // echo "<pre>";
        // print_r($data);
        // echo "</pre>";
        $data["tipo_sol"] = $tip_id;
        $data['option_prog'] = @$this->integracion->programasActivos();
        array_unshift($data['option_prog'], "Seleccione Programa");
        $this->load->view('mini_cartelera', $data);
    }

    //ccastellanos 26/03/2015
    public function ayudaMinicartelera2($materia) {

        $data['fieldId'] = $this->input->get('field');

        $this->load->model('Parametro_model', 'Parametro_model_crn', TRUE);
        $data['secciones'] = $this->integracion->alternativas($materia, $this->input->get('crn'), $this->input->get('name'));

        $data['tiposSecciones'] = array();
        if (is_array($data['secciones'])) {
            foreach ($data['secciones'] as $id => $dato) {
                $data['secciones'][$id] = $dato['TITULO'];
                $data['materias'][$id] = $dato['MATERIA'];
                $data['las_secciones'][$id] = $dato['SECCION'];
                $data['titulos'][$id] = $dato['TITULO'];
                $data['profesores'][$id] = $dato['PROFESORES'];
                $data['lab_comp'][$id] = $this->integracion->esComplementaria($id, $periodo) ? 1 : 0;
                $data['tiposSecciones'][$id] = $this->validar_crn($id, '', $periodo);
                // echo "<pre>";
                // print_r($data['tiposSecciones']);
                // echo "</pre>";
                if (($data['tiposSecciones'][$id] == 'com' || $data['tiposSecciones'][$id] == 'mag') && strpos($id_input, '_disp_') !== false) { //si es disparador complementaria se adicionan los datos de su magistral key2 y seccion2
                    $secciones2 = $data['tiposSecciones'][$id] == 'com' ? $this->integracion->esComplementaria($id, $periodo) : array_intersect_assoc($this->integracion->esMagistral($id, $periodo), (array) $this->integracion->materiasInscritas($pidm, $periodo));
                    if (is_array($secciones2)) {
                        foreach ($secciones2 as $key2 => $valor2) {

                            $data['key2'][$id] = $key2;
                            $data['seccion2'][$id] = $valor2;
                            $_POST["periodo"] = $periodo;
                            $magistral = $this->integracion->vistaMinicartelera(array(OPCION1 => $key2)); //busca todos los datos de la magistral
                            $data['materias2'][$id] = $magistral[1]['MATERIA'];
                            $data['las_secciones2'][$id] = $magistral[1]['SECCION'];
                            $data['profesores2'][$id] = $magistral[1]['PROFESORES'];
                            if ($data['tiposSecciones'][$id] == 'mag')
                                $data['seccion2'][$id] = $magistral[1]['TITULO'];
                        }
                    }
                }
            }
        }

        $this->load->view('mini_cartelera2', $data);
    }

    public function busquedaMinicartelera() {
        $this->load->model('Parametro_model', 'Parametro_model_crn', TRUE);
        // $periodo = $this->Parametro_model_crn->get_item('periodo','par_nombre');
        // $periodo = $periodo[0]['par_valor'];
        $periodo = $this->input->post("periodo");
        $btn = $this->input->post('busqueda');
        $actual = $this->input->post('actual');
        $filas = $this->input->post('filas');

        if (empty($actual))
            $actual = 0;
        if (empty($filas))
            $filas = PAGINAS;

        $parametros['busqueda'] = array();

        //Para cambio de secciï¿½n CRN Inscripciï¿½n debe tener el mismo t?tulo que CRN Retiro
        if ($this->input->post('valor') != '' && ($this->input->post('tip_id') == '2') && $this->input->post('id_input') == 'sol_disp_crn_ins') {
            //$titulos = $this->integracion->obtenerTituloMateria($this->input->post('valor')); //Se tiene en cuenta el periodo al momento de consultar el titulo de la materia
            $titulos = $this->integracion->obtenerTituloMateria($this->input->post('valor'), $this->input->post('periodo'));
            $parametros['busqueda']['TITULO_RET'] = (!empty($titulos[0]['TITULO'])) ? $titulos[0]['TITULO'] : "";
        }

        if ($this->input->post('valor') != '' && ($this->input->post('tip_id') == '4') && $this->input->post('id_input') == 'sol_disp_crn_ins') {
            $parametros['busqueda']['CRN__RET'] = $this->input->post('valor');
        }

        if ($btn == 'buscar1')
            $parametros['busqueda'][OPCION1] = $crn = $this->input->post('crn');
        if ($btn == 'buscar2') {
            $programidtmp = $this->input->post('programa_id');
            $materia1tmp = $this->input->post('materia1');
            if ($programidtmp != '0' && !empty($materia1tmp)) {
                $opcion22 = $programidtmp . $materia1tmp;
            }
            $parametros['busqueda'][OPCION21] = $programa = $this->input->post('programa_id');
            $parametros['busqueda'][OPCION22] = $materia1 = $opcion22;
            $parametros['busqueda'][OPCION23] = $seccion1 = $this->input->post('seccion1');
        }
        if ($btn == 'buscar3') {
            $parametros['busqueda'][OPCION31] = $this->input->post('materia2');
            $parametros['busqueda'][OPCION32] = $this->input->post('seccion2');
        }
        if ($btn == 'buscar4') {
            $parametros['busqueda'][OPCION41] = $this->input->post('profesor');
            $parametros['busqueda'][OPCION42] = $this->input->post('profesor');
            $parametros['busqueda'][OPCION43] = $this->input->post('profesor');
        }
        $datos['boton'] = $btn;
        $datos['actual'] = $actual;
        $datos['filas'] = $filas;

        switch ($this->input->post('campo_orden')) {
            case 'CRN';
                $campo_orden = 'crn';
                break;
            case 'MATERIA':
                $campo_orden = 'materia||curso';
                break;
            case 'SECCION':
                $campo_orden = 'seccion';
                break;
            case 'TITULO':
                $campo_orden = 'titulo';
                break;
            case 'PROFESORES':
                $campo_orden = "profesor_1||','||profesor_2||','||profesor_3"; //'profesores';
                break;
            default:
                $campo_orden = 'crn';
        }
        $orden = $this->input->post('orden') != '' ? $this->input->post('orden') : 'asc';
        $registros = $this->integracion->vistaMinicartelera($parametros['busqueda'], $actual, $filas, $campo_orden, $orden);
//echo "registros...";print_r($registros);
        $total = array_shift($registros);
        $datos['total'] = $total;

        $orden_crn = $orden_materia = $orden_seccion = $orden_titulo = $orden_profesores = $ordenados = $crn_ordenados = array();
        foreach ($registros as $indice => $regisro) {
            $orden_crn[$regisro['CRN']] = $regisro['CRN'];
            $orden_materia[$regisro['CRN']] = $regisro['MATERIA'];
            $orden_seccion[$regisro['CRN']] = $regisro['SECCION'];
            $orden_titulo[$regisro['CRN']] = $regisro['TITULO'];
            $orden_profesores[$regisro['CRN']] = $regisro['PROFESORES'];
            $regisro['tipo'] = $this->validar_crn($regisro['CRN']);
            $secciones = $this->integracion->esComplementaria($regisro['CRN'], $periodo);
            if ($regisro['tipo'] == 'com' && strpos($this->input->post('id_input'), '_disp_') !== false) { //si es disparador complementaria se adicionan los datos de su magistral key2 y seccion2
                $secciones2 = $this->integracion->esComplementaria($regisro['CRN'], $periodo);
                if (is_array($secciones2)) {
                    foreach ($secciones2 as $key2 => $valor2) {
                        $regisro['key2'] = $key2;
                        $regisro['seccion2'] = $valor2;
                        $magistral = $this->integracion->vistaMinicartelera(array(OPCION1 => $key2)); //busca todos los datos de la magistral
                        $regisro['materias2'] = $magistral[1]['MATERIA'];
                        $regisro['las_secciones2'] = $magistral[1]['SECCION'];
                        $regisro['profesores2'] = $magistral[1]['PROFESORES'];
                    }
                }
            }
            $registros[$indice] = array_merge((array) $registros[$indice], $regisro);
        }
        $datos['registros'] = $registros;
        $datos['id_input'] = $this->input->post('id_input');
        $datos["periodo"] = $periodo;
        $correquisitos = array();
        //Se identifican los CRNs con correquisitos: CRNs_CC
        foreach ($datos['registros'] as $key => $value) {
            $arrayCorrequisitos = explode(';', $this->integracion->crnsRelacionados($value['CRN'], $periodo));
            //echo "<pre>";print_r($arrayCorrequisitos);exit;
            $strCRNs_CC = "";
            if ($arrayCorrequisitos[0] == 'M' . $value['CRN'] || $arrayCorrequisitos[0] == 'N' . $value['CRN']) {//Si es magistral
                foreach ($arrayCorrequisitos as $k2 => $v2) {
                    $pos = strpos($v2, 'CC');
                    if ($pos !== false) {//Si existe un correquisto en los CRNs asociados a la magistral
                        $strCRNs_CC .= $v2 . '-';
                    }
                }
                $strCRNs_CC = trim($strCRNs_CC, '-');
            }
            $datos['registros'][$key]['CRNs_CC'] = $strCRNs_CC;
        }
        //echo "<pre>";print_r($datos);exit;
        $this->load->view('resultados_mini_cartelera', $datos);
    }

    public function ajaxsearch() {
        $function_name = $this->input->post('function_name');
        $description = $this->input->post('description');
        echo $this->function_model->getSearchResults($function_name, $description);
    }

    public function search() {
        $data['title'] = "Code Igniter Search Results";
        $function_name = $this->input->post('function_name');
        $data['search_results'] = $this->function_model->getSearchResults($function_name);
        $this->load->view('application/search', $data);
    }

    private function _adicionar_foraneas(&$item, $tipo_model, $motivo_model, $estado_model, $filas = true) {
        if ($filas) { //con filas para listado
            if (is_array($item['filas'])) {
                $n = 0;
                foreach ($item['filas'] as $fila) {
                    $tipo = $tipo_model->get_item($fila['tip_id']);
                    $motivo = $motivo_model->get_item($fila['mov_id']);
                    $estado = $estado_model->get_item($fila['est_id']);
                    $filanueva = array_merge($fila, (array) @$tipo[0], (array) @$motivo[0], (array) @$estado[0]);
                    $item['filas'][$n] = $filanueva;
                    $n++;
                }
            }
        } else { //sin filas para formulario
            $tipo = $tipo_model->get_item($item['tip_id']);
            $motivo = $motivo_model->get_item($item['mov_id']);
            $estado = $estado_model->get_item($item['est_id']);
            $datanueva = array_merge($item, (array) @$tipo[0], (array) @$motivo[0], (array) @$estado[0]);
            $item = $datanueva;
        }
    }

    private function _adicionar_foraneas_comentario(&$datac, $rol_model, $filas = true) {
        if ($filas) { //con filas para listado
            if (is_array($datac['filas'])) {
                $n = 0;
                foreach ($datac['filas'] as $fila) {
                    $rol = $rol_model->get_item($fila['rol_id']);
                    $filanueva = array_merge($fila, (array) $rol[0]);
                    $datac['filas'][$n] = $filanueva;
                    $n++;
                }
            }
        } else { //sin filas para formulario
            $rol = $rol_model->get_item($datac['rol_id']);
            $datanueva = array_merge($datac, (array) $rol[0]);
            $datac = $datanueva;
        }
    }

    /* Revisa los permisos y segun el usuario entrega un tipo de men? */

    private function _getmenu() {
        $this->load->model('Rol_model', '', TRUE);
        $rol_name = $this->Rol_model->get_item($this->session->userdata('rol'));
        $this->load->model('Coordinador_model', '', TRUE);
        $coo_data = $this->Coordinador_model->get_item($this->session->userdata('login'), 'coo_login');
        $niv_id = @$coo_data[0]['niv_id'];
        $dep_id = @$coo_data[0]['dep_id'];
        $this->load->model('Nivel_model', '', TRUE);

        $dep_ids = explode('*', $this->session->userdata('programas'));
        $programas = '';
        $this->load->library('integracion');
        if (is_array($dep_ids)) {
            foreach ($dep_ids as $dep_id) {
                $dep_descripcion = $this->integracion->obtenerPrograma($dep_id);
                if (@$dep_descripcion != '') {
                    $programas .= $programas != '' ? '-' : '';
                    $programas .= @$dep_descripcion;
                }
            }
        } else {
            $dep_descripcion = $this->integracion->obtenerPrograma($dep_ids);
            $programas = @$dep_descripcion;
        }
        $niv_ids = explode('*', $this->session->userdata('niveles'));
        $niveles = '';
        if (is_array($niv_ids)) {
            foreach ($niv_ids as $niv_id) {
                $niv_descripcion = $this->Nivel_model->get_item($niv_id);
                $niv_descripcion = @$niv_descripcion[0]['niv_descripcion'];
                if (@$niv_descripcion != '') {
                    $niveles .= $niveles != '' ? '-' : '';
                    $niveles .= @$niv_descripcion;
                }
            }
        } else {
            $niv_descripcion = $this->Nivel_model->get_item($niv_id);
            $niv_descripcion = @$niv_descripcion[0]['niv_descripcion'];
            $niveles .= @$niv_descripcion;
        }

        $menu = '';
        $data = array(
            'nombres' => $this->session->userdata('nombres'),
            'apellidos' => $this->session->userdata('apellidos'),
            'codigo' => $this->session->userdata('UACarnetEstudiante'),
            'programa' => $programas,
            'niveles' => $niveles,
            'usuario' => $this->session->userdata('login'),
            'rol_name' => @$rol_name[0]['rol_descripcion'],
            'mostrar_bloqueo' => ""
        );
        if ($this->session->userdata('logged_in')) {
            if ($this->session->userdata('rol') == 1) {
                $menu = $this->load->view('_menu_admin', $data, true);
            } else {
                $menu = $this->load->view('_menu_normal', $data, true);
            }
        } else {
            redirect('/auth');
            return false;
        }
        return $menu;
    }

    /* Obtiene las filas segun */

    private function _get_filas($rol, $inicio, $pr, $order, $sortname, $qtype, $query, $qtype2, $query2, $query3, $imprimir = false, $sol_id = "") {

        if ($imprimir)
            echo "<br>rol $rol, inicio $inicio, pr $pr, order $order, sortname $sortname, qtype $qtype, query $query, qtype2 $qtype2, query2 $query2";
        // echo "<br>rol $rol, inicio $inicio, pr $pr, order $order, sortname $sortname, qtype $qtype, query $query, qtype2 $qtype2, query2 $query2";

        $this->load->model('Parametro_model', 'Parametro_model_get_filas', TRUE);
        $fecha_inicial_listado = $this->Parametro_model_get_filas->get_item('fecha inicial', 'par_nombre');
        // echo "Parametro_model_get_filas"; exit;
        $fecha_inicial_listado = $fecha_inicial_listado[0]['par_valor'];
        //echo "fecha_inicial_listado $fecha_inicial_listado";
        unset($this->Parametro_model_get_filas);
        $filas = array();

        switch ($rol) {
            case 1:
                // echo "<pre>";
                // print_r($_GET);
                // echo "</pre>";
                // echo "Sol: ".$sol_id."<br>";
                $filas = $this->Solicitud_model->get_all($inicio, $pr, $order, $sortname, $qtype, $query, '', '', $qtype2, $query2, $query3, $imprimir, $fecha_inicial_listado, $sol_id);
                break;
            case 2:
                $this->load->model('Coordinador_model', '', TRUE);
                $coo_data = $this->Coordinador_model->get_item($this->session->userdata('login'), 'coo_login');
                $materia_id = @$coo_data[0]['materia_id'];

                $programas = explode('*', $this->session->userdata('programas'));
                $niveles = explode('*', $this->session->userdata('niveles'));
                $materias = explode('*', $materia_id);

                $filas = $this->Solicitud_model->get_all_coordinador($inicio, $pr, $order, $sortname, $qtype, $query, $programas, 'dep_id', $programas, $niveles, $qtype2, $query2, $query3, $fecha_inicial_listado, $materias);
                break;
            case 3:
                $login = $this->session->userdata('login');
                // $filas   = $this->Solicitud_model->get_all($inicio,$pr,$order,$sortname,$qtype,$query,'','',$qtype2,$query2,$query3, $imprimir, $fecha_inicial_listado);
                $filas = $this->Solicitud_model->get_all($inicio, $pr, $order, $sortname, $qtype, $query, $login, 'sol_login', $qtype2, $query2, $query3, $imprimir, $fecha_inicial_listado);
                break;
        }

        return $filas;
    }

    private function _get_count($rol, $qtype, $query, $qtype2, $query2, $query3) {
        // print_r($query);
        // echo"<br /> query 2 <br />  ".$query2;exit;

        $this->load->model('Parametro_model', 'Parametro_model_get_count', TRUE);
        $fecha_inicial_listado = $this->Parametro_model_get_count->get_item('fecha inicial', 'par_nombre');
        $fecha_inicial_listado = $fecha_inicial_listado[0]['par_valor'];
        unset($this->Parametro_model_get_count);

        $filas = array();
        switch ($rol) {
            case 1:
                $filas = $this->Solicitud_model->get_count($qtype, $query, '', '', $qtype2, $query2, $query3, $fecha_inicial_listado);
                break;
            case 2:
                $this->load->model('Coordinador_model', '', TRUE);
                $coo_data = $this->Coordinador_model->get_item($this->session->userdata('login'), 'coo_login');
                $materia_id = @$coo_data[0]['materia_id'];
                $programas = explode('*', $this->session->userdata('programas'));
                $niveles = explode('*', $this->session->userdata('niveles'));
                $materias = explode('*', $materia_id);
                $filas = $this->Solicitud_model->get_count_coordinador($qtype, $query, $programas, 'dep_id', $programas, $niveles, $qtype2, $query2, $query3, $fecha_inicial_listado, $materias);
                break;
            case 3:
                $login = $this->session->userdata('login');
                $filas = $this->Solicitud_model->get_count($qtype, $query, $login, 'sol_login', $qtype2, $query2, $query3, $fecha_inicial_listado);
                break;
        }
        return $filas;
    }

//test--------------------------------------------------------------------------------------------------------------------------
    private function _get_filas2($rol, $inicio, $pr, $order, $sortname, $qtype, $query, $qtype2, $query2, $query3, $imprimir = false) {
        //if($imprimir)echo "<br>rol $rol, inicio $inicio, pr $pr, order $order, sortname $sortname, qtype $qtype, query $query, qtype2 $qtype2, query2 $query2";
        $this->load->model('Solicitud_model2', 'Solicitud_model_get_filas2', TRUE);

        $this->load->model('Parametro_model', 'Parametro_model_get_filas', TRUE);
        $fecha_inicial_listado = $this->Parametro_model_get_filas->get_item('fecha inicial', 'par_nombre');
        $fecha_inicial_listado = $fecha_inicial_listado[0]['par_valor'];
        //echo "fecha_inicial_listado $fecha_inicial_listado";
        unset($this->Parametro_model_get_filas);

        $filas = array();
        switch ($rol) {
            case 1:
                $filas = $this->Solicitud_model_get_filas2->get_all($imprimir, $inicio, $pr, $order, $sortname, $qtype, $query, '', '', $qtype2, $query2, $query3, $imprimir, $fecha_inicial_listado);
                break;
            case 2:
                //$programas = explode('*',$this->session->userdata('programas'));
                //$niveles = explode('*',$this->session->userdata('niveles'));
//jmeter-----------------------------------------------------------------------
                $programas = explode('*', ($this->input->post('programas') != '' ? $this->input->post('programas') : $this->session->userdata('programas')));
                $niveles = explode('*', ($this->input->post('niveles') != '' ? $this->input->post('niveles') : $this->session->userdata('niveles')));
                $filas = $this->Solicitud_model_get_filas2->get_all_coordinador($imprimir, $inicio, $pr, $order, $sortname, $qtype, $query, $programas, 'dep_id', $programas, $niveles, $qtype2, $query2, $query3, $fecha_inicial_listado);
                break;
            case 3:
                $login = $this->session->userdata('login');
                $filas = $this->Solicitud_model_get_filas2->get_all($imprimir, $inicio, $pr, $order, $sortname, $qtype, $query, '', '', $qtype2, $query2, $query3, $imprimir, $fecha_inicial_listado);
                $filas = $this->Solicitud_model_get_filas2->get_all($imprimir, $inicio, $pr, $order, $sortname, $qtype, $query, $login, 'sol_login', $qtype2, $query2, $query3, $imprimir, $fecha_inicial_listado);
                break;
        }
        unset($this->Solicitud_model_get_filas2);
        return $filas;
    }

    private function _get_count2($imprimir, $rol, $qtype, $query, $qtype2, $query2, $query3) {
        $this->load->model('Solicitud_model2', 'Solicitud_model_get_count2', TRUE);
        $this->load->model('Parametro_model', 'Parametro_model_get_count', TRUE);
        $fecha_inicial_listado = $this->Parametro_model_get_count->get_item('fecha inicial', 'par_nombre');
        $fecha_inicial_listado = $fecha_inicial_listado[0]['par_valor'];
        unset($this->Parametro_model_get_count);

        $filas = array();
        switch ($rol) {
            case 1:
                $filas = $this->Solicitud_model_get_count2->get_count($imprimir, $qtype, $query, '', '', $qtype2, $query2, $query3, $fecha_inicial_listado);
                break;
            case 2:
                //$programas = explode('*',$this->session->userdata('programas'));
                //$niveles = explode('*',$this->session->userdata('niveles'));
//jmeter-----------------------------------------------------------------------
                $programas = explode('*', ($this->input->post('programas') != '' ? $this->input->post('programas') : $this->session->userdata('programas')));
                $niveles = explode('*', ($this->input->post('niveles') != '' ? $this->input->post('niveles') : $this->session->userdata('niveles')));
                $filas = $this->Solicitud_model_get_count2->get_count_coordinador($imprimir, $qtype, $query, $programas, 'dep_id', $programas, $niveles, $qtype2, $query2, $query3, $fecha_inicial_listado);
                break;
            case 3:
                $login = $this->session->userdata('login');
                $filas = $this->Solicitud_model_get_count2->get_count($imprimir, $qtype, $query, $login, 'sol_login', $qtype2, $query2, $query3, $fecha_inicial_listado);
                break;
        }
        unset($this->Solicitud_model_get_count2);
        return $filas;
    }

//------------------------------------------------------------------------------------------------------------------------------

    private function _get_color($id) {


        $color = '';
        $height = '10px';
        switch ($id) {
            case 1 ://En revisiï¿½n
                $color = '#CC0000'; //'#5B86EA';
                break;
            case 16 :// Solicitudes No Exitosas
            case 2 ://  Finalizado sin soluciï¿½n
            case 17 :// conflicto horario
            case 18 :// Falta prerrequisito
            case 19 :// No tiene creditos
            case 20 :// Restricciï¿½n
            case 21 :// Secciï¿½n llena
                $color = '#7030A0'; //'#339933';
                break;
            case 10 ://Solicitudes Exitosas
            case 3 ://Finalizado con soluciï¿½n
            case 14 ://Se agregï¿½ otra seccion
                $color = '#00B050'; //'#66CC66';
                break;
            case 4 ://En espera de respuesta del estudiante
                $color = '#FFC000'; //'#CC9900';
                break;
            case 5 ://En espera de respuesta del coordinador
                $color = '#FF8000'; //'#CC6600';
                break;
            case 12 ://Solicitudes Ignoradas
            case 6 ://Cancelado
            case 7 ://No corresponde
            case 13 ://Formato Duplicado
            case 15 ://Ya estaba inscrita
            case 23 ://Ya estaba inscrita
                $color = '#000000'; //'#333333';
                break;
            case 11 ://Lista espera
                $color = '#0070C0'; //'#CC3300';
                break;
        }
        //debe ser span y no div porque el width interfiere con la redimensi?n de las columnas a la derecha de la columna estado
        return '<span style="background-color:' . $color . ';height:' . $height . '">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>';
    }

    public function validar_crn($crn, $val = '', $periodo = '') {
        $this->load->library('integracion');
        // $this->load->model('Parametro_model','Parametro_model_crn',TRUE);
        // $periodo = $this->Parametro_model_crn->get_item('periodo','par_nombre');
        // $periodo = $periodo[0]['par_valor'];
        if ($periodo == '') {
            $periodo = $this->input->post("periodo");
        }
        //unset($this->Parametro_model_crn);

        if ($val == '') { //saber si es magistral o complementaria
            $rta = ($this->integracion->esMagistral($crn, $periodo) !== false) ? 'mag' : 'no';
            //print_r($this->integracion->esMagistral($crn,));
            if ($rta === 'no')
                $rta = ($this->integracion->esComplementaria($crn, $periodo) !== false) ? 'com' : 'no';
        }
        else { //comprobar si es magistral o complementaria pasando su valor
            switch ($val) {
                case 'mag' :
                    $rta = ($this->integracion->esMagistral($crn, $periodo) !== false) ? 1 : 0;
                    break;
                case 'com' :
                    $rta = ($this->integracion->esComplementaria($crn, $periodo) !== false) ? 1 : 0;
                    break;
            }
        }
        $rta = $rta === 'no' ? 0 : $rta;

        // echo "validar_crn $crn - $rta<br>";
        return $rta;
    }

    /* obtiene datos segun el numero de carn? */

    public function carne() {
        $carne = $this->input->post('carne');
        if ($carne) {
            //$carne = trim($carne);
            $pidm = $this->integracion->obtenerPidm($carne);
            if ($pidm) {
                //obtengo el periodo actual
                $this->load->model('Parametro_model', '', TRUE);
                $periodo = $this->Parametro_model->get_item('periodo', 'par_nombre');
                $periodo = $periodo[0]['par_valor'];
                $datos_estudiante = $this->integracion->datosEstudiante($pidm, $periodo);
                $sol_primer_sem = $this->integracion->esPrimiparo($pidm, $periodo);
                $aux = $this->auth_ldap->cargarDatos($carne);
                $datos_estudiante['LOGIN'] = $aux['login'];
                $datos_estudiante['CORREOU'] = $aux['correouniandes'];

                //print_r($datos_estudiante);

                $retorno_carne = array(
                    'sol_pidm' => $pidm,
                    'sol_uidnumber' => $carne,
                    'sol_nombre' => ucwords(mb_strtolower($datos_estudiante["NOMBRES"], 'UTF-8')),
                    //'sol_apellido'=>(ucwords(strtolower(utf8_decode($datos_estudiante["APELLIDOS"])))),
                    'sol_apellido' => ucwords(mb_strtolower(utf8_decode($datos_estudiante["APELLIDOS"]), 'UTF-8')),
                    'dep_id' => $datos_estudiante["CODIGO_PROGRAMA"],
                    'dep_id_sec' => $datos_estudiante["DOBLE_PROGRAMA"],
                    'sol_nivel' => $datos_estudiante["NIVEL"],
                    'sol_login' => $datos_estudiante["LOGIN"],
                    'sol_email' => $datos_estudiante["CORREOU"],
                    'sol_opcion_estud' => $datos_estudiante["OPCION"],
                    'sol_ssc' => $datos_estudiante["SSC"],
                    'sol_primer_sem' => $sol_primer_sem['PRIMIPARO'],
                    'sol_primer_semes_msg' => $sol_primer_sem['MSG']
                );

                // JC
                //obtengo el nivel
                $niveles = $this->Parametro_model->get_item('niveles', 'par_nombre');
                $niveles = $niveles[0]['par_valor'];
                if ($niveles == '1') {
                    if ($datos_estudiante['NIVEL'] != PREGRADO && $datos_estudiante['NIVEL'] != MAESTRIA) {
                        echo 'nivel_no_permitido';
                    } else
                        echo json_encode($retorno_carne);
                } else
                    echo json_encode($retorno_carne);
            } else
                echo 'NO';
        } else
            echo 'NO';
    }

    /* valida si es posible la creacion segun las fechas de apertura */

    private function _validar_crear($prog = '') {
        /* filtro de limite de creacion  segun el programa principal del estudiante */
        $programa = $prog != '' ? $prog : $this->session->userdata('programas');
        //el programa no se pasa por parametro ni esta en la sesi?n para usuario administrador, no validar? limites
        if (empty($prog) || $prog == '') {
            return true;
        }
        $this->load->model('Limite_model', '', TRUE);

        $limites = $this->Limite_model->get_item($programa, 'dep_id');
        if (empty($limites)) { //no se encontro el programa
            $res = FALSE;
        } else {
            $res = $this->Limite_model->validar_rango_fechas($limites[0]["lim_fec_a_sol"], $limites[0]["lim_fec_c_sol"], date("Y-m-d H:i:s"));
        }
        unset($this->Limite_model);
        return $res;
    }

    private function _validar_crear2($prog = '') {
        $programa = $prog != '' ? $prog : $this->session->userdata('programas');
        if (empty($prog) || $prog == '') {
            return true;
        }
        $this->load->model('Limite_model', '', TRUE);
        $limites = $this->Limite_model->get_item($programa, 'dep_id');
        if (@$limites[0]["sol_creacion"] != "1") {
            $res = true;
        } else {
            $res = false;
        }
        return $res;
    }

    /* valida si es posible la creacion segun las fechas de gestion si pasa el id del
      departamento lo valida para este sino del que haya en sesion */

    private function _validar_gestion($id_programa = '') {
        /* filtro de limite de creacion  segun el programa principal del estudiante */
        if (empty($id_programa) || $id_programa == '') {
            $id_programa = $this->session->userdata('programas');
        }
        //el programa no se pasa por parametro ni esta en la sesi?n para usuario administrador, no validar? limites
        if (empty($id_programa) || $id_programa == '') {
            return true;
        }
        $this->load->model('Limite_model', '', TRUE);
        $limites = $this->Limite_model->get_item($id_programa, 'dep_id');
        if (empty($limites))//no se encontrï¿½ el programa
            $res = FALSE;
        else
            $res = $this->Limite_model->validar_rango_fechas($limites[0]["lim_fec_a_ges"], $limites[0]["lim_fec_c_ges"], date("Y-m-d H:i:s"));
        //var_dump($res);
        //unset($this->Limite_model);
        return $res;
        /**/
    }

    public function ver($sol_id) {

        $menu = $this->Menu_model->_getmenu();
        $data = array();
        if ($sol_id) {
            $item = $this->Solicitud_model->get_item($sol_id);
            $this->load->model('Tipo_model', '', TRUE);
            $tipo = $this->Tipo_model->get_item($item[0]['tip_id']);
            unset($this->Tipo_model);
            $this->load->model('Motivo_model', '', TRUE);
            $motivo = $this->Motivo_model->get_item($item[0]['mov_id']);
            unset($this->Motivo_model);
            $this->load->model('Estado_model', '', TRUE);
            $estado = $this->Estado_model->get_item($item[0]['est_id']);
            $estadoPadre = $this->Estado_model->get_item($estado[0]['est_padre']);
            $estPadre = '';
            if (count($estadoPadre) > 0) {
                $estPadre = $estadoPadre[0]['est_descripcion'];
            }
            $options_estado = $this->Estado_model->get_dropdown();
            unset($this->Estado_model);

            $sol_ins_tipo = ($item[0]['sol_ins_tipo'] == 'mag' || $item[0]['sol_ins_tipo'] == 'com') ? $item[0]['sol_ins_tipo'] : 'NORMAL';
            if ($sol_ins_tipo != 'NORMAL')
                $sol_ins_tipo = ($item[0]['sol_ins_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
            else
                $sol_ins_tipo = $item[0]['sol_ins_crn'] != '' ? $sol_ins_tipo : '';
            $sol_ret_tipo = ($item[0]['sol_ret_tipo'] == 'mag' || $item[0]['sol_ret_tipo'] == 'com') ? $item[0]['sol_ret_tipo'] : 'NORMAL';
            if ($sol_ret_tipo != 'NORMAL')
                $sol_ret_tipo = ($item[0]['sol_ret_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
            else
                $sol_ret_tipo = $item[0]['sol_ret_crn'] != '' ? $sol_ret_tipo : '';
            $sol_sug_ins_tipo = ($item[0]['sol_sug_ins_tipo'] == 'mag' || $item[0]['sol_sug_ins_tipo'] == 'com') ? $item[0]['sol_sug_ins_tipo'] : 'NORMAL';
            if ($sol_sug_ins_tipo != 'NORMAL')
                $sol_sug_ins_tipo = ($item[0]['sol_sug_ins_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
            else
                $sol_sug_ins_tipo = $item[0]['sol_sug_ins_crn'] != '' ? $sol_sug_ins_tipo : '';
            $sol_sug_ret_tipo = ($item[0]['sol_sug_ret_tipo'] == 'mag' || $item[0]['sol_sug_ret_tipo'] == 'com') ? $item[0]['sol_sug_ret_tipo'] : 'NORMAL';
            if ($sol_sug_ret_tipo != 'NORMAL')
                $sol_sug_ret_tipo = ($item[0]['sol_sug_ret_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
            else
                $sol_sug_ret_tipo = $item[0]['sol_sug_ret_crn'] != '' ? $sol_sug_ret_tipo : '';

            $item[0]['sol_ins_tipo'] = $sol_ins_tipo;
            $item[0]['sol_ret_tipo'] = $sol_ret_tipo;
            $item[0]['sol_sug_ins_tipo'] = $sol_sug_ins_tipo;
            $item[0]['sol_sug_ret_tipo'] = $sol_sug_ret_tipo;
            $data = array('sol_id' => $item[0]['sol_id'],
                'sol_descripcion' => $item[0]['sol_descripcion'],
                'tip_id' => $item[0]['tip_id'],
                'mov_id' => $item[0]['mov_id'],
                'est_id' => $item[0]['est_id'],
                'dep_id_sec' => $item[0]['dep_id_sec'],
                'sol_email' => $item[0]['sol_email'],
                'sol_nombre' => $item[0]['sol_nombre'],
                'sol_apellido' => $item[0]['sol_apellido'],
                'sol_fec_creacion' => $item[0]['sol_fec_creacion'],
                'sol_pidm' => $item[0]['sol_pidm'],
                'sol_uidnumber' => $item[0]['sol_uidnumber'],
                'sol_login' => $item[0]['sol_login'],
                /* 'sol_mag_crn_ret_des' => $item[0]['sol_mag_crn_ret_des'],
                  'sol_mag_crn_ret' => $item[0]['sol_mag_crn_ret'],
                  'sol_mag_crn_ins_des' => $item[0]['sol_mag_crn_ins_des'],
                  'sol_mag_crn_ins' => $item[0]['sol_mag_crn_ins'],
                  'sol_com_crn_ret_des' => $item[0]['sol_com_crn_ret_des'],
                  'sol_com_crn_ret' => $item[0]['sol_com_crn_ret'],
                  'sol_com_crn_ins_des' => $item[0]['sol_com_crn_ins_des'],
                  'sol_com_crn_ins' => $item[0]['sol_com_crn_ins'], */
                'sol_ins_crn' => $item[0]['sol_ins_crn'],
                'sol_ret_crn' => $item[0]['sol_ret_crn'],
                'sol_ins_des' => $item[0]['sol_ins_des'],
				'sol_lista_cruzada' => $item[0]['sol_lista_cruzada'],
                'sol_ret_des' => $item[0]['sol_ret_des'],
                'sol_ins_mat' => $item[0]['sol_ins_mat'],
                'sol_ret_mat' => $item[0]['sol_ret_mat'],
                'sol_sug_ins_crn' => $item[0]['sol_sug_ins_crn'],
                'sol_sug_ret_crn' => $item[0]['sol_sug_ret_crn'],
                'sol_sug_ins_des' => $item[0]['sol_sug_ins_des'],
                'sol_sug_ret_des' => $item[0]['sol_sug_ret_des'],
                'sol_sug_ins_mat' => $item[0]['sol_sug_ins_mat'],
                'sol_sug_ret_mat' => $item[0]['sol_sug_ret_mat'],
                'sol_ins_seccion' => $item[0]['sol_ins_seccion'],
                'sol_ins_instructor' => $item[0]['sol_ins_instructor'],
                'sol_ins_tipo' => $item[0]['sol_ins_tipo'],
                'sol_ret_seccion' => $item[0]['sol_ret_seccion'],
                'sol_ret_instructor' => $item[0]['sol_ret_instructor'],
                'sol_ret_tipo' => $item[0]['sol_ret_tipo'],
                'sol_sug_ins_seccion' => $item[0]['sol_sug_ins_seccion'],
                'sol_sug_ins_instructor' => $item[0]['sol_sug_ins_instructor'],
                'sol_sug_ins_tipo' => $item[0]['sol_sug_ins_tipo'],
                'sol_attr_curso' => $item[0]['sol_attr_curso'],
                'sol_sug_ret_seccion' => $item[0]['sol_sug_ret_seccion'],
                'sol_sug_ret_instructor' => $item[0]['sol_sug_ret_instructor'],
                'sol_sug_ret_tipo' => $item[0]['sol_sug_ret_tipo'],
                'tipo' => @$tipo[0]['tip_descripcion'],
                'motivo' => @$motivo[0]['mov_descripcion'],
                'estado' => @$estado[0]['est_descripcion'],
                'estadoPadre' => @$estPadre,
                'options_estado' => $options_estado
            );
            $this->load->model('Comentario_model', '', TRUE);
            $this->load->model('Rol_model', '', TRUE);

            $this->load->model('Parametro_model', '', TRUE);
            $comentario_normal = $this->Parametro_model->get_item('comentario normal', 'par_nombre');
            $comentario_cancelar = $this->Parametro_model->get_item('comentario cancelar', 'par_nombre');
            $comentario_cambiar_estado = $this->Parametro_model->get_item('comentario cambiar estado', 'par_nombre');
            //unset($this->Parametro_model);
            $datac = array('sol_id' => $sol_id,
                'com_nombre' => $this->session->userdata('nombres'),
                'rol_id' => $this->session->userdata('rol'),
                'accion' => '',
                'comentario_normal' => $comentario_normal[0]['par_valor'],
                'comentario_cancelar' => $comentario_cancelar[0]['par_valor'],
                'comentario_cambiar_estado' => $comentario_cambiar_estado[0]['par_valor'],
            );
            unset($this->Comentario_model);
            unset($this->Rol_model);
            $comentario_form = $this->load->view('comentario_form', $datac, true);

            $data['sol_id'] = $sol_id;
            $data['accion'] = 'estado';
            $data['titulo'] = 'Detalle';
            $data['comentario_form'] = $comentario_form;
            $data['menu'] = $menu;
            //otros datos estudiante
            //obtengo el periodo actual
            $this->load->model('Parametro_model', '', TRUE);
            $periodo = $this->Parametro_model->get_item('periodo', 'par_nombre');
            $periodo = $periodo[0]['par_valor'];
            // echo $item[0]['sol_periodo']."<br>";
            // echo $item[0]['sol_pidm'];
            $datos_estudiante = $this->integracion->datosEstudiante($item[0]['sol_pidm'], $item[0]['sol_periodo']);
            // $data['prog'] = $datos_estudiante['PROGRAMA'];
            // $data['doble_prog'] = $datos_estudiante['DOBLE_PROGRAMA'];
            // $data['creditos']= $datos_estudiante['CRED_INS'];
            // $data['opcion']= $datos_estudiante['OPCION'];
            // $data['ssc']= $datos_estudiante['SSC'];
            $data['prog'] = $item[0]["sol_prog"];
            $data['doble_prog'] = $item[0]["sol_doble_prog"];
            $data['creditos'] = $item[0]["sol_creditos"];
            $data['opcion'] = $item[0]["sol_opcion_estud"];
            $data['ssc'] = $item[0]["sol_ssc"];
            $data['sol_id'] = $sol_id;
            //obtiene una cadena 'anterior,actual,siguiente'-----------------------------------------------
            $filtros['sortorder'] = $this->session->userdata('sortorder');
            $filtros['sortname'] = $this->session->userdata('sortname');
            $filtros['qtype'] = $this->session->userdata('qtype');
            $filtros['query'] = $this->session->userdata('query');

            $filtros['qtype2'] = $this->session->userdata('qtype2');
            $filtros['query2'] = $this->session->userdata('query2');
            $filtros['query3'] = $this->session->userdata('query3');

            $ordenadas = $this->_get_filas($this->session->userdata('rol'), 0, 0, //$inicio,$this->session->userdata('cantpag'),
                    $filtros['sortorder'], $filtros['sortname'], $filtros['qtype'], $filtros['query'], $filtros['qtype2'], $filtros['query2'], $filtros['query3'], false, $sol_id); //imprimir
            // echo "AA";
            // exit;
            $ordenfilas = '';
            /* echo 'sortorder '.$filtros['sortorder'].' sortname '.$filtros['sortname'];
              echo $filtros['qtype'].'<br>';
              echo $filtros['query'].'<br>';
              echo $filtros['qtype2'].'<br>';
              echo $filtros['query2'].'<br>'; */

            // echo "<pre>";
            // print_r($ordenadas);
            // echo "</pre>";
            foreach ($ordenadas as $indice => $fila) {
                //echo $fila['sol_id'].'<-<br>';
                // echo $fila['sol_id']."<br>";
                if ($fila['sol_id'] == str_replace('-', '', $sol_id)) {

                    $anterior = $indice == 0 ? $ordenadas[count($ordenadas) - 1]['sol_id'] : $ordenadas[$indice - 1]['sol_id'];
                    $siguiente = $indice == count($ordenadas) - 1 ? $ordenadas[0]['sol_id'] : $ordenadas[$indice + 1]['sol_id'];

                    $ordenfilas = $anterior . ';' . $fila['sol_id'] . ';' . $siguiente; //cadena 'anterior,actual,siguiente'
                }
            }

            //-----------------------------------------------------------------------------------------------
            $data['ordenfilas'] = $ordenfilas;
            $data['ordenfilas_paginado'] = $this->session->userdata('ordenfilas');
            $data['rol_botones'] = $this->session->userdata('rol');
            $data["sol_periodo"] = $item[0]["sol_periodo"];
            ;

            $sol_ins_cupos = $this->integracion->cupos_inscritos($data["sol_ins_crn"], $item[0]['sol_periodo']);
            $data["sol_ins_cupos"] = (int) @$sol_ins_cupos[1];
            $data["sol_ins_cupos_ins"] = (int) @$sol_ins_cupos[2];

            $sol_ret_cupos = $this->integracion->cupos_inscritos($data["sol_ret_crn"], $item[0]['sol_periodo']);
            $data["sol_ret_cupos"] = (int) @$sol_ret_cupos[1];
            $data["sol_ret_cupos_ins"] = (int) @$sol_ret_cupos[2];

            $sol_sug_cupos = $this->integracion->cupos_inscritos($data["sol_sug_ins_crn"], $item[0]['sol_periodo']);
            $data["sol_sug_cupos"] = (int) @$sol_sug_cupos[1];
            $data["sol_sug_cupos_ins"] = (int) @$sol_sug_cupos[2];

            $sol_sug_ret_cupos = $this->integracion->cupos_inscritos($data["sol_sug_ret_crn"], $item[0]['sol_periodo']);
            $data["sol_sug_ret_cupos"] = (int) @$sol_sug_ret_cupos[1];
            $data["sol_sug_ret_cupos_ins"] = (int) @$sol_sug_ret_cupos[2];
            $data["tip_id"] = $item[0]["tip_id"];
            $this->load->view('solicitud_form_int', $data);
        }
    }

    public function detalle($sol_id) {
        $menu = $this->Menu_model->_getmenu();
        $data = array();
        if ($sol_id) {
            $item = $this->Solicitud_model->get_item($sol_id);
            $this->load->model('Tipo_model', '', TRUE);
            $tipo = $this->Tipo_model->get_item($item[0]['tip_id']);
            unset($this->Tipo_model);
            $this->load->model('Motivo_model', '', TRUE);
            $motivo = $this->Motivo_model->get_item($item[0]['mov_id']);
            unset($this->Motivo_model);
            $this->load->model('Estado_model', '', TRUE);
            $estado = $this->Estado_model->get_item($item[0]['est_id']);
            $estadoPadre = $this->Estado_model->get_item($estado[0]['est_padre']);
            $estPadre = '';
            if (count($estadoPadre) > 0) {
                $estPadre = $estadoPadre[0]['est_descripcion'];
            }
            $options_estado = $this->Estado_model->get_dropdown();
            unset($this->Estado_model);

            $sol_ins_tipo = ($item[0]['sol_ins_tipo'] == 'mag' || $item[0]['sol_ins_tipo'] == 'com') ? $item[0]['sol_ins_tipo'] : 'NORMAL';
            if ($sol_ins_tipo != 'NORMAL')
                $sol_ins_tipo = ($item[0]['sol_ins_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
            else
                $sol_ins_tipo = $item[0]['sol_ins_crn'] != '' ? $sol_ins_tipo : '';
            $sol_ret_tipo = ($item[0]['sol_ret_tipo'] == 'mag' || $item[0]['sol_ret_tipo'] == 'com') ? $item[0]['sol_ret_tipo'] : 'NORMAL';
            if ($sol_ret_tipo != 'NORMAL')
                $sol_ret_tipo = ($item[0]['sol_ret_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
            else
                $sol_ret_tipo = $item[0]['sol_ret_crn'] != '' ? $sol_ret_tipo : '';
            $sol_sug_ins_tipo = ($item[0]['sol_sug_ins_tipo'] == 'mag' || $item[0]['sol_sug_ins_tipo'] == 'com') ? $item[0]['sol_sug_ins_tipo'] : 'NORMAL';
            if ($sol_sug_ins_tipo != 'NORMAL')
                $sol_sug_ins_tipo = ($item[0]['sol_sug_ins_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
            else
                $sol_sug_ins_tipo = $item[0]['sol_sug_ins_crn'] != '' ? $sol_sug_ins_tipo : '';
            $sol_sug_ret_tipo = ($item[0]['sol_sug_ret_tipo'] == 'mag' || $item[0]['sol_sug_ret_tipo'] == 'com') ? $item[0]['sol_sug_ret_tipo'] : 'NORMAL';
            if ($sol_sug_ret_tipo != 'NORMAL')
                $sol_sug_ret_tipo = ($item[0]['sol_sug_ret_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
            else
                $sol_sug_ret_tipo = $item[0]['sol_sug_ret_crn'] != '' ? $sol_sug_ret_tipo : '';

            $item[0]['sol_ins_tipo'] = $sol_ins_tipo;
            $item[0]['sol_ret_tipo'] = $sol_ret_tipo;
            $item[0]['sol_sug_ins_tipo'] = $sol_sug_ins_tipo;
            $item[0]['sol_sug_ret_tipo'] = $sol_sug_ret_tipo;
            $data = array('sol_id' => $item[0]['sol_id'],
                'sol_descripcion' => $item[0]['sol_descripcion'],
                'tip_id' => $item[0]['tip_id'],
                'mov_id' => $item[0]['mov_id'],
                'est_id' => $item[0]['est_id'],
                'dep_id_sec' => $item[0]['dep_id_sec'],
                'sol_email' => $item[0]['sol_email'],
                'sol_nombre' => $item[0]['sol_nombre'],
                'sol_apellido' => $item[0]['sol_apellido'],
                'sol_fec_creacion' => $item[0]['sol_fec_creacion'],
                'sol_pidm' => $item[0]['sol_pidm'],
                'sol_uidnumber' => $item[0]['sol_uidnumber'],
                'sol_login' => $item[0]['sol_login'],
                /* 'sol_mag_crn_ret_des' => $item[0]['sol_mag_crn_ret_des'],
                  'sol_mag_crn_ret' => $item[0]['sol_mag_crn_ret'],
                  'sol_mag_crn_ins_des' => $item[0]['sol_mag_crn_ins_des'],
                  'sol_mag_crn_ins' => $item[0]['sol_mag_crn_ins'],
                  'sol_com_crn_ret_des' => $item[0]['sol_com_crn_ret_des'],
                  'sol_com_crn_ret' => $item[0]['sol_com_crn_ret'],
                  'sol_com_crn_ins_des' => $item[0]['sol_com_crn_ins_des'],
                  'sol_com_crn_ins' => $item[0]['sol_com_crn_ins'], */
                'sol_ins_crn' => $item[0]['sol_ins_crn'],
                'sol_ret_crn' => $item[0]['sol_ret_crn'],
                'sol_ins_des' => $item[0]['sol_ins_des'],
				'sol_lista_cruzada' => $item[0]['sol_lista_cruzada'],
                'sol_ret_des' => $item[0]['sol_ret_des'],
                'sol_ins_mat' => $item[0]['sol_ins_mat'],
                'sol_ret_mat' => $item[0]['sol_ret_mat'],
                'sol_sug_ins_crn' => $item[0]['sol_sug_ins_crn'],
                'sol_sug_ret_crn' => $item[0]['sol_sug_ret_crn'],
                'sol_sug_ins_des' => $item[0]['sol_sug_ins_des'],
                'sol_sug_ret_des' => $item[0]['sol_sug_ret_des'],
                'sol_sug_ins_mat' => $item[0]['sol_sug_ins_mat'],
                'sol_sug_ret_mat' => $item[0]['sol_sug_ret_mat'],
                'sol_ins_seccion' => $item[0]['sol_ins_seccion'],
                'sol_ins_instructor' => $item[0]['sol_ins_instructor'],
                'sol_ins_tipo' => $item[0]['sol_ins_tipo'],
                'sol_ret_seccion' => $item[0]['sol_ret_seccion'],
                'sol_ret_instructor' => $item[0]['sol_ret_instructor'],
                'sol_ret_tipo' => $item[0]['sol_ret_tipo'],
                'sol_sug_ins_seccion' => $item[0]['sol_sug_ins_seccion'],
                'sol_sug_ins_instructor' => $item[0]['sol_sug_ins_instructor'],
                'sol_sug_ins_tipo' => $item[0]['sol_sug_ins_tipo'],
                'sol_sug_ret_seccion' => $item[0]['sol_sug_ret_seccion'],
                'sol_sug_ret_instructor' => $item[0]['sol_sug_ret_instructor'],
                'sol_sug_ret_tipo' => $item[0]['sol_sug_ret_tipo'],
                'sol_sug_crns_cc' => $item[0]['sol_sug_crns_cc'],
                'sol_sug_crns_cc_seccion' => $item[0]['sol_sug_crns_cc_seccion'],
                'sol_sug_crns_cc_instructor' => $item[0]['sol_sug_crns_cc_instructor'],
                'sol_sug_crns_cc_materia' => $item[0]['sol_sug_crns_cc_materia'],
                'sol_sug_crns_cc_materia' => $item[0]['sol_sug_crns_cc_materia'],
                'tipo' => @$tipo[0]['tip_descripcion'],
                'sol_attr_curso' => $item[0]['sol_attr_curso'],
                'motivo' => @$motivo[0]['mov_descripcion'],
                'estado' => @$estado[0]['est_descripcion'],
                'estadoPadre' => @$estPadre,
                'options_estado' => $options_estado
            );

            $this->load->model('Comentario_model', '', TRUE);
            $this->load->model('Rol_model', '', TRUE);

            $this->load->model('Parametro_model', '', TRUE);
            $comentario_normal = $this->Parametro_model->get_item('comentario normal', 'par_nombre');
            $comentario_cancelar = $this->Parametro_model->get_item('comentario cancelar', 'par_nombre');
            $comentario_cambiar_estado = $this->Parametro_model->get_item('comentario cambiar estado', 'par_nombre');
            //unset($this->Parametro_model);
            $datac = array('sol_id' => $sol_id,
                'com_nombre' => $this->session->userdata('nombres'),
                'rol_id' => $this->session->userdata('rol'),
                'accion' => '',
                'comentario_normal' => $comentario_normal[0]['par_valor'],
                'comentario_cancelar' => $comentario_cancelar[0]['par_valor'],
                'comentario_cambiar_estado' => $comentario_cambiar_estado[0]['par_valor'],
            );
            unset($this->Comentario_model);
            unset($this->Rol_model);
            $comentario_form = $this->load->view('comentario_form', $datac, true);
            $data['sol_id'] = $sol_id;
            $data['accion'] = 'estado';
            $data['titulo'] = 'Detalle';
            $data['comentario_form'] = $comentario_form;
            $data['menu'] = $menu;
            //otros datos estudiante
            //obtengo el periodo actual
            $this->load->model('Parametro_model', '', TRUE);
            $periodo = $this->Parametro_model->get_item('periodo', 'par_nombre');
            $periodo = $periodo[0]['par_valor'];
            // echo $item[0]['sol_periodo']."<br>";
            // echo $item[0]['sol_pidm'];
            $datos_estudiante = $this->integracion->datosEstudiante($item[0]['sol_pidm'], $item[0]['sol_periodo']);
            // $data['prog'] = $datos_estudiante['PROGRAMA'];
            // $data['doble_prog'] = $datos_estudiante['DOBLE_PROGRAMA'];
            // $data['creditos']= $datos_estudiante['CRED_INS'];
            // $data['opcion']= $datos_estudiante['OPCION'];
            // $data['ssc']= $datos_estudiante['SSC'];
            $data['prog'] = $item[0]["sol_prog"];
            $data['doble_prog'] = $item[0]["sol_doble_prog"];
            $data['creditos'] = $item[0]["sol_creditos"];
            $data['opcion'] = $item[0]["sol_opcion_estud"];
            $data['ssc'] = $item[0]["sol_ssc"];
            $data['sol_primer_sem'] = $item[0]["sol_primer_sem"];
            $data['sol_primer_semes_msg'] = $item[0]["sol_primer_semes_msg"];
            $data['sol_id'] = $sol_id;
            //obtiene una cadena 'anterior,actual,siguiente'-----------------------------------------------
            $filtros['sortorder'] = $this->session->userdata('sortorder');
            $filtros['sortname'] = $this->session->userdata('sortname');
            $filtros['qtype'] = $this->session->userdata('qtype');
            $filtros['query'] = $this->session->userdata('query');

            $filtros['qtype2'] = $this->session->userdata('qtype2');
            $filtros['query2'] = $this->session->userdata('query2');
            $filtros['query3'] = $this->session->userdata('query3');

            $ordenadas = $this->_get_filas($this->session->userdata('rol'), 0, 0, //$inicio,$this->session->userdata('cantpag'),
                    $filtros['sortorder'], $filtros['sortname'], $filtros['qtype'], $filtros['query'], $filtros['qtype2'], $filtros['query2'], $filtros['query3'], false, $sol_id); //imprimir
            // echo "AA";
            // exit;
            $ordenfilas = '';
            /* echo 'sortorder '.$filtros['sortorder'].' sortname '.$filtros['sortname'];
              echo $filtros['qtype'].'<br>';
              echo $filtros['query'].'<br>';
              echo $filtros['qtype2'].'<br>';
              echo $filtros['query2'].'<br>'; */

            // echo "<pre>";
            // print_r($ordenadas);
            // echo "</pre>";
            foreach ($ordenadas as $indice => $fila) {
                //echo $fila['sol_id'].'<-<br>';
                // echo $fila['sol_id']."<br>";
                if ($fila['sol_id'] == str_replace('-', '', $sol_id)) {

                    $anterior = $indice == 0 ? $ordenadas[count($ordenadas) - 1]['sol_id'] : $ordenadas[$indice - 1]['sol_id'];
                    $siguiente = $indice == count($ordenadas) - 1 ? $ordenadas[0]['sol_id'] : $ordenadas[$indice + 1]['sol_id'];

                    $ordenfilas = $anterior . ';' . $fila['sol_id'] . ';' . $siguiente; //cadena 'anterior,actual,siguiente'
                }
            }

            //-----------------------------------------------------------------------------------------------
            $data['ordenfilas'] = $ordenfilas;
            $data['ordenfilas_paginado'] = $this->session->userdata('ordenfilas');
            $data['rol_botones'] = $this->session->userdata('rol');
            $data["sol_periodo"] = $item[0]["sol_periodo"];
            ;

            $sol_ins_cupos = $this->integracion->cupos_inscritos($data["sol_ins_crn"], $item[0]['sol_periodo']);
            $data["sol_ins_cupos"] = (int) @$sol_ins_cupos[1];
            $data["sol_ins_cupos_ins"] = (int) @$sol_ins_cupos[2];

            if (!empty($item[0]['sol_alternativas'])) {

                $alternativas = json_decode($item[0]['sol_alternativas'], true);

                foreach ($alternativas as $key => $value) {

                    $cupos = $this->integracion->cupos_inscritos($alternativas[$key]['crn'], $item[0]['sol_periodo']);
                    $alternativas[$key]["sol_ret_cupos"] = (int) @$cupos[1];
                    $alternativas[$key]["sol_ret_cupos_ins"] = (int) @$cupos[2];
                }

                $data['alternativas'] = $alternativas;
            }

            if (!empty($item[0]['sol_sug_crns_cc'])) {
                $sol_ins_cupos = $this->integracion->cupos_inscritos($data["sol_sug_crns_cc"], $item[0]['sol_periodo']);
                $data["sol_sug_crns_cc_cupos"] = (int) @$sol_ins_cupos[1];
                $data["sol_sug_crns_cc_cupos_ins"] = (int) @$sol_ins_cupos[2];
            }



            $sol_ret_cupos = $this->integracion->cupos_inscritos($data["sol_ret_crn"], $item[0]['sol_periodo']);
            $data["sol_ret_cupos"] = (int) @$sol_ret_cupos[1];
            $data["sol_ret_cupos_ins"] = (int) @$sol_ret_cupos[2];

            $sol_sug_cupos = $this->integracion->cupos_inscritos($data["sol_sug_ins_crn"], $item[0]['sol_periodo']);
            $data["sol_sug_cupos"] = (int) @$sol_sug_cupos[1];
            $data["sol_sug_cupos_ins"] = (int) @$sol_sug_cupos[2];

            $sol_sug_ret_cupos = $this->integracion->cupos_inscritos($data["sol_sug_ret_crn"], $item[0]['sol_periodo']);
            $data["sol_sug_ret_cupos"] = (int) @$sol_sug_ret_cupos[1];
            $data["sol_sug_ret_cupos_ins"] = (int) @$sol_sug_ret_cupos[2];
            $data["tip_id"] = $item[0]["tip_id"];
            $this->load->view('solicitud_form_ver', $data);
        }
    }

    public function formacancelar($sol_id) {

        $mensaje = '';
        $mensaje_varios = '';
        $ids_habilitados = '';
        $ids_cancelados = '';
        $mensaje_cancelados = '';
        $contador = 0;
        $contador_c = 0;
        $menu = $this->Menu_model->_getmenu();
        $data = array();
        if ($sol_id) {
            $lista = trim($sol_id, '-');
            $lista = explode('-', $lista);
            foreach ($lista as $id_c) {
                $item = $this->Solicitud_model->get_item($id_c);
//$item[0]['dep_id'] = 'PRUEBA'; ////////////////////////////////////////////prueba eliminar
                if (!$this->_validar_gestion($item[0]['dep_id'])) {
                    $limites_dep = $this->Limite_model->get_item($item[0]['dep_id'], 'dep_id');
                    $aviso = empty($limites_dep) ? 'No se encuentra registrado el programa con ID: ' . $item[0]['dep_id'] : 'El Periodo de gesti&oacute;n de solicitudes ha finalizado.';
                    $mensaje .= 'El Periodo de gesti&oacute;n de la solicitud de id:' . $id_c . ' ha finalizado.<br>';
                    $contador++;

                    //se quita validaciï¿½n para que siempre se pueda cancelar
                } elseif ((int) $item[0]['est_id'] == 0 /* (int)$item[0]['est_id']==6 ||(int)$item[0]['est_id']==2 || (int)$item[0]['est_id']==3 */) {
                    $ids_cancelados .= $id_c . ',';
                } else {
                    $mensaje_varios .= $id_c . ',';
                    $ids_habilitados .= $id_c . ',';
                }
            }

            if ($contador == count($lista)) {
                $this->load->view('solicitud_aviso', array('aviso' => /* 'Recuerde que con esta aplicaci&oacute;n solo se reciben solicitudes para las facultades de <strong>Derecho</strong> y <strong>Econom&iacute;a</strong>.' */ $aviso/* 'El Periodo de gesti&oacute;n de solicitudes ha finalizado.' */, 'menu' => $menu, 'titulo' => 'AVISO', 'no_header' => 'no', 'rol' => $this->session->userdata('rol')));
            } elseif ($contador_c == count($lista)) {
                $this->load->view('solicitud_aviso', array('aviso' => 'Las solicitudes ya han sido canceladas o finalizadas.', 'menu' => $menu, 'titulo' => 'AVISO', 'no_header' => 'no', 'rol' => $this->session->userdata('rol')));
            } else {
                if (count($lista) === 1) {
                    $item = $this->Solicitud_model->get_item($lista[0]);
                    $this->load->model('Tipo_model', '', TRUE);
                    $tipo = $this->Tipo_model->get_item($item[0]['tip_id']);
                    unset($this->Tipo_model);
                    $this->load->model('Motivo_model', '', TRUE);
                    $motivo = $this->Motivo_model->get_item($item[0]['mov_id']);
                    unset($this->Motivo_model);
                    $this->load->model('Estado_model', '', TRUE);
                    $estado = $this->Estado_model->get_item($item[0]['est_id']);
                    $options_estado = $this->Estado_model->get_dropdown();
                    unset($this->Estado_model);

                    $sol_ins_tipo = ($item[0]['sol_ins_tipo'] == 'mag' || $item[0]['sol_ins_tipo'] == 'com') ? $item[0]['sol_ins_tipo'] : 'NORMAL';
                    if ($sol_ins_tipo != 'NORMAL')
                        $sol_ins_tipo = ($item[0]['sol_ins_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
                    else
                        $sol_ins_tipo = $item[0]['sol_ins_crn'] != '' ? $sol_ins_tipo : '';
                    $sol_ret_tipo = ($item[0]['sol_ret_tipo'] == 'mag' || $item[0]['sol_ret_tipo'] == 'com') ? $item[0]['sol_ret_tipo'] : 'NORMAL';
                    if ($sol_ret_tipo != 'NORMAL')
                        $sol_ret_tipo = ($item[0]['sol_ret_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
                    else
                        $sol_ret_tipo = $item[0]['sol_ret_crn'] != '' ? $sol_ret_tipo : '';
                    $sol_sug_ins_tipo = ($item[0]['sol_sug_ins_tipo'] == 'mag' || $item[0]['sol_sug_ins_tipo'] == 'com') ? $item[0]['sol_sug_ins_tipo'] : 'NORMAL';
                    if ($sol_sug_ins_tipo != 'NORMAL')
                        $sol_sug_ins_tipo = ($item[0]['sol_sug_ins_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
                    else
                        $sol_sug_ins_tipo = $item[0]['sol_sug_ins_crn'] != '' ? $sol_sug_ins_tipo : '';
                    $sol_sug_ret_tipo = ($item[0]['sol_sug_ret_tipo'] == 'mag' || $item[0]['sol_sug_ret_tipo'] == 'com') ? $item[0]['sol_sug_ret_tipo'] : 'NORMAL';
                    if ($sol_sug_ret_tipo != 'NORMAL')
                        $sol_sug_ret_tipo = ($item[0]['sol_sug_ret_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
                    else
                        $sol_sug_ret_tipo = $item[0]['sol_sug_ret_crn'] != '' ? $sol_sug_ret_tipo : '';
                    $item[0]['sol_ins_tipo'] = $sol_ins_tipo;
                    $item[0]['sol_ret_tipo'] = $sol_ret_tipo;
                    $item[0]['sol_sug_ins_tipo'] = $sol_sug_ins_tipo;
                    $item[0]['sol_sug_ret_tipo'] = $sol_sug_ret_tipo;
                    $data = array('sol_id' => $item[0]['sol_id'],
                        'sol_descripcion' => $item[0]['sol_descripcion'],
                        'tip_id' => $item[0]['tip_id'],
                        'mov_id' => $item[0]['mov_id'],
                        'est_id' => $item[0]['est_id'],
                        'dep_id_sec' => $item[0]['dep_id_sec'],
                        'sol_email' => $item[0]['sol_email'],
                        'sol_nombre' => $item[0]['sol_nombre'],
                        'sol_apellido' => $item[0]['sol_apellido'],
                        'sol_fec_creacion' => $item[0]['sol_fec_creacion'],
                        'sol_pidm' => $item[0]['sol_pidm'],
                        'sol_uidnumber' => $item[0]['sol_uidnumber'],
                        /* 'sol_mag_crn_ret_des' => $item[0]['sol_mag_crn_ret_des'],
                          'sol_mag_crn_ret' => $item[0]['sol_mag_crn_ret'],
                          'sol_mag_crn_ins_des' => $item[0]['sol_mag_crn_ins_des'],
                          'sol_mag_crn_ins' => $item[0]['sol_mag_crn_ins'],
                          'sol_com_crn_ret_des' => $item[0]['sol_com_crn_ret_des'],
                          'sol_com_crn_ret' => $item[0]['sol_com_crn_ret'],
                          'sol_com_crn_ins_des' => $item[0]['sol_com_crn_ins_des'],
                          'sol_com_crn_ins' => $item[0]['sol_com_crn_ins'], */
                        'sol_ins_crn' => $item[0]['sol_ins_crn'],
                        'sol_ret_crn' => $item[0]['sol_ret_crn'],
                        'sol_ins_des' => $item[0]['sol_ins_des'],
						'sol_lista_cruzada' => $item[0]['sol_lista_cruzada'],
                        'sol_ret_des' => $item[0]['sol_ret_des'],
                        'sol_ins_mat' => $item[0]['sol_ins_mat'],
                        'sol_ret_mat' => $item[0]['sol_ret_mat'],
                        'sol_sug_ins_crn' => $item[0]['sol_sug_ins_crn'],
                        'sol_sug_ret_crn' => $item[0]['sol_sug_ret_crn'],
                        'sol_sug_ins_des' => $item[0]['sol_sug_ins_des'],
                        'sol_sug_ret_des' => $item[0]['sol_sug_ret_des'],
                        'sol_sug_ins_mat' => $item[0]['sol_sug_ins_mat'],
                        'sol_sug_ret_mat' => $item[0]['sol_sug_ret_mat'],
                        'sol_ins_seccion' => $item[0]['sol_ins_seccion'],
                        'sol_ins_instructor' => $item[0]['sol_ins_instructor'],
                        'sol_ins_tipo' => $item[0]['sol_ins_tipo'],
                        'sol_ret_seccion' => $item[0]['sol_ret_seccion'],
                        'sol_ret_instructor' => $item[0]['sol_ret_instructor'],
                        'sol_ret_tipo' => $item[0]['sol_ret_tipo'],
                        'sol_sug_ins_seccion' => $item[0]['sol_sug_ins_seccion'],
                        'sol_sug_ins_instructor' => $item[0]['sol_sug_ins_instructor'],
                        'sol_sug_ins_tipo' => $item[0]['sol_sug_ins_tipo'],
                        'sol_sug_ret_seccion' => $item[0]['sol_sug_ret_seccion'],
                        'sol_sug_ret_instructor' => $item[0]['sol_sug_ret_instructor'],
                        'sol_sug_ret_tipo' => $item[0]['sol_sug_ret_tipo'],
                        'tipo' => @$tipo[0]['tip_descripcion'],
                        'tipodesc' => @$tipo[0]['tip_descripcion'],
                        'motivo' => @$motivo[0]['mov_descripcion'],
                        'estado' => @$estado[0]['est_descripcion'],
                        'options_estado' => $options_estado,
                        'tipo' => 'uno',
                    );
                }else {
                    /* mensaje cuando son varios ids */
                    $mensaje_varios = trim($mensaje_varios, ',');
                    $mensaje_varios = 'Se van a modificar las solicitudes con los siguientes ID: ' . $mensaje_varios . '.';
                    $data['mensaje_gestion'] = $mensaje;
                    $data['mensaje_varios'] = $mensaje_varios;
                    $data['mensaje_cancelados'] = $ids_cancelados == '' ? '' : 'Ya presentan estado cancelado o finalizado las solicitudes con los siguientes ID:' . trim($ids_cancelados, ',') . '.';
                    $data['tipo'] = 'varios';
                }
                $this->load->model('Comentario_model', '', TRUE);
                $this->load->model('Rol_model', '', TRUE);

                $this->load->model('Parametro_model', '', TRUE);
                $comentario_normal = $this->Parametro_model->get_item('comentario normal', 'par_nombre');
                $comentario_cancelar = $this->Parametro_model->get_item('comentario cancelar', 'par_nombre');
                $comentario_cambiar_estado = $this->Parametro_model->get_item('comentario cambiar estado', 'par_nombre');
                unset($this->Parametro_model);
                $datac = array('sol_id' => $sol_id,
                    'com_nombre' => $this->session->userdata('nombres'),
                    'rol_id' => $this->session->userdata('rol'),
                    'accion' => '',
                    'comentario_normal' => $comentario_normal[0]['par_valor'],
                    'comentario_cancelar' => $comentario_cancelar[0]['par_valor'],
                    'comentario_cambiar_estado' => $comentario_cambiar_estado[0]['par_valor'],
                );
                //$this->_prepare_list_comentario($datac, $id, $this->Comentario_model, false);
                unset($this->Comentario_model);
                unset($this->Rol_model);
                $comentario_form = $this->load->view('comentario_form', $datac, true);
                $data['sol_id'] = $sol_id;
                $data['accion'] = 'estado';
                $data['titulo'] = 'Cancelar';
                $data['comentario_form'] = $comentario_form;
                $data['menu'] = $menu;
                $data['ids_habilitados'] = $ids_habilitados;
                //$data['sol_id'] = $sol_id;
                //obtiene una cadena 'anterior,actual,siguiente'-----------------------------------------------
                $filtros['sortorder'] = $this->session->userdata('sortorder');
                $filtros['sortname'] = $this->session->userdata('sortname');
                $filtros['qtype'] = $this->session->userdata('qtype');
                $filtros['query'] = $this->session->userdata('query');
                $filtros['qtype2'] = $this->session->userdata('qtype2');
                $filtros['query2'] = $this->session->userdata('query2');
                $filtros['query3'] = $this->session->userdata('query3');

                $ordenadas = $this->_get_filas($this->session->userdata('rol'), 0, 0, //$inicio,$this->session->userdata('cantpag'),
                        $filtros['sortorder'], $filtros['sortname'], $filtros['qtype'], $filtros['query'], $filtros['qtype2'], $filtros['query2'], $filtros['query3'], false, true); //imprimir
                // echo "<pre>";
                // print_r($sol_id);
                // echo "</pre>";
                $ordenfilas = '';
                /* echo 'sortorder '.$filtros['sortorder'].' sortname '.$filtros['sortname'];
                  echo $filtros['qtype'].'<br>';
                  echo $filtros['query'].'<br>';
                  echo $filtros['qtype2'].'<br>';
                  echo $filtros['query2'].'<br>'; */

                //print_r($ordenadas);
                foreach ($ordenadas as $indice => $fila) {
                    //echo $fila['sol_id'].'<-<br>';
                    if ($fila['sol_id'] == str_replace('-', '', $sol_id)) {

                        $anterior = $indice == 0 ? $ordenadas[count($ordenadas) - 1]['sol_id'] : $ordenadas[$indice - 1]['sol_id'];
                        $siguiente = $indice == count($ordenadas) - 1 ? $ordenadas[0]['sol_id'] : $ordenadas[$indice + 1]['sol_id'];

                        $ordenfilas = $anterior . ';' . $fila['sol_id'] . ';' . $siguiente; //cadena 'anterior,actual,siguiente'
                    }
                }
                //-----------------------------------------------------------------------------------------------
                $data['ordenfilas'] = $ordenfilas;
                $data['ordenfilas_paginado'] = $this->session->userdata('ordenfilas');
                $data['rol_botones'] = $this->session->userdata('rol');
                $this->load->view('solicitud_form_cancelar', $data);
            }
        }
    }

    public function formaestado($sol_id) {
        $mensaje = '';
        $mensaje_cancelados = '';
        $mensaje_varios = '';
        $ids_cancelados = '';
        $ids_habilitados = '';
        $contador = 0;
        $contador_c = 0;
        $menu = $this->Menu_model->_getmenu();
        $data = array();
        $this->load->model('Estado_model', '', TRUE);
        $options_estado = $this->Estado_model->get_dropdown(TRUE);

        if ($sol_id) {
            $lista = trim($sol_id, '-');
            $lista = explode('-', $lista);
            foreach ($lista as $id_c) {
                $item = $this->Solicitud_model->get_item($id_c);
//$item[0]['dep_id'] = 'PRUEBA'; ////////////////////////////////////////////prueba eliminar
                if (!$this->_validar_gestion($item[0]['dep_id'])) {
                    $limites_dep = $this->Limite_model->get_item($item[0]['dep_id'], 'dep_id');
                    $aviso = empty($limites_dep) ? 'No se encuentra registrado el programa con ID: ' . $item[0]['dep_id'] : 'El Periodo de gesti&oacute;n de solicitudes ha finalizado.';
                    $mensaje .= 'El Periodo de gesti&oacute;n de la solicitud de id:' . $id_c . ' ha finalizado.<br>';
                    $contador++;

                    //se quita validaciï¿½n para que siempre se pueda cambiar estado
                } elseif ((int) $item[0]['est_id'] == 0 /* (int)$item[0]['est_id']==6||(int)$item[0]['est_id']==2 || (int)$item[0]['est_id']==3 */) {
                    $ids_cancelados .= $id_c . ',';
                } else {
                    $mensaje_varios .= $id_c . ',';
                    $ids_habilitados .= $id_c . ',';
                }
            }
            if ($contador == count($lista)) {
                $this->load->view('solicitud_aviso', array('aviso' => /* 'Recuerde que con esta aplicaci&oacute;n solo se reciben solicitudes para las facultades de <strong>Derecho</strong> y <strong>Econom&iacute;a</strong>.' */ $aviso/* 'El Periodo de gesti&oacute;n de solicitudes ha finalizado.' */, 'menu' => $menu, 'titulo' => 'AVISO', 'no_header' => 'no', 'rol' => $this->session->userdata('rol')));
            } elseif ($contador_c == count($lista)) {
                $this->load->view('solicitud_aviso', array('aviso' => 'Las solicitudes ya han sido canceladas o finalizadas.', 'menu' => $menu, 'titulo' => 'AVISO', 'no_header' => 'no', 'rol' => $this->session->userdata('rol')));
            } else {
                if (count($lista) === 1) {
                    $estado = $this->Estado_model->get_item($item[0]['est_id']);
                    $estadoPadre = $this->Estado_model->get_item($estado[0]['est_padre']);
                    $estPadre = '';
                    if (count($estadoPadre) > 0) {
                        $estPadre = $estadoPadre[0]['est_descripcion'];
                    }
                    $item = $this->Solicitud_model->get_item($lista[0]);
                    $this->load->model('Tipo_model', '', TRUE);
                    $tipo = $this->Tipo_model->get_item($item[0]['tip_id']);
                    unset($this->Tipo_model);
                    $this->load->model('Motivo_model', '', TRUE);
                    $motivo = $this->Motivo_model->get_item($item[0]['mov_id']);
                    unset($this->Motivo_model);

                    $sol_ins_tipo = ($item[0]['sol_ins_tipo'] == 'mag' || $item[0]['sol_ins_tipo'] == 'com') ? $item[0]['sol_ins_tipo'] : 'NORMAL';
                    if ($sol_ins_tipo != 'NORMAL')
                        $sol_ins_tipo = ($item[0]['sol_ins_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
                    else
                        $sol_ins_tipo = $item[0]['sol_ins_crn'] != '' ? $sol_ins_tipo : '';
                    $sol_ret_tipo = ($item[0]['sol_ret_tipo'] == 'mag' || $item[0]['sol_ret_tipo'] == 'com') ? $item[0]['sol_ret_tipo'] : 'NORMAL';
                    if ($sol_ret_tipo != 'NORMAL')
                        $sol_ret_tipo = ($item[0]['sol_ret_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
                    else
                        $sol_ret_tipo = $item[0]['sol_ret_crn'] != '' ? $sol_ret_tipo : '';
                    $sol_sug_ins_tipo = ($item[0]['sol_sug_ins_tipo'] == 'mag' || $item[0]['sol_sug_ins_tipo'] == 'com') ? $item[0]['sol_sug_ins_tipo'] : 'NORMAL';
                    if ($sol_sug_ins_tipo != 'NORMAL')
                        $sol_sug_ins_tipo = ($item[0]['sol_sug_ins_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
                    else
                        $sol_sug_ins_tipo = $item[0]['sol_sug_ins_crn'] != '' ? $sol_sug_ins_tipo : '';
                    $sol_sug_ret_tipo = ($item[0]['sol_sug_ret_tipo'] == 'mag' || $item[0]['sol_sug_ret_tipo'] == 'com') ? $item[0]['sol_sug_ret_tipo'] : 'NORMAL';
                    if ($sol_sug_ret_tipo != 'NORMAL')
                        $sol_sug_ret_tipo = ($item[0]['sol_sug_ret_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
                    else
                        $sol_sug_ret_tipo = $item[0]['sol_sug_ret_crn'] != '' ? $sol_sug_ret_tipo : '';
                    $item[0]['sol_ins_tipo'] = $sol_ins_tipo;
                    $item[0]['sol_ret_tipo'] = $sol_ret_tipo;
                    $item[0]['sol_sug_ins_tipo'] = $sol_sug_ins_tipo;
                    $item[0]['sol_sug_ret_tipo'] = $sol_sug_ret_tipo;

                    // $item[0]['sol_fec_est_actualiza'] = ($item[0]["est_id"]==$this->input->post("est_id") ? $item[0]['sol_fec_est_actualiza'] : date("Y-m-d H:i:s"));

                    $data = array('sol_id' => $item[0]['sol_id'],
                        'sol_descripcion' => $item[0]['sol_descripcion'],
                        'tip_id' => $item[0]['tip_id'],
                        'mov_id' => $item[0]['mov_id'],
                        'est_id' => $item[0]['est_id'],
                        'dep_id_sec' => $item[0]['dep_id_sec'],
                        'sol_email' => $item[0]['sol_email'],
                        'sol_nombre' => $item[0]['sol_nombre'],
                        'sol_apellido' => $item[0]['sol_apellido'],
                        'sol_fec_creacion' => $item[0]['sol_fec_creacion'],
                        'sol_pidm' => $item[0]['sol_pidm'],
                        'sol_uidnumber' => $item[0]['sol_uidnumber'],
                        /* 'sol_mag_crn_ret_des' => $item[0]['sol_mag_crn_ret_des'],
                          'sol_mag_crn_ret' => $item[0]['sol_mag_crn_ret'],
                          'sol_mag_crn_ins_des' => $item[0]['sol_mag_crn_ins_des'],
                          'sol_mag_crn_ins' => $item[0]['sol_mag_crn_ins'],
                          'sol_com_crn_ret_des' => $item[0]['sol_com_crn_ret_des'],
                          'sol_com_crn_ret' => $item[0]['sol_com_crn_ret'],
                          'sol_com_crn_ins_des' => $item[0]['sol_com_crn_ins_des'],
                          'sol_com_crn_ins' => $item[0]['sol_com_crn_ins'], */
                        'sol_ins_crn' => $item[0]['sol_ins_crn'],
                        'sol_ret_crn' => $item[0]['sol_ret_crn'],
                        'sol_ins_des' => $item[0]['sol_ins_des'],
						'sol_lista_cruzada' => $item[0]['sol_lista_cruzada'],
                        'sol_ret_des' => $item[0]['sol_ret_des'],
                        'sol_ins_mat' => $item[0]['sol_ins_mat'],
                        'sol_ret_mat' => $item[0]['sol_ret_mat'],
                        'sol_sug_ins_crn' => $item[0]['sol_sug_ins_crn'],
                        'sol_sug_ret_crn' => $item[0]['sol_sug_ret_crn'],
                        'sol_sug_ins_des' => $item[0]['sol_sug_ins_des'],
                        'sol_sug_ret_des' => $item[0]['sol_sug_ret_des'],
                        'sol_sug_ins_mat' => $item[0]['sol_sug_ins_mat'],
                        'sol_sug_ret_mat' => $item[0]['sol_sug_ret_mat'],
                        'sol_ins_seccion' => $item[0]['sol_ins_seccion'],
                        'sol_ins_instructor' => $item[0]['sol_ins_instructor'],
                        'sol_ins_tipo' => $item[0]['sol_ins_tipo'],
                        'sol_ret_seccion' => $item[0]['sol_ret_seccion'],
                        'sol_ret_instructor' => $item[0]['sol_ret_instructor'],
                        'sol_ret_tipo' => $item[0]['sol_ret_tipo'],
                        'sol_sug_ins_seccion' => $item[0]['sol_sug_ins_seccion'],
                        'sol_sug_ins_instructor' => $item[0]['sol_sug_ins_instructor'],
                        'sol_sug_ins_tipo' => $item[0]['sol_sug_ins_tipo'],
                        'sol_sug_ret_seccion' => $item[0]['sol_sug_ret_seccion'],
                        'sol_sug_ret_instructor' => $item[0]['sol_sug_ret_instructor'],
                        'sol_sug_ret_tipo' => $item[0]['sol_sug_ret_tipo'],
                        'tipo' => @$tipo[0]['tip_descripcion'],
                        'motivo' => @$motivo[0]['mov_descripcion'],
                        'estado' => @$estado[0]['est_descripcion'],
                        'estadoPadre' => @$estPadre,
                        'tipo_entrada' => 'uno',
                        'sol_primer_sem' => $item[0]['sol_primer_sem'],
                        'sol_primer_semes_msg' => $item[0]['sol_primer_semes_msg']
                            // 'sol_fec_est_actualiza'=>$item[0]['sol_fec_est_actualiza'],
                    );
                }else {
                    /* mensaje cuando son varios ids */
                    $mensaje_varios = trim($mensaje_varios, ',');
                    $mensaje_varios = 'Se van a modificar las solicitudes con los siguientes ID: ' . $mensaje_varios . '.';
                    $data['mensaje_gestion'] = $mensaje;
                    $data['mensaje_varios'] = $mensaje_varios;
                    $data['mensaje_cancelados'] = $ids_cancelados == '' ? '' : 'Presentan estado cancelado o finalizado  las solicitudes con los siguientes ID:' . trim($ids_cancelados, ',') . '.';
                    $data['tipo_entrada'] = 'varios';
                }

                // echo "<pre>";
                // print_r($data);
                // echo "</pre>";
                // exit;
                $this->load->model('Comentario_model', '', TRUE);
                $this->load->model('Rol_model', '', TRUE);
                $this->load->model('Parametro_model', '', TRUE);
                $comentario_normal = $this->Parametro_model->get_item('comentario normal', 'par_nombre');
                $comentario_cancelar = $this->Parametro_model->get_item('comentario cancelar', 'par_nombre');
                $comentario_cambiar_estado = $this->Parametro_model->get_item('comentario cambiar estado', 'par_nombre');
                unset($this->Parametro_model);
                $datac = array('sol_id' => $sol_id,
                    'com_nombre' => $this->session->userdata('nombres'),
                    'rol_id' => $this->session->userdata('rol'),
                    'accion' => '',
                    'comentario_normal' => $comentario_normal[0]['par_valor'],
                    'comentario_cancelar' => $comentario_cancelar[0]['par_valor'],
                    'comentario_cambiar_estado' => $comentario_cambiar_estado[0]['par_valor'],
                );
                //$this->_prepare_list_comentario($datac, $id, $this->Comentario_model, false);
                $listadoComenPeronsal = $this->Comentario_model->comenPersonales($this->session->userdata('login'));
                unset($this->Comentario_model);
                unset($this->Rol_model);

                $comentario_form = $this->load->view('comentario_form', $datac, true);
                $data['sol_id'] = $sol_id;
                $data['accion'] = 'estado';
                $data['titulo'] = 'Cambiar estado';
                $data['comentario_form'] = $comentario_form;
                $data['menu'] = $menu;
                $data['ids_habilitados'] = $ids_habilitados;
                $data['options_estado'] = $options_estado;
                //$data['sol_id'] = $sol_id;
                //obtiene una cadena 'anterior,actual,siguiente'-----------------------------------------------
                $filtros['sortorder'] = $this->session->userdata('sortorder');
                $filtros['sortname'] = $this->session->userdata('sortname');
                $filtros['qtype'] = $this->session->userdata('qtype');
                $filtros['query'] = $this->session->userdata('query');
                $filtros['qtype2'] = $this->session->userdata('qtype2');
                $filtros['query2'] = $this->session->userdata('query2');
                $filtros['query3'] = $this->session->userdata('query3');

                $ordenadas = $this->_get_filas($this->session->userdata('rol'),
                        //0, 0, //$inicio,$this->session->userdata('cantpag'), //original 23-05-2013
                        //$this->session->userdata('numpag'),$this->session->userdata('cantpag'),
                        0, $this->session->userdata('cantpag'), $filtros['sortorder'], $filtros['sortname'], $filtros['qtype'], $filtros['query'], $filtros['qtype2'], $filtros['query2'], $filtros['query3'], false, $sol_id); //imprimir

                $ordenfilas = '';
                /* echo 'sortorder '.$filtros['sortorder'].' sortname '.$filtros['sortname'];
                  echo $filtros['qtype'].'<br>';
                  echo $filtros['query'].'<br>';
                  echo $filtros['qtype2'].'<br>';
                  echo $filtros['query2'].'<br>'; */

                //print_r($ordenadas);
                foreach ($ordenadas as $indice => $fila) {
                    //echo $fila['sol_id'].'<-<br>';
                    if ($fila['sol_id'] == str_replace('-', '', $sol_id)) {
                        //echo "encuentra"; exit;
                        $anterior = $indice == 0 ? $ordenadas[count($ordenadas) - 1]['sol_id'] : $ordenadas[$indice - 1]['sol_id'];
                        $siguiente = $indice == count($ordenadas) - 1 ? $ordenadas[0]['sol_id'] : $ordenadas[$indice + 1]['sol_id'];

                        $ordenfilas = $anterior . ';' . $fila['sol_id'] . ';' . $siguiente; //cadena 'anterior,actual,siguiente'
                    }
                }
                //-----------------------------------------------------------------------------------------------
                $data['ordenfilas'] = $ordenfilas;
                $data['ordenfilas_paginado'] = $this->session->userdata('ordenfilas');
                $data['rol_botones'] = $this->session->userdata('rol');
                $data['listadoComenPeronsal'] = $listadoComenPeronsal;
                $this->load->view('solicitud_form_estado', $data);
            }
        }
    }

    public function guardarcomenperonsal() {
        $comentariotitulo = strip_tags($this->input->post('comentariotitulo'));
        $comentariocont = strip_tags($this->input->post('comentariocont'));
        $idcomenpersonal = (int) $this->input->post('idcomenpersonal');
        $login = $this->session->userdata('login');
        $id_ch_comenpersonales = $this->Solicitud_model->agregarComentarioPersonal($idcomenpersonal, $comentariotitulo, $comentariocont, $login);
        echo (int) $id_ch_comenpersonales;
    }

    public function borrarcomenperonsal() {
        $idcomenpersonal = $this->input->post('idcomenpersonal');
        $estado = $this->Solicitud_model->borrarComentarioPersonal($idcomenpersonal);
        echo (int) $estado;
    }

    public function enviarCorreo($tipo_correo, $to2, $subject2, $id, $cc2 = '', $bcc2 = '') {
        //echo "to2 $to2, subject2 $subject2, id $id, cc2 $cc2, bcc2 $bcc2";
        $this->load->model('Parametro_model', 'Parametro_model_correo', TRUE);
        $correo_from = $this->Parametro_model_correo->get_item('correo_from', 'par_nombre');
        $nombre_from = $this->Parametro_model_correo->get_item('nombre_from', 'par_nombre');
        //unset($this->Parametro_model_correo);

        $this->email->from($correo_from[0]['par_valor'], $nombre_from[0]['par_valor']);

        //no envia en ambiente de pruebas
        $to2 = AMBIENTE_PRUEBAS == '1' ? CORREO_PRUEBAS : $to2;

        $this->email->to($to2);
        $this->email->bcc($correo_from[0]['par_valor']);
        if ($cc2 != '')
            $this->email->cc($cc2);
        if ($bcc2 != '')
            $this->email->bcc($bcc2);
        $this->email->subject(utf8_decode($subject2));

        $item = $this->Solicitud_model->get_item($id);

        $this->load->model('Tipo_model', 'Tipo_model_correo', TRUE);
        $tipo = $this->Tipo_model_correo->get_item($item[0]['tip_id']);
        //unset($this->Tipo_model_correo);
        $this->load->model('Motivo_model', 'Motivo_model_correo', TRUE);
        $motivo = $this->Motivo_model_correo->get_item($item[0]['mov_id']);
        //unset($this->Motivo_model_correo);
        $this->load->model('Estado_model', 'Estado_model_correo', TRUE);
        $estado = $this->Estado_model_correo->get_item($item[0]['est_id']);
        $estadoPadre = $this->Estado_model_correo->get_item($estado[0]['est_padre']);
        $estPadre = '';
        if (count($estadoPadre) > 0) {
            $estPadre = $estadoPadre[0]['est_descripcion'];
        }
        //unset($this->Estado_model_correo);
        $this->load->model('Rol_model', 'Rol_model_correo', TRUE);
        $rol = $this->Rol_model_correo->get_item($this->session->userdata('rol'));
        //unset($this->Rol_model_correo);
        $this->load->model('Comentario_model', 'Comentario_model_correo', TRUE); //esta linea adiciona tabulado al OK
        $comentario = $this->Comentario_model_correo->get_item($this->Comentario_model_correo->get_last($id, 'sol_id'));
        //unset($this->Comentario_model_correo);

        $sol_ins_tipo = ($item[0]['sol_ins_tipo'] == 'mag' || $item[0]['sol_ins_tipo'] == 'com') ? $item[0]['sol_ins_tipo'] : 'NORMAL';
        if ($sol_ins_tipo != 'NORMAL')
            $sol_ins_tipo = ($item[0]['sol_ins_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
        else
            $sol_ins_tipo = $item[0]['sol_ins_crn'] != '' ? $sol_ins_tipo : '';
        $sol_ret_tipo = ($item[0]['sol_ret_tipo'] == 'mag' || $item[0]['sol_ret_tipo'] == 'com') ? $item[0]['sol_ret_tipo'] : 'NORMAL';
        if ($sol_ret_tipo != 'NORMAL')
            $sol_ret_tipo = ($item[0]['sol_ret_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
        else
            $sol_ret_tipo = $item[0]['sol_ret_crn'] != '' ? $sol_ret_tipo : '';
        $sol_sug_ins_tipo = ($item[0]['sol_sug_ins_tipo'] == 'mag' || $item[0]['sol_sug_ins_tipo'] == 'com') ? $item[0]['sol_sug_ins_tipo'] : 'NORMAL';
        if ($sol_sug_ins_tipo != 'NORMAL')
            $sol_sug_ins_tipo = ($item[0]['sol_sug_ins_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
        else
            $sol_sug_ins_tipo = $item[0]['sol_sug_ins_crn'] != '' ? $sol_sug_ins_tipo : '';
        $sol_sug_ret_tipo = ($item[0]['sol_sug_ret_tipo'] == 'mag' || $item[0]['sol_sug_ret_tipo'] == 'com') ? $item[0]['sol_sug_ret_tipo'] : 'NORMAL';
        if ($sol_sug_ret_tipo != 'NORMAL')
            $sol_sug_ret_tipo = ($item[0]['sol_sug_ret_tipo'] == 'mag') ? 'MAGISTRAL' : 'COMPLEMENTARIA';
        else
            $sol_sug_ret_tipo = $item[0]['sol_sug_ret_crn'] != '' ? $sol_sug_ret_tipo : '';

        $tipo_crn_ins = $this->nombre_tipo(@$sol_ins_tipo);
        $tipo_crn_ret = $this->nombre_tipo(@$sol_ins_tipo, 'sug');

        $item[0]['sol_ins_tipo'] = $sol_ins_tipo;
        $item[0]['sol_ret_tipo'] = $sol_ret_tipo;
        $item[0]['sol_sug_ins_tipo'] = $sol_sug_ins_tipo;
        $item[0]['sol_sug_ret_tipo'] = $sol_sug_ret_tipo;
        $data = array('sol_id' => $item[0]['sol_id'],
            'sol_descripcion' => $item[0]['sol_descripcion'],
            'tip_id' => $item[0]['tip_id'],
            'mov_id' => $item[0]['mov_id'],
            'est_id' => $item[0]['est_id'],
            'dep_id_sec' => $item[0]['dep_id_sec'],
            'sol_email' => $item[0]['sol_email'],
            'sol_nombre' => $item[0]['sol_nombre'],
            'sol_apellido' => $item[0]['sol_apellido'],
            'sol_fec_creacion' => $item[0]['sol_fec_creacion'],
            'sol_pidm' => $item[0]['sol_pidm'],
            'sol_uidnumber' => $item[0]['sol_uidnumber'],
            /* 'sol_mag_crn_ret_des' => $item[0]['sol_mag_crn_ret_des'],
              'sol_mag_crn_ret' => $item[0]['sol_mag_crn_ret'],
              'sol_mag_crn_ins_des' => $item[0]['sol_mag_crn_ins_des'],
              'sol_mag_crn_ins' => $item[0]['sol_mag_crn_ins'],
              'sol_com_crn_ret_des' => $item[0]['sol_com_crn_ret_des'],
              'sol_com_crn_ret' => $item[0]['sol_com_crn_ret'],
              'sol_com_crn_ins_des' => $item[0]['sol_com_crn_ins_des'],
              'sol_com_crn_ins' => $item[0]['sol_com_crn_ins'], */
            'sol_ins_crn' => $item[0]['sol_ins_crn'],
            'sol_ret_crn' => $item[0]['sol_ret_crn'],
            'sol_ins_des' => $item[0]['sol_ins_des'],
			'sol_lista_cruzada' => $item[0]['sol_lista_cruzada'],
            'sol_ret_des' => $item[0]['sol_ret_des'],
            'sol_ins_mat' => $item[0]['sol_ins_mat'],
            'sol_ret_mat' => $item[0]['sol_ret_mat'],
            'sol_sug_ins_crn' => $item[0]['sol_sug_ins_crn'],
            'sol_sug_ret_crn' => $item[0]['sol_sug_ret_crn'],
            'sol_sug_ins_des' => $item[0]['sol_sug_ins_des'],
            'sol_sug_ret_des' => $item[0]['sol_sug_ret_des'],
            'sol_sug_ins_mat' => $item[0]['sol_sug_ins_mat'],
            'sol_sug_ret_mat' => $item[0]['sol_sug_ret_mat'],
            'sol_ins_seccion' => $item[0]['sol_ins_seccion'],
            'sol_ins_instructor' => $item[0]['sol_ins_instructor'],
            'sol_ins_tipo' => $item[0]['sol_ins_tipo'],
            'sol_ret_seccion' => $item[0]['sol_ret_seccion'],
            'sol_ret_instructor' => $item[0]['sol_ret_instructor'],
            'sol_ret_tipo' => $item[0]['sol_ret_tipo'],
            'sol_sug_ins_seccion' => $item[0]['sol_sug_ins_seccion'],
            'sol_sug_ins_instructor' => $item[0]['sol_sug_ins_instructor'],
            'sol_sug_ins_tipo' => $item[0]['sol_sug_ins_tipo'],
            'sol_sug_ret_seccion' => $item[0]['sol_sug_ret_seccion'],
            'sol_sug_ret_instructor' => $item[0]['sol_sug_ret_instructor'],
            'sol_sug_ret_tipo' => $item[0]['sol_sug_ret_tipo'],
            'sol_ticket' => $item[0]['sol_ticket'],
            'tipo' => @$tipo[0]['tip_descripcion'],
            'motivo' => @$motivo[0]['mov_descripcion'],
            'estado' => @$estado[0]['est_descripcion'],
            'estadoPadre' => @$estPadre,
            'rol_descripcion' => $rol[0]['rol_descripcion'],
            'tipo_crn_ins' => $tipo_crn_ins,
            'tipo_crn_ret' => $tipo_crn_ret,
        );
        unset($comentario[0]['estado']);
        $data = array_merge($data, (array) @$comentario[0]);

        foreach ($data as $clave => $valor) {
            $data[$clave] = utf8_decode($valor);
        }

        switch ($tipo_correo) {
            case 'crear':
                $plantilla = 'solicitud_correo';
                break;
            case 'cancelar':
                $plantilla = 'estado_correo';
                break;
            case 'estado':
                $plantilla = 'estado_correo';
                break;
            case 'comentario':
                $plantilla = 'comentario_correo';
                break;
        }
        $message2 = $this->load->view($plantilla, $data, true);

        $this->email->message($message2);
        $this->email->send();
        //echo $this->email->print_debugger();*/
    }

    public function condiciones() {
        $this->load->model('Parametro_model', 'Parametro_model_condiciones', TRUE);
        $condiciones = $this->Parametro_model_condiciones->get_item('condiciones', 'par_nombre');
        unset($this->Parametro_model);
        $condiciones = $condiciones[0]['par_valor'];
        $data = array('titulo' => 'T&eacute;rminos y condiciones', 'condiciones' => $condiciones);
        $this->load->view('solicitud_condiciones', $data);
    }

    public function mensaje($tipo, $dep_id = '') {
        $menu = $this->Menu_model->_getmenu();
        $mensaje = '';
        switch ($tipo) {
            case 1:
                $mensaje = 'El Periodo de creaci&oacute;n de solicitudes ha finalizado.';
                break;
            case 4:
                $this->load->model('Parametro_model', '', TRUE);
                $this->load->model('Departamento_model', '', TRUE);
                $programa = $this->Departamento_model->obtenerPrograma($dep_id);
                $link_coordinadores = $this->Parametro_model->get_item('link_coordinadores', 'par_nombre');
                $pagCoordinadores = "<a target='_blank' href='" . $link_coordinadores[0]['par_valor'] . "'>p&aacute;gina</a>";
                $mensaje = 'El env&iacute;o de solicitudes para ' . $programa . ' se encuentra desactivado. Por favor contacte al coordinador del departamento que ofrece el curso desde la siguiente ' . $pagCoordinadores;
                break;
            case 2:
                break;
            case 3:
                $mensaje = 'No se encuentra registrado el programa con ID: ' . $dep_id;
                break;
            case 5:
                $mensaje = 'No es posible crear su solicitud, usted ha alcanzado el máximo de créditos posibles.';
                break;
        }
        $no_header = $this->session->userdata('rol') != 3 /* estudiante */ ? 'si' : 'no';
        $this->load->view('solicitud_aviso', array('aviso' => $mensaje, 'menu' => $menu, 'titulo' => 'AVISO',
            'rol' => $this->session->userdata('rol'), 'no_header' => $no_header
        ));
    }

    public function columnasno() {
        $columnas = $this->input->post('ocultas');
        $datos_sesion = array('colocultas' => '');
        $this->session->set_userdata($datos_sesion);
        redirect(base_url() . 'index.php/solicitud');
    }

    function nombre_tipo($tipo, $sug = '') {
        switch ($tipo) {
            case 'MAGISTRAL':
                $nombre_tipo = $sug == '' ? ' Magistral' : ' Complementaria';
                break;
            case 'COMPLEMENTARIA':
                $nombre_tipo = $sug == '' ? ' Complementaria' : ' Magistral';
                break;
            default:
                $nombre_tipo = '';
        }
        return $nombre_tipo;
    }

    public function formacomentario($sol_id) {
        $mensaje = '';
        $mensaje_varios = '';
        $ids_habilitados = '';
        $ids_cancelados = '';
        $mensaje_cancelados = '';
        $contador = 0;
        $contador_c = 0;
        $menu = $this->Menu_model->_getmenu();
        $data = array();
        if ($sol_id) {
            $lista = trim($sol_id, '-');
            $lista = explode('-', $lista);
            foreach ($lista as $id_c) {
                $item = $this->Solicitud_model->get_item($id_c);
//$item[0]['dep_id'] = 'PRUEBA'; ////////////////////////////////////////////prueba eliminar
                if (!$this->_validar_gestion($item[0]['dep_id'])) {
                    $limites_dep = $this->Limite_model->get_item($item[0]['dep_id'], 'dep_id');
                    $aviso = empty($limites_dep) ? 'No se encuentra registrado el programa con ID: ' . $item[0]['dep_id'] : 'El Periodo de gesti&oacute;n de solicitudes ha finalizado.';
                    $mensaje .= 'El Periodo de gesti&oacute;n de la solicitud de id:' . $id_c . ' ha finalizado.<br>';
                    $contador++;
                } elseif (!((($item[0]['est_id'] == '1'/* En revisi?n */ || $item[0]['est_id'] == '5'/* En espera de respuesta del coordinador */) && ($this->session->userdata('rol') == '2'/* Coordinador */ || $this->session->userdata('rol') == '1'/* Administrador */)) ||
                        ($item[0]['est_id'] == '4'/* En espera de respuesta del estudiante */ && $this->session->userdata('rol') == '3'/* Estudiante */))) {
                    $ids_cancelados .= $id_c . ','; //no se pueden comentar por su estado
                } else {
                    $mensaje_varios .= $id_c . ',';
                    $ids_habilitados .= $id_c . ',';
                }
            }
            if ($contador == count($lista)) {
                $this->load->view('solicitud_aviso', array('aviso' => /* 'Recuerde que con esta aplicaci&oacute;n solo se reciben solicitudes para las facultades de <strong>Derecho</strong> y <strong>Econom&iacute;a</strong>.' */ $aviso/* 'El Periodo de gesti&oacute;n de solicitudes ha finalizado.' */, 'menu' => $menu, 'titulo' => 'AVISO', 'no_header' => 'no', 'rol' => $this->session->userdata('rol')));
            } elseif ($contador_c == count($lista)) {
                $this->load->view('solicitud_aviso', array('aviso' => 'No se pueden enviar comentarios debido al estado de las solicitudes.', 'menu' => $menu, 'titulo' => 'AVISO', 'no_header' => 'no', 'rol' => $this->session->userdata('rol')));
            } else {
                if (count($lista) === 1) {
                    $this->comentario($sol_id);
                } else {
                    /* mensaje cuando son varios ids */
                    $mensaje_varios = trim($mensaje_varios, ',');
                    $mensaje_varios = 'Se van a enviar comentarios a las solicitudes con los siguientes ID: ' . $mensaje_varios . '.';
                    $data['mensaje_gestion'] = $mensaje;
                    $data['mensaje_varios'] = $mensaje_varios;
                    $data['mensaje_cancelados'] = $ids_cancelados == '' ? '' : 'No se pueden enviar comentarios debido al estado de las solicitudes con los siguientes ID:' . trim($ids_cancelados, ',') . '.';
                    $data['tipo'] = 'varios';
                }
                $this->load->model('Comentario_model', '', TRUE);
                $this->load->model('Rol_model', '', TRUE);

                $this->load->model('Parametro_model', '', TRUE);
                $comentario_normal = $this->Parametro_model->get_item('comentario normal', 'par_nombre');
                $comentario_cancelar = $this->Parametro_model->get_item('comentario cancelar', 'par_nombre');
                $comentario_cambiar_estado = $this->Parametro_model->get_item('comentario cambiar estado', 'par_nombre');
                unset($this->Parametro_model);
                $datac = array('sol_id' => $sol_id,
                    'com_nombre' => $this->session->userdata('nombres'),
                    'rol_id' => $this->session->userdata('rol'),
                    'accion' => '',
                    'comentario_normal' => $comentario_normal[0]['par_valor'],
                    'comentario_cancelar' => $comentario_cancelar[0]['par_valor'],
                    'comentario_cambiar_estado' => $comentario_cambiar_estado[0]['par_valor'],
                );
                //$this->_prepare_list_comentario($datac, $id, $this->Comentario_model, false);
                unset($this->Comentario_model);
                unset($this->Rol_model);
                $comentario_form = $this->load->view('comentario_form', $datac, true);
                $data['sol_id'] = $sol_id;
                $data['accion'] = 'estado';
                $data['titulo'] = 'Comentario';
                $data['comentario_form'] = $comentario_form;
                $data['menu'] = $menu;
                $data['ids_habilitados'] = $ids_habilitados;
                //$data['sol_id'] = $sol_id;
                $data['ordenfilas'] = $this->session->userdata('ordenfilas');
                $this->load->view('solicitud_form_comentar_masivo', $data);
            }
        }
    }

    public function comentar_masivo() {
        $this->load->model('Comentario_model', '', TRUE);
        $this->load->model('Rol_model', '', TRUE);
        $res = '';
        $sol_id = $this->input->post('sol_id');
        if ($sol_id) {
            $lista = trim($sol_id, ',');
            $lista = explode(',', $sol_id);
            foreach ($lista as $item) {
                if (!empty($item)) {
                    //$data = array('est_id'=>6, 'sol_fec_actualizacion' => date("Y-m-d H:i:s"));
                    //if($this->Solicitud_model->update($item,$data)) {
                    $res = 'OK';
                    //}
                    //asocio el comentario
                    $datac = array(
                        'com_texto' => $this->input->post('com_texto'),
                        'com_login' => $this->session->userdata('login'),
                        'com_nombre' => $this->session->userdata('nombres'),
                        'sol_id' => $item,
                        'rol_id' => $this->session->userdata('rol'),
                    );
                    if ($datac['com_texto'] != '') {
                        $this->Comentario_model->insert($datac);

                        $est_id = $this->session->userdata('rol') == '3'/* estudiante */ ? '5'/* En espera de respuesta del coordinador */ : '4'; //En espera de respuesta del estudiante
                        $datosol = array('est_id' => $est_id, 'sol_fec_actualizacion' => date("Y-m-d H:i:s"));
                        $this->Solicitud_model->update($item, $datosol);
                    }
                    $datos = $this->Solicitud_model->get_item($item, 'sol_id');
                    $this->enviarCorreo('comentario', $datos[0]['sol_email'], 'Comentario solicitud', $item);
                }
            }
        }
        $this->index();
    }

    public function validarCrnEstudiante() {

        $code = $this->input->post('codigo');


        $codigo = ($code == 'undefined') ? $this->session->userdata('UACarnetEstudiante') : $code; //;
        $materia = $this->input->post('materia');
        $attr_curso = $this->input->post('attr_curso');
        $crn = $this->input->post('crn');
        $tipo = $this->input->post('tipo');
        $tiposol = $this->input->post('tipoSol');
        $periodo = $this->input->post('periodo');

        if (!$codigo || !$crn) {
            $msj = "Datos Insuficientes";
        }
        $rta['msj_crn'] = "";
        $rta['msj_cruceh'] = "";
        $rta['cruceh'] = 0;
        $this->load->model('Parametro_model', '', TRUE);
        // LS: se debe validar.... hace falta.
        $resultado = $this->Solicitud_model->validarCrnBloqueado($crn, $periodo);
        // echo "<pre>";
        // print_r($resultado);
        // exit;
        // if (count($resultado) > 0) {
        // $periodo_crn
        $periodo = $this->input->post("periodo");
        foreach ($resultado as $crn_bloqs) {
            if ($crn_bloqs['periodo'] == $periodo) {
                $rta['msj'] = "La creación de solicitudes para el CRN " . ($crn) . " ha sido bloqueada";
                $rta['pass'] = 0;
            }
        }

        // } else {
        $periodo = $this->input->post("periodo");
        //Si el tipo de solicitud es Inscr, Inscr/ret o Cambio sec. solo es posible seleccionar magistrales
        $crnIsMag = $this->Solicitud_model->crnIsMagistral($crn, $periodo);
        $tipoSolicitud = $this->input->post('tiposol');
        $tipos = array(1, 2, 3);
        if ((!$crnIsMag) && (in_array($tipoSolicitud, $tipos)) && $tiposol != 'com') {
            $rta['msj'] = "Usted debe seleccionar un curso Magistral, para los tipos de solicitud: Inscripción, Inscripción y Retiro y Cambio de Sección.";
            $rta['pass'] = 0;
        } else {
            $this->load->model('Parametro_model', '', TRUE);
            // $periodo = $this->Parametro_model->get_item('periodo','par_nombre');
            // $periodo = $periodo[0]['par_valor'];
            $resultado = $this->Solicitud_model->validarItemCodigoCrn($codigo, $crn, $periodo);
            // echo "<pre>"; print_r($resultado); exit;
            $codigoMaterias = $this->Solicitud_model->traerCodigoMateria($codigo, $periodo);
            $materiaInscrita = false;
            foreach ($codigoMaterias as $__asignatura) {
                $asignatura = (array) $__asignatura;
                if ($asignatura['sol_ins_mat'] == $materia) {
                    $materiaInscrita = true;
                }
            }
            if (count($resultado) > 0) {
                $rta['msj'] = "Ya existe un registro en el sistema para el CRN " . $crn;
                $rta['pass'] = 0;
            } else if ($materiaInscrita) {

                $rta['msj'] = "Ya existe un registro en el sistema para la materia " . $materia;
                $rta['pass'] = 0;
            } else {
                $rta['msj'] = "si se puede";
                $rta['pass'] = 1;
                //Se consulta los mensajes asociados el CRN seleccionado
                $msgCRN = $this->Solicitud_model->msgCRN($crn);
                if (!empty($msgCRN)) {
                    $rta['msj_crn'] = $msgCRN;
                }
                $HorarioCRN = $this->Solicitud_model->conultarHorarioCRN($crn, $periodo);
                if ($codigo < 1) {
                    $codigo = $this->session->userdata('UACarnetEstudiante');
                }
                $existeCruceH = $this->hayCruceHorario($crn, $HorarioCRN, $codigo, $periodo);
                // var_dump($existeCruceH); exit;
                if ($existeCruceH && ($tipoSolicitud != 3 && $tipoSolicitud != 2  && $tipoSolicitud != 4)) {
                    $msgCruceHorario = $this->Parametro_model->get_item('msgcrucehorario', 'par_nombre');
                    $rta['msj_cruceh'] = $msgCruceHorario[0]['par_valor'];
                    $rta['cruceh'] = 1;
                    $rta['pass'] = 0;
                    $rta['msj'] = "No se puede registrar su solicitud, debido a que existe un cruce de horario con otra materia inscrita en banner";
                }
            }
        }
        // }
        // echo "<pre>";print_r($rta);exit;
        echo json_encode($rta);
    }

    public function crear_fechas_para_validar_cruce($fecha, $_d) {
        $date = date("Y-m-d-N", strtotime($fecha));
        $info_date = explode("-", $date);
        $anio = $info_date[0];
        $mes = $info_date[1];
        $dia = $info_date[2];
        $dsemana = $info_date[3];
        $fecha = explode(" ", date("Y-m-d N", mktime(0, 0, 0, $mes, $dia + $_d, $anio)));
        return array(
            "fecha" => $fecha[0],
            "dia" => $fecha[1]
        );
    }

    //LS: Optimizada para tener en cuenta todas las condiciones.
    public function hayCruceHorario($crn, $horarioGlobalCrn, $codigo, $periodo) {

        $pidm = $this->integracion->obtenerPidm($codigo);
        $horarioEst = $this->integracion->dataHorarioEstudiante($pidm, $periodo);


        // LS: Se maqueta el array del horario del estudiante con las fechas que corresponde a cada franja por dia.
        $arr_est = array();
        foreach ($horarioEst as $a) {
            if (!empty($a)) {
                $date1 = date("Y-m-d", strtotime($a["fechainicio"]));
                $date2 = date("Y-m-d", strtotime($a["fechafin"]));
                $dia = 0;
                while ($date1 < $date2) {
                    $_rdate = $this->crear_fechas_para_validar_cruce($date1, $dia);
                    $date1 = $_rdate["fecha"];
                    $dia = 1;
                    if (!empty($a["domingo"]) && $_rdate["dia"] == 7) {
                        $arr_est["D"][$date1][] = array(
                            "I" => date("H:i", strtotime($a["hini"])),
                            "F" => date("H:i", strtotime($a["hfin"]))
                        );
                    }
                    if (!empty($a["lunes"]) && $_rdate["dia"] == 1) {
                        $arr_est["L"][$date1][] = array(
                            "I" => date("H:i", strtotime($a["hini"])),
                            "F" => date("H:i", strtotime($a["hfin"]))
                        );
                    }
                    if (!empty($a["martes"]) && $_rdate["dia"] == 2) {
                        $arr_est["M"][$date1][] = array(
                            "I" => date("H:i", strtotime($a["hini"])),
                            "F" => date("H:i", strtotime($a["hfin"]))
                        );
                    }
                    if (!empty($a["miercoles"]) && $_rdate["dia"] == 3) {
                        $arr_est["I"][$date1][] = array(
                            "I" => date("H:i", strtotime($a["hini"])),
                            "F" => date("H:i", strtotime($a["hfin"]))
                        );
                    }
                    if (!empty($a["jueves"]) && $_rdate["dia"] == 4) {
                        $arr_est["J"][$date1][] = array(
                            "I" => date("H:i", strtotime($a["hini"])),
                            "F" => date("H:i", strtotime($a["hfin"]))
                        );
                    }
                    if (!empty($a["viernes"]) && $_rdate["dia"] == 5) {
                        $arr_est["V"][$date1][] = array(
                            "I" => date("H:i", strtotime($a["hini"])),
                            "F" => date("H:i", strtotime($a["hfin"]))
                        );
                    }
                    if (!empty($a["sabado"]) && $_rdate["dia"] == 6) {
                        $arr_est["S"][$date1][] = array(
                            "I" => date("H:i", strtotime($a["hini"])),
                            "F" => date("H:i", strtotime($a["hfin"]))
                        );
                    }
                }
            }
        }

        // LS: Se maqueta el array del horario del CRN con las fechas que corresponde a cada franja por dia.
        $arr_crn = array();
        foreach ($horarioGlobalCrn as $a) {
            if (!empty($a)) {
                $date1 = date("Y-m-d", strtotime($a["FECHAINICIO"]));
                $date2 = date("Y-m-d", strtotime($a["FECHAFIN"]));
                $dia = 0;
                while ($date1 < $date2) {
                    $_rdate = $this->crear_fechas_para_validar_cruce($date1, $dia);
                    $date1 = $_rdate["fecha"];
                    $dia = 1;
                    if (!empty($a["DOMINGO"]) && $_rdate["dia"] == 7) {
                        $arr_crn["D"][$date1][] = array(
                            "I" => date("H:i", strtotime($a["HINI"])),
                            "F" => date("H:i", strtotime($a["HFIN"]))
                        );
                    }
                    if (!empty($a["LUNES"]) && $_rdate["dia"] == 1) {
                        $arr_crn["L"][$date1][] = array(
                            "I" => date("H:i", strtotime($a["HINI"])),
                            "F" => date("H:i", strtotime($a["HFIN"]))
                        );
                    }
                    if (!empty($a["MARTES"]) && $_rdate["dia"] == 2) {
                        $arr_crn["M"][$date1][] = array(
                            "I" => date("H:i", strtotime($a["HINI"])),
                            "F" => date("H:i", strtotime($a["HFIN"]))
                        );
                    }
                    if (!empty($a["MIERCOLES"]) && $_rdate["dia"] == 3) {
                        $arr_crn["I"][$date1][] = array(
                            "I" => date("H:i", strtotime($a["HINI"])),
                            "F" => date("H:i", strtotime($a["HFIN"]))
                        );
                    }
                    if (!empty($a["JUEVES"]) && $_rdate["dia"] == 4) {
                        $arr_crn["J"][$date1][] = array(
                            "I" => date("H:i", strtotime($a["HINI"])),
                            "F" => date("H:i", strtotime($a["HFIN"]))
                        );
                    }
                    if (!empty($a["VIERNES"]) && $_rdate["dia"] == 5) {
                        $arr_crn["V"][$date1][] = array(
                            "I" => date("H:i", strtotime($a["HINI"])),
                            "F" => date("H:i", strtotime($a["HFIN"]))
                        );
                    }
                    if (!empty($a["SABADO"]) && $_rdate["dia"] == 6) {
                        $arr_crn["S"][$date1][] = array(
                            "I" => date("H:i", strtotime($a["HINI"])),
                            "F" => date("H:i", strtotime($a["HFIN"]))
                        );
                    }
                }
            }
        }
        // LS: Se realiza la validacion para saber si hay cruce o no.
        $cruce = false;
        foreach ($arr_crn as $dia => $fechas) {
            foreach ($fechas as $fec => $horas) {
                if ($arr_est[$dia][$fec]) {
                    foreach ($horas as $hr_crn) {
                        foreach ($arr_est[$dia][$fec] as $hr_est) {
                            if ($hr_crn["I"] < $hr_est["I"] && $hr_crn["F"] > $hr_est["I"] && $hr_crn["F"] < $hr_est["F"]) {
                                $cruce = true;
                            }
                            if ($hr_crn["I"] >= $hr_est["I"] && $hr_crn["F"] <= $hr_est["I"]) {
                                $cruce = true;
                            }
                            if ($hr_crn["I"] < $hr_est["F"] && $hr_crn["F"] > $hr_est["F"]) {
                                $cruce = true;
                            }
                            if ($hr_crn["I"] >= $hr_est["I"] && $hr_crn["F"] < $hr_est["F"]) {
                                $cruce = true;
                            }
                            if ($hr_crn["I"] > $hr_est["I"] && $hr_crn["F"] <= $hr_est["F"]) {
                                $cruce = true;
                            }
                            if ($hr_crn["I"] == $hr_est["F"] && $hr_crn["F"] > $hr_est["F"]) {
                                $cruce = false;
                            }
                            if ($hr_crn["I"] < $hr_est["I"] && $hr_crn["F"] == $hr_est["I"]) {
                                $cruce = false;
                            }
                            if ($hr_crn["I"] == $hr_est["I"] && $hr_crn["F"] == $hr_est["F"]) {
                                $cruce = true;
                            }
                            if ($cruce) {
                                break;
                            }
                        }
                        if ($cruce) {
                            break;
                        }
                    }
                    if ($cruce) {
                        break;
                    }
                }
                if ($cruce) {
                    break;
                }
            }
            if ($cruce) {
                break;
            }
        }
        return $cruce;
    }

    public function hayCruceHorario_bk($crn, $horarioGlobalCrn, $codigo, $periodo) {
        $pidm = $this->integracion->obtenerPidm($codigo);
        $horarioEst = $this->integracion->dataHorarioEstudiante($pidm, $periodo);
        foreach ($horarioGlobalCrn as $HorarioCRN) {
            if (count($HorarioCRN) < 1)
                return false;
            $diasHorarioCRN = array();
            if (!empty($HorarioCRN['DOMINGO']))
                $diasHorarioCRN[] = 'domingo';
            if (!empty($HorarioCRN['LUNES']))
                $diasHorarioCRN[] = 'lunes';
            if (!empty($HorarioCRN['MARTES']))
                $diasHorarioCRN[] = 'martes';
            if (!empty($HorarioCRN['MIERCOLES']))
                $diasHorarioCRN[] = 'miercoles';
            if (!empty($HorarioCRN['JUEVES']))
                $diasHorarioCRN[] = 'jueves';
            if (!empty($HorarioCRN['VIERNES']))
                $diasHorarioCRN[] = 'viernes';
            if (!empty($HorarioCRN['SABADO']))
                $diasHorarioCRN[] = 'sabado';
            foreach ($horarioEst as $key => $value) {
                // echo "<pre>"; print_r($value);
                if (is_array($value)) {
                    $fechainicio_crn = $HorarioCRN['FECHAINICIO'];
                    $fechafin_crn = $HorarioCRN['FECHAFIN'];
                    $HorarioCRN_Est = $this->Solicitud_model->conultarHorarioCRN($value['crn'], $periodo);
                    $fechainicio_est = $HorarioCRN_Est[0]['FECHAINICIO'];
                    $fechafin_est = $HorarioCRN_Est[0]['FECHAFIN'];
                    // $fechainicio_est =   $value['fechainicio'];
                    // $fechafin_est        =   $value['fechafin'];
                    // $fechainicio_crn =   "13-MAY-17";    
                    // $fechafin_est        =   "13-MAY-17";
                    foreach ($diasHorarioCRN as $k2 => $dia) {
                        if (!empty($value[$dia])) {
                            if ($HorarioCRN['HINI'] <= $value['hfin'] && $HorarioCRN['HFIN'] >= $value['hini']) {
                                //valido que la fecha de inicio del curso a inscribir sea superior a la fecha final de los cursos con cruze de horario
                                // if (strtotime($fechainicio_crn) > strtotime($fechafin_est)){ 
                                // echo "<pre>";
                                // print_r($HorarioCRN);
                                // print_r($value);
                                // print_r($value);
                                if (strtotime($fechainicio_crn) < strtotime($fechafin_est)) {
                                    // echo "<pre>11s"; 
                                    // echo "data inicio: ";
                                    // print_r(strtotime($fechainicio_crn)); 
                                    // echo "data FIN: ";
                                    // print_r(strtotime($fechafin_est)); 
                                    // // print_r($horarioGlobalCrn);
                                    // exit;
                                    return true;
                                }
                                // return true; 
                                // }                        
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    public function actEstado() {
        $id = mysql_escape_string($this->input->post("id"));
        $this->Solicitud_model->actEstado($id);
        echo "OK";
    }

    public function horario($id) {
        $d = $this->Solicitud_model->get_item($id);

        $materias = $this->obtenerMatSol($d[0]);

        $pidm = $d[0]["sol_pidm"];
        $periodo = $d[0]["sol_periodo"];
        echo $this->integracion->horario_estudiantes($pidm, $periodo, $d[0]["sol_nombre"], $d[0]["sol_apellido"], $materias);
        exit;
    }

    private function obtenerMatSol($solicitud) {
        $respuesta = array();
        if (!empty($solicitud['sol_alternativas'])) {
            $alternativas = json_decode($solicitud['sol_alternativas'], true);
            foreach ($alternativas as $alternativa) {

                $respuesta[] = array(
                    "crn" => $alternativa['crn'],
                    "tipo" => "Alternativa");
            }
        }

        if ($solicitud['sol_ins_crn']) {
            $respuesta[] = array(
                "crn" => $solicitud['sol_ins_crn'],
                "tipo" => "Magistral");
        }
        if ($solicitud['sol_sug_ins_crn']) {
            $respuesta[] = array(
                "crn" => $solicitud['sol_sug_ins_crn'],
                "tipo" => "Complementaria");
        }
        if ($solicitud['sol_sug_crns_cc']) {
            $respuesta[] = array(
                "crn" => $solicitud['sol_sug_crns_cc'],
                "tipo" => "Correquisito");
        }
        if ($solicitud['sol_ret_crn']) {
            $respuesta[] = array(
                "crn" => $solicitud['sol_ret_crn'],
                "tipo" => "Retiro");
        }

        return $respuesta;
    }

    public function marcar_registro(){
        $sol_id = $this->input->post("sol_id");
        $marca = $this->input->post("marca");
        $datos = array(
            'sol_marca' => $marca
        );
        $this->Solicitud_model->update($sol_id, $datos);
        echo "OK";
    }

}
