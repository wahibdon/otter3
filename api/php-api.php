<?php
require_once('dbconn.inc');
$call = $_GET['call'];
switch ($call){
	case 'list-vendors':
		echo json_encode(listTable("vendors"));
		break;
	case 'list-clients':
		echo json_encode(listTable("clients"));
	break;
		case 'show-contact-vendor':
		switch ($_GET['type']) {
			case 'vendors':
				$table = 'vendor_contact';
				$col = 'vendor_id';
				break;
			case 'clients':
				$table = 'client_contact';
				$col = 'client_id';
				break;
			default:
				die();
				break;
		}
		$stmt = $db->prepare("select * from $table where $col = :id");
		$stmt->bindParam(":id", $_GET['id']);
		$stmt->execute();
		echo json_encode($stmt->fetchAll(PDO::FETCH_OBJ));
		break;
	case 'job-search':
		if(!isset($_GET['page']))
			break;
		$lower = ($_GET['page']-1)*50;
		$upper = $_GET['page']*50;
		$stmt = $db->prepare("select jobs.*, abbr, users.first, users.last from jobs left join clients on jobs.client_id = clients.id left join users on users.id = jobs.creator order by id DESC limit ?");
		$stmt->bindValue(1, $lower, PDO::PARAM_INT);
		$stmt->bindValue(2, $upper, PDO::PARAM_INT);
		$stmt->execute();
		//print_r($db->errorInfo());
		echo json_encode($stmt->fetchAll(PDO::FETCH_OBJ));
		break;
	default:
		echo json_encode("false");
		break;
}
function listTable($table){
	global $db;
	$stmt = $db->prepare("select * from $table order by name");
	$stmt->execute();
	return $stmt->fetchAll(PDO::FETCH_OBJ);
}