<?php
	#####################################################  
	# FORMATA O CAMINHO ROOT
	#####################################################
	$r                        = $_SERVER["DOCUMENT_ROOT"];
	$_SERVER["DOCUMENT_ROOT"] = (substr($r, -1) == '/') ? substr($r, 0, -1) : $r;

	#####################################################  
	# DEFINE O PATH DO MÓDULO 
	#####################################################
	define("PATH", 'App/Modulos/usuarios');
		
	#####################################################  
	# LIMPA O CACHE INTERNO
	#####################################################
	clearstatcache();
	
	#####################################################  
	# CONTROLA O CACHE
	#####################################################
	header("Expires: Mon, 26 Jul 1990 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	
	#####################################################  
	# IMPORTA A CLASSE PADRÃO DO SISTEMA
	#####################################################
	ob_start();
	include($_SERVER['DOCUMENT_ROOT'] . '/admin/App/Lib/class-ws-v1.php');
	
	#####################################################  
	# VERIFICA SE ESTAMOS USANDO O MODO "INSECURE"  
	#####################################################
	if(SECURE===FALSE) die(_erro(" NÃO É POSSÍVEL ACESSAR O GERENCIADOR DE USUÁRIOS NO MODO 'INSECURE'"));

	#####################################################  
	# CRIA SESSÃO
	#####################################################  
	$user = new session();
	
	#####################################################  
	# VERIFICA SE O USUÁRIO ESTÁ LOGADO OU AS SESSÕES E COOKIES ESTÃO EM ORDEM
	#####################################################
	verifyUserLogin();
	
	#####################################################  
	# DEFINE O LINK DO TEMPLATE DESTE MÓDULO 
	#####################################################  
	define("TEMPLATE_LINK", ROOT_ADMIN . "/App/Templates/html/Modulos/usuarios/ws-tool-usuarios-index.html");

	############################################################################################
	# MONTAMOS A CLASSE DOS TEMPLATES 
	############################################################################################
	$template           									= new Template(TEMPLATE_LINK, true);
	$template->PATH 										= PATH;
	$template->userManager_index_mainTitle 					= ws::getLang("userManager>index>mainTitle");
	$template->userManager_index_editMyProfile 				= ws::getLang("userManager>index>editMyProfile");
	$template->userManager_index_editMyProfilePlaceHolder 	= ws::getLang("userManager>index>editMyProfilePlaceHolder");
	$template->userManager_index_register 					= ws::getLang("userManager>index>register");
	$template->userManager_index_noRegisteredUsers 			= ws::getLang("userManager>index>noRegisteredUsers");
	$template->userManager_index_deleteUser 				= ws::getLang("userManager>index>deleteUser");
	$template->userManager_index_editPermissions 			= ws::getLang("userManager>index>editPermissions");
	$template->userManager_index_modal_delete_content 		= ws::getLang("userManager>index>modal>delete>content");
	$template->userManager_index_modal_delete_button1 		= ws::getLang("userManager>index>modal>delete>button1");
	$template->userManager_index_modal_delete_button2 		= ws::getLang("userManager>index>modal>delete>button2");
	$template->userManager_index_topAlert_delete_sucess 	= ws::getLang("userManager>index>topAlert>delete>sucess");
	$template->userManager_index_topAlert_delete_fail 		= ws::getLang("userManager>index>topAlert>delete>fail");

	#####################################################
	# BUSCAMOS NA BASE O USUÁRIO COM O ID DA SESSÃO 
	#####################################################
	$iUser = new MySQL();
	$iUser->set_table(PREFIX_TABLES.'ws_usuarios');
	$iUser->set_where('id="'.$user->get('id').'"');
	$iUser->set_where('AND ativo="1"');
	$iUser->select();
 	$iUser= $iUser->fetch_array[0];

	$template->ID_USER 		= $iUser['id'];
	#####################################################
	# MONTAMOS A CLASSE DOS TEMPLATES 
	#####################################################
	if($iUser['add_user']=="1" || $iUser['admin']=="1" ){	
		$template->block("BLOCK_TOP_ADMIN");	
	}else{ 
		$template->block("BLOCK_TOP_USER");	
	}

	#####################################################
	# MONTAMOS A CLASSE DOS TEMPLATES 
	#####################################################
	$ListUser = new MySQL();
	$ListUser->set_table(PREFIX_TABLES.'ws_usuarios');
	$ListUser->set_where('ativo="1"');
	$ListUser->set_where('AND  id<>"'.$iUser['id'].'"');
	if($iUser['admin']!="1"){		
		$ListUser->set_where('AND admin="0"');
	}
	$ListUser->select();
	$template->NO_USER = ($ListUser->_num_rows==0) ?  "block;" : "none;";	

	#####################################################
	# VARREMOS A BASE E RETORNAMOS A LISTA DE USUÁRIOS 
	#####################################################
	foreach ($ListUser->fetch_array as $row) {
		$template->LI_ID		=	$row['id'];
		$template->LI_NOME 		=	$row['nome'];
		$template->LI_SOBRENOME =	$row['sobrenome'];
		$template->LI_LOGIN 	=	$row['login'];
		$template->LI_EMAIL 	=	$row['email'];
		if($iUser['add_user']=="1" || $iUser['admin']=="1"){
			$template->block("LI_ADMIN_EXCLUDE"); 	
		}
		$template->block("LI_USER"); 	
	}

	############################################################
	# FINALIZAMOS O ARQUIVO, SETAMOS O BLOCO E RETORNAMOS O HTML
	############################################################
	$template->block("USER_MODEL"); 	
	$template->show(); 	