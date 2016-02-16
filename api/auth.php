<?php
// Initialize session
session_start();
require_once('dbconn.inc');
$call = $_GET['call'];
switch ($call) {
	case 'check-auth':
		if(isset($_SESSION['auth']) && $_SESSION['auth'])
			echo json_encode(true);
		else
			echo json_encode(false);
		break;
	case 'logout':
		session_destroy();
		echo json_encode(true);
		break;
	case 'login':
		echo json_encode(authenticate($_POST['username'], $_POST['password']));
		break;
	default:
		print_r($_SESSION);
		return false;
		break;
}
 
function authenticate($user, $password) {
	global $db;
	if(empty($user) || empty($password)) return false;
	// Active Directory server
	$ldap_host = "ottsrv.ottcom.com";
	// Active Directory DN
	$ldap_dn = "OU=Ott Staff,DC=ott,DC=local";
	// Domain, for purposes of constructing $user
	$ldap_usr_dom = '@ott.local';
 
	// connect to active directory
	$ldap = ldap_connect($ldap_host);
 
	// verify user and password
	if($bind = @ldap_bind($ldap, $user.$ldap_usr_dom, $password)) {
		$stmt = $db->prepare("SELECT * from users where username = :username");
		$stmt->bindParam(':username', $user);
		$stmt->execute();
		$user_properties = $stmt->fetchAll(PDO::FETCH_OBJ);
		foreach ($user_properties[0] as $property=>$value){
			$_SESSION[$property] = $value;
		}
		$_SESSION['auth'] = true;
		return true;
	} else {
		// invalid name or password
		return false;
	}
}
