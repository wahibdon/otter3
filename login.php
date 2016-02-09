<?php
require_once('api/dbconn.inc');
		$current_year = date('y');
		$latest_job_num = $db->prepare('SELECT max(number) from jobs');
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