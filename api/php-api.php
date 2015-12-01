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
		if(!isset($_GET['page']) || !isset($_GET['status']))
			break;
		$search_string = '';
		if($_GET['search_string']!=''){
			switch ($_GET['search_type']) {
				case 'All':
					$params = [];
					preg_match('/([A-Za-z]{3})([0-9]{5,6})/', $_GET['search_string'], $matches);
					if($matches[0]){
						$params['abbr'] = $matches[1];
						$params['number'] = $matches[2];
					}
					//
					$stmt = $db->prepare("select jobs.*, abbr, users.first, users.last from jobs left join clients on jobs.client_id = clients.id left join users on users.id = jobs.creator where job_status like :status order by id DESC");
					break;
				case 'Job ':
					preg_match('/^([A-Za-z]{3})([0-9]{5}|[0-9]{6})$/', $_GET['search_string'], $matches);
					preg_match('/^[A-Za-z]{3}$/', $_GET['search_string'], $abbr);
					if(isset($matches[0])){
						$stmt = $db->prepare("select jobs.*, abbr, users.first, users.last from jobs left join clients on jobs.client_id = clients.id left join users on users.id = jobs.creator where job_status like :status and (jobs.number=:number and abbr=:abbr) order by id DES");
						$stmt->bindParam(':number', $matches[2]);
						$stmt->bindParam(':abbr', $matches[1]);
					}else if($abbr[0]){
						$stmt = $db->prepare("select jobs.*, abbr, users.first, users.last from jobs left join clients on jobs.client_id = clients.id left join users on users.id = jobs.creator where job_status like :status and (abbr=:abbr) order by id DESC");
						$stmt->bindParam(':abbr', $abbr[0]);
					}else {
						die(json_encode(null));
					}
					break;
				case 'Title':
					$stmt = $db->prepare("select jobs.*, abbr, users.first, users.last from jobs left join clients on jobs.client_id = clients.id left join users on users.id = jobs.creator where job_status like :status and (jobs.title like :title) order by id DESC");
					$stmt->bindValue(':title', '%'.$_GET['search_string'].'%');
					break;
				default:
					break;
			}
		}else{
			$stmt = $db->prepare("select jobs.*, abbr, users.first, users.last from jobs left join clients on jobs.client_id = clients.id left join users on users.id = jobs.creator where job_status like :status order by id DESC");
		}
		$status = ($_GET['status'] == 0) ? '%' : $_GET['status'];
		$lower = ($_GET['page']-1)*50;
		$stmt->bindValue(':status', $status);
		//$stmt->bindValue(':lower', $lower, PDO::PARAM_INT);
		$stmt->execute();
		$jobs = $stmt->fetchAll(PDO::FETCH_OBJ);
		/*$stmt = $db->prepare("select count(jobs.id) from jobs left join clients on jobs.client_id = clients.id where job_status like ? $search_string");
		$stmt->bindValue(1, $status);
		$stmt->execute();
		$count = $stmt->fetchColumn();*/
		echo json_encode([count($jobs), array_splice($jobs, $lower, 50), $search_string, $_GET]);
		break;
	case 'job':
		if(isset($_GET['number'])){
			$stmt = $db->prepare("select jobs.*, abbr, concat(users.first, ' ', users.last) creator, clients.name, concat(client_contact.first, ' ', client_contact.last) contact from jobs left join clients on jobs.client_id = clients.id left join users on users.id = jobs.creator left join client_contact on client_contact.id = jobs.contact_id where jobs.number=:number");
			$stmt->bindValue(':number', $_GET['number'], PDO::PARAM_INT);
			$stmt->execute();
			echo json_encode($stmt->fetchAll(PDO::FETCH_OBJ));
		}
		break;
	case 'job-additional':
		if(isset($_GET['number'])){
			$estimates = $db->prepare("select estimates.*, concat(users.first, ' ', users.last) added_by from estimates left join jobs on estimates.job_id = jobs.id left join users on estimates.added_by = users.id where jobs.number = :number");
			$estimates->bindValue(":number", $_GET['number']);
			$estimates->execute();
			$times = $db->prepare("select times.* from times left join jobs on times.job_id = jobs.id where jobs.number = :number");
			$times->bindValue(":number", $_GET['number']);
			$times->execute();
			$timecodes = $db->prepare("select timecodes.* from timecodes left join jobs on jobs.client_id = timecodes.client_id where jobs.number = :number or timecodes.client_id = 0 limit 0,1");
			$timecodes->bindValue(":number", $_GET['number']);
			$timecodes->execute();
			$expenses = $db->prepare("select expenses.* from expenses left join jobs on expenses.job_id = jobs.id where jobs.number = :number");
			$expenses->bindValue(":number", $_GET['number']);
			$expenses->execute();
			$invoices = $db->prepare("select invoices.* from invoices left join jobs on invoices.job_id = jobs.id where jobs.number = :number");
			$invoices->bindValue(":number", $_GET['number']);
			$invoices->execute();
			$art = $db->prepare("select art.* from art left join jobs on art.job_id=jobs.id where jobs.number = :number");
			$art->bindValue(":number", $_GET['number']);
			$art->execute();
			echo json_encode(["estimates"=>$estimates->fetchAll(PDO::FETCH_OBJ), "times"=>$times->fetchAll(PDO::FETCH_OBJ), "timecodes" => $timecodes->fetchAll(PDO::FETCH_OBJ)[0], "expenses" => $expenses->fetchAll(PDO::FETCH_OBJ), "invoices" => $invoices->fetchAll(PDO::FETCH_OBJ), "art" => $art->fetchAll(PDO::FETCH_OBJ)]);
		}
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