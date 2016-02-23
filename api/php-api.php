<?php
session_start();
require_once('dbconn.inc');
$call = $_GET['call'];
switch ($call){
	case 'list-vendors':
		echo json_encode(listTable("vendors"));
		break;
	case 'list-clients':
		echo json_encode(listTable("clients"));
		break;
	case 'list-users':
		$stmt=$db->prepare("select id, first, last from users where type!=0 order by first ASC");
		$stmt->execute();
		echo json_encode($stmt->fetchAll(PDO::FETCH_OBJ));
		break;
	case 'home-task-list':
		$stmt = $db->prepare("SELECT tasks.id as id, jobs.number, tasks.due, tasks.created, tasks.title, jobs.title as job_title, clients.abbr, tasks.title FROM otter3.task_users left join tasks on task_users.task_id = tasks.id left join users on users.id=task_users.user_id left join jobs on tasks.job_id = jobs.id left join clients on clients.id = jobs.client_id where users.id=:uid and task_users.status != 1");
		$stmt->bindParam(':uid', $_SESSION['id'], PDO::PARAM_INT);
		$stmt->execute();
		echo json_encode($stmt->fetchAll(PDO::FETCH_OBJ));
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
	case 'list-team':
		$stmt = $db->prepare("select * from users where type != 0");
		$stmt->execute();
		echo json_encode($stmt->fetchAll(PDO::FETCH_OBJ));
		break;
	case 'show-task':
		$stmt = $db->prepare("select tasks.*, users.first, users.last, jobs.number, clients.abbr from tasks left join users on creator=users.id left join jobs on jobs.id=tasks.job_id left join clients on jobs.client_id = clients.id where tasks.id=:task_id");
		$stmt->bindParam(":task_id", $_GET['task_id'], PDO::PARAM_INT);
		$stmt->execute();
		$users_assign = $db->prepare("select * from task_users left join users on task_users.user_id = users.id where task_id = :task_id");
		$users_assign->bindParam(":task_id", $_GET['task_id'], PDO::PARAM_INT);
		$users_assign->execute();
		echo json_encode(['task' => $stmt->fetchAll(PDO::FETCH_OBJ), 'users' => $users_assign->fetchAll(PDO::FETCH_OBJ)]);
		break;
	case 'job-search':
		if(!isset($_GET['page']) || !isset($_GET['status']))
			break;
		$search_string = '';
		if($_GET['search_string']!=''){
			switch ($_GET['search_type']) {
				case 'All':
//					$params = [];
//					preg_match('/([0-9A-Za-z]{3})?([0-9]{5,6})?/', $_GET['search_string'], $matches);
//						print_r($matches);
//					//
//					$stmt = $db->prepare("select jobs.*, abbr, users.first, users.last from jobs left join clients on jobs.client_id = clients.id left join users on users.id = jobs.creator where job_status like :status and number like :number and abbr like :abbr order by id DESC");
//					$abbr = isset($matches[1]) ? $matches[1] : '%%';
//					$number = isset($matches[2]) ? $matches[2] : '%%';
//					$stmt->bindValue(':abbr', $abbr);
//					$stmt->bindValue(':number', $number);
					break;
				case 'Job ':
					preg_match('/^([0-9A-Za-z]{3})?([0-9]{5,6})$/', $_GET['search_string'], $matches);
					preg_match('/^[0-9A-Za-z]{3}$/', $_GET['search_string'], $abbr);
					if(isset($matches[0])){
						$stmt = $db->prepare("select jobs.*, abbr, users.first, users.last from jobs left join clients on jobs.client_id = clients.id left join users on users.id = jobs.creator where job_status like :status and (jobs.number=:number and abbr like :abbr) order by id DESC");
						$stmt->bindParam(':number', $matches[2]);
						$stmt->bindValue(':abbr', '%'.$matches[1].'%');
					}else if(isset($abbr[0])){
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
			$stmt = $db->prepare("select jobs.*, abbr, concat(users.first, ' ', users.last) creator, clients.name from jobs left join clients on jobs.client_id = clients.id left join users on users.id = jobs.creator where jobs.number=:number");
			$stmt->bindValue(':number', $_GET['number'], PDO::PARAM_INT);
			$stmt->execute();
			echo json_encode($stmt->fetchAll(PDO::FETCH_OBJ));
		}
		break;
	case 'task-done':
		$stmt = $db->prepare('update task_users set status=if(status = 1, 0, 1) where task_id = :task_id and user_id=:user_id');
		$stmt->bindParam(':task_id', $_GET['task_id'], PDO::PARAM_INT);
		$stmt->bindParam(':user_id', $_SESSION['id'], PDO::PARAM_INT);
		$stmt->execute();
		//$email_info = $db->prepare("");
		//mail($admin_email, "$subject", $comment, "From:" . $email);
		echo json_encode(true);
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
			$expenses = $db->prepare("select expenses.*, concat(users.first, ' ', users.last) added_by from expenses left join jobs on expenses.job_id = jobs.id left join users on expenses.added_by = users.id where jobs.number = :number");
			$expenses->bindValue(":number", $_GET['number']);
			$expenses->execute();
			$invoices = $db->prepare("select invoices.*, concat(users.first, ' ', users.last) added_by from invoices left join jobs on invoices.job_id = jobs.id left join users on invoices.added_by = users.id where jobs.number = :number");
			$invoices->bindValue(":number", $_GET['number']);
			$invoices->execute();
			$art = $db->prepare("select art.*, concat(users.first, ' ', users.last) added_by from art left join jobs on art.job_id=jobs.id left join users on art.added_by = users.id where jobs.number = :number");
			$art->bindValue(":number", $_GET['number']);
			$art->execute();
			$tasks = $db->prepare("SELECT tasks.id, tasks.title `task-title`, GROUP_CONCAT(concat(first, ' ', last)) as names, created, due, if(sum(status) = count(task_id), 1, 0) as status FROM jobs left join tasks on jobs.id = tasks.job_id left join otter3.task_users on tasks.id = task_users.task_id left join users on users.id = user_id where jobs.number = :number group by task_id");
			$tasks->bindValue(":number", $_GET['number']);
			$tasks->execute();
			echo json_encode(["estimates"=>$estimates->fetchAll(PDO::FETCH_OBJ), "times"=>$times->fetchAll(PDO::FETCH_OBJ), "timecodes" => $timecodes->fetchAll(PDO::FETCH_OBJ)[0], "expenses" => $expenses->fetchAll(PDO::FETCH_OBJ), "invoices" => $invoices->fetchAll(PDO::FETCH_OBJ), "art" => $art->fetchAll(PDO::FETCH_OBJ), "tasks" => $tasks->fetchAll(PDO::FETCH_OBJ)]);
		}
		break;
	case 'credentials':
		$stmt = $db->prepare("select * from credentials");
		$stmt->execute();
		echo json_encode($stmt->fetchAll(PDO::FETCH_OBJ));
		break;
	case 'list-client':
		$stmt = $db->prepare("select id, abbr, name from clients order by abbr");
		$stmt->execute(); 
		echo json_encode($stmt->fetchAll(PDO::FETCH_OBJ));
		break;
	case 'submit-form':
		switch ($_POST['form']){
			case 'add-job':
				$current_year = date('y');
				$latest_job_num = $db->prepare("SELECT max(number) from jobs where substring(number, 1, 2) = date_format(now(), '%y');");
				$latest_job_num->execute();
				$latest_job_num = $latest_job_num->fetchColumn();
				$latest_job_num_year = substr($latest_job_num, 0, 2);
				$latest_job_num = substr($latest_job_num, 2);
				if($current_year != $latest_job_num_year){
					$latest_job_num = '0';
					$latest_job_num_year = $current_year;
				}
				$latest_job_num++;
				$latest_job_num = str_pad($latest_job_num, 3, "0", STR_PAD_LEFT);
				$job_num = $latest_job_num_year.$latest_job_num;
				$stmt = $db->prepare("insert into jobs (id, client_id, number, title, description, opened, type, creator, billing_status, job_status) values (:id, :client_id, :number, :title, :description, :opened, :type, :creator, :billing_status, :job_status) on duplicate key update client_id = :client_id, number=:number, title=:title, description=:description, billing_status=:billing_status, job_status=:job_status");
				$stmt->bindParam(':id', $_POST['id'], PDO::PARAM_INT);
				$stmt->bindParam(':client_id', $_POST['client'], PDO::PARAM_INT);
				$stmt->bindParam(':number', $job_num, PDO::PARAM_INT);
				$stmt->bindParam(':title',$_POST['title']);
				$stmt->bindParam(':description',$_POST['description']);
				$stmt->bindValue(':opened', date('Y-m-d'));
				$stmt->bindParam(':type', $_POST['type'], PDO::PARAM_INT);
				$stmt->bindParam(':creator', $_SESSION['id'], PDO::PARAM_INT);
				$stmt->bindParam(':billing_status', $_POST['billing'], PDO::PARAM_INT);
				$stmt->bindParam(':job_status', $_POST['status'], PDO::PARAM_INT);
				$stmt->execute();
				$client_prefix = $db->prepare("SELECT abbr from clients where id = :client_id");
				$client_prefix->bindParam(":client_id", $_POST['client']);
				$client_prefix->execute();
				//mail($admin_email, "$subject", $comment, "From:" . $email);
				echo json_encode($client_prefix->fetchColumn().$job_num);
				break;
			case 'add-time':
				preg_match('/^[0-9A-Za-z]{3}([0-9]{5,6})$/', $_POST['job'], $matches);
				$timecard_prep = $db->prepare("INSERT IGNORE into time_card (user_id, date) values (:user_id, current_date())");
				$timecard_prep->bindParam(':user_id', $_SESSION['id'], PDO::PARAM_INT);
				$timecard_prep->execute();
				$timecard = $db->prepare("select * from time_card where user_id=:user_id and date=current_date()");
				$timecard->bindParam(':user_id', $_SESSION['id']);
				$timecard->execute();
				$time_card_info = $timecard->fetch(PDO::FETCH_OBJ);
				$time = $db->prepare("insert into times (timecard_id, code, totaltime, summary, job_id, cost) values (:timecard_id, :code, :totaltime, :summary, (select id from jobs where number = :number), (select `{$_POST['code']}` from timecodes where client_id = (select client_id from jobs where number = :number) or client_id=0 order by client_id DESC limit 0,1) * :totaltime)");
				$time->bindParam(':timecard_id', $time_card_info->id, PDO::PARAM_INT);
				$time->bindParam(':code', $_POST['code'], PDO::PARAM_INT);
				$time->bindValue(':totaltime', $_POST['hours'].$_POST['minutes'], PDO::PARAM_INT);
				$time->bindParam(':summary', $_POST['summary']);
				$time->bindParam(':number', $matches[1], PDO::PARAM_INT);
				$time->execute();
				echo json_encode(true);
				break;
			case 'add-task':
				preg_match('/^[0-9A-Za-z]{3}([0-9]{5,6})$/', $_POST['job'], $matches);
				$task = $db->prepare("insert into tasks (id, title, summary, due, job_id, creator) values (:id, :title, :summary, str_to_date(:due, '%M %d, %Y %k:%i:%s'), (select id from jobs where number = :number), :creator) on duplicate key update title=:title, summary=:summary, due=str_to_date(:due, '%M %d, %Y %k:%i:%s')");
				$task->bindParam(':id', $_POST['id']);
				$task->bindParam(':title', $_POST['title']);
				$task->bindParam(':summary', $_POST['description']);
				$task->bindValue(':due', $_POST['date']." ".$_POST['due']);
				$task->bindParam(':number', $matches[1], PDO::PARAM_INT);
				$task->bindParam(':creator', $_SESSION['id'], PDO::PARAM_INT);
				$task->execute();
				$task_id = ($db->lastInsertId()) ? $db->lastInsertId() : $_POST['id'];
				$users = explode(',', $_POST['users']);
				if($users[0] !=0 ){
					$remove_users = $db->prepare("delete from task_users where task_id = :task_id");
					$remove_users->bindParam(":task_id", $task_id);
					$remove_users->execute();
					$users_tasks = $db->prepare("insert ignore into task_users (task_id, user_id) values (:task_id, :user_id)");
					$users_tasks->bindParam(":task_id", $task_id);
					for($i=0; $i<count($users); $i++){
						$users_tasks->bindParam(":user_id", $users[$i]);
						$users_tasks->execute();
					}
				}
				echo json_encode(true);
				break;
		}
		break;
	case "team-tasks":
		$stmt = $db->prepare("SELECT *, jobs.title as job_title, clients.abbr, tasks.title FROM otter3.task_users left join tasks on task_users.task_id = tasks.id left join users on users.id=task_users.user_id left join jobs on tasks.job_id = jobs.id left join clients on clients.id = jobs.client_id where users.type != 0 and status !=1 order by first;");
		$stmt->execute();
		echo json_encode($stmt->fetchAll(PDO::FETCH_OBJ));
		break;
	case "unassigned-tasks":
		$stmt = $db->prepare("SELECT tasks.*, jobs.number, jobs.title as job_title, clients.abbr, task_users.task_id FROM tasks left join jobs on jobs.id = tasks.job_id left join clients on clients.id=jobs.client_id left join task_users on task_users.task_id = tasks.id where task_users.task_id is NULL order by tasks.id DESC");
		$stmt->execute();
		echo json_encode($stmt->fetchAll(PDO::FETCH_OBJ));
		break;
	case "claim-task":
		$stmt = $db->prepare("insert into task_users (task_id, user_id) values (:task_id, :user_id);");
		$stmt->bindParam(":task_id", $_GET['task_id'], PDO::PARAM_INT);
		$stmt->bindParam(":user_id", $_SESSION['id'], PDO::PARAM_INT);
		$stmt->execute();
		echo json_encode(true);
		break;
	case "report-list-jobs":
		$stmt=$db->prepare("select jobs.id, number, abbr, title from jobs left join clients on client_id=jobs.client_id order by abbr ASC, jobs.id DESC");
		$stmt->execute();
		echo json_encode($stmt->fetchAll(PDO::FETCH_OBJ));
		break;
	case "run-report":
		switch($_GET['type']){
			case "atr":
				$stmt=$db->prepare("select code, sum(totaltime) time, job_id, abbr, number, jobs.title from time_card left join times on time_card.id=times.timecard_id left join jobs on jobs.id = job_id left join clients on clients.id=client_id where date <= str_to_date(:date_end, '%M %d, %Y') and date >= str_to_date(:date_start, '%M %d, %Y') group by job_id, code order by abbr, title");
				$stmt->bindParam(":date_start", $_GET['date_start']);
				$stmt->bindParam(":date_end", $_GET['date_end']);
				$stmt->execute();
				echo json_encode($stmt->fetchAll(PDO::FETCH_OBJ));
				break;
			case "tfp":
				$number = $_GET['job'];
				preg_match('/^([0-9A-Za-z]{3})?([0-9]{5,6})$/', $number, $matches);
				$number=$matches[2];
				$stmt=$db->prepare("select code,sum(totaltime) totaltime, sum(cost) cost from times left join jobs on jobs.id=job_id where jobs.number = :number group by code");
				$stmt->bindParam(":number", $number);
				$stmt->execute();
				echo json_encode($stmt->fetchAll(PDO::FETCH_OBJ));
				break;
			default:
				echo json_encode(false);
				break;
		}
		break;
	case "time-card":
		$stmt=$db->prepare("SELECT clients.abbr, jobs.number, jobs.title, times.summary, times.code, times.totaltime from time_card left join times on time_card.id = times.timecard_id left join jobs on jobs.id=times.job_id left join clients on clients.id = jobs.client_id where date = current_date() and user_id=:uid");
		$stmt->bindParam(":uid", $_SESSION['id']);
		$stmt->execute();
		echo json_encode($stmt->fetchAll(PDO::FETCH_OBJ));
		break;
	default:
		echo json_encode(false);
		break;
}
function listTable($table){
	global $db;
	$stmt = $db->prepare("select * from $table order by name");
	$stmt->execute();
	return $stmt->fetchAll(PDO::FETCH_OBJ);
}
function email($from, $to, $description, $title, $job_num, $date, $new_complete){
	if ($new_complete == 'new'){

	}elseif($new_complete == 'complete'){

	}else{
		return;
	}
}
