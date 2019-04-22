<?php

// Load RTMS
// ---------

require_once("../../config.php");

//  Install as a Cron Job and run this as fast as possible
//  There are internal braking methods to prevent a CPU overload
//  To acheive "real time" performance, we try to run this file as fast as possible, at all times, forever!
//
//  # Moodle Real Time Scanner, must run every minute
//  */1 * * * * wget -O - https://yourwebsite.com/local/RealTimeMonitoringService/?key=YOUR_WEB_KEY > /dev/null 2>&1


$RtmsKey = get_config('local_rtms', 'key');
$RtmsAmountToProcess = get_config('local_rtms', 'amountToProcess');

$RtmsPlugin_plugin_NED_block = get_config('local_rtms', 'plugin_NED_block');
$RtmsPlugin_plugin_YU_fixOverridenQuizzesk = get_config('local_rtms', 'plugin_NED_block');
$RtmsPlugin_plugin_YU_overdueAssignmentsToZero = get_config('local_rtms', 'plugin_NED_block');

echo "rtms processing: ".$RtmsAmountToProcess ;


if($_GET['key'] != $RtmsKey){
	echo "<h1>invalid key</h1>";
	die();
}

function debugMessage($type, $message){

	if($_GET['debugMessages'] == "true"){

		if( is_string($message) ){
			echo "<$type>$message</$type>";
		}

		if( is_object($message) ){
			echo "<pre>";
			var_dump($message);
			echo "</pre>";
		}


	}

	return false;

}








$startTime = microtime(true);


// Start Moodle & Libraries
// ------------------------

//require_once $CFG->libdir.'/gradelib.php';
//require_once $CFG->dirroot.'/grade/lib.php';
//require_once $CFG->dirroot.'/grade/report/lib.php';

//require_once $CFG->dirroot.'/mod/assign/externallib.php'; // New attempt to save external grades -- ONLY works for assignments darnit...
//$externalGrade = new mod_assign_external;

//require_once $CFG->dirroot.'/mod/quiz/lib.php'; // Maybe we can pull directly from this lib?

//global $USER, $DB;


// RTMS Que Check & Build
// ----------------------

$refillQue = "empty";
$ques = $DB->get_records_sql('SELECT id FROM mdl_rtms_courseque LIMIT 1', array(1));
foreach($ques as $que){
	$refillQue = "full";
}
debugMessage ("h1", "Overdue que status: ".$refillQue);

if($refillQue == "empty"){

	debugMessage ("h2", "Refilling Que with ALL Courses!");
	$courses = $DB->get_records_sql('SELECT id FROM mdl_course', array(1));
	foreach($courses as $course){

		$theCourse = new stdClass();
		$theCourse->quechannel = 1;
		$theCourse->courseid = $course->id;
		$theCourse->timeadded = time();

		$lastinsertid = $DB->insert_record('rtms_courseque', $theCourse, false);
	}

}else{
	debugMessage("h2", "Que has data, process!");
}

// CHECK FOR LOCKS, CLEAR OR DIE
// -----------------------------
$queLocks = $DB->get_records_sql('SELECT * FROM mdl_rtms_coursequelocks', array(1));
foreach($queLocks as $queLock){
	debugMessage ("h1", "Previous Que Still Running. Exit.");
	debugMessage ("p", var_dump($queLock) );
	debugMessage("h2", "Lock Duration: ". (time() - $queLock->time));

	if((time() - $queLock->time) >= 10){ //Five Minutes = 300s
		debugMessage("h2", "Que has been running for some time and is perhaps stuck. Removing lock");
		$DB->execute('DELETE FROM mdl_rtms_coursequelocks WHERE id='.$queLock->id);
	}
	die();
}

// CREATE NEW LOCK
// ---------------
$theLock = new stdClass();
$theLock->quechannel = 1;
$theLock->status = "Running";
$theLock->time = time();
$theLockId = $DB->insert_record('rtms_coursequelocks', $theLock, true);
debugMessage ("p","LOCKING CHANNEL!");
debugMessage ("p",$theLockId);

// BEGIN OVERDUE SCANNING
$quedCourses = $DB->get_records_sql('SELECT * FROM mdl_rtms_courseque LIMIT '.$RtmsAmountToProcess, array(1));
debugMessage ("p","Processing: " );
debugMessage ("p", count($quedCourses) );
debugMessage ("p","===========================");


foreach($quedCourses as $theCourse){

	$courses = $DB->get_records_sql('SELECT id FROM mdl_course WHERE id='.$theCourse->courseid, array(1));
	if($courses){
		debugMessage ("p","COURSE FOUND, YOU MAY PROCEED");
	}else{
		debugMessage ("p","NO COURSE WAS FOUND -- THE COURSE WAS LIKELY DELETED BETWEEN REAL TIME CYCLE -- REMOVING FROM QUE");
		$DB->execute('DELETE FROM mdl_rtms_courseque WHERE courseid='.$theCourse->courseid);
	}

	foreach($courses as $course){

		// no longer using the /plugins/enabled structure, let us run from a database
		/*
		foreach (glob("plugins/enabled/*.php") as $filename){
		    include $filename;
		}
		*/

		if($RtmsPlugin_plugin_NED_block == "1"){
			debugMessage ("p","RUNNING NED BLOCK");
			require_once($_SERVER['DOCUMENT_ROOT']."/blocks/ned_teacher_tools/rtms/refresh_all_data.php");
		}
		if($RtmsPlugin_plugin_YU_overdueAssignmentsToZero == "1"){
			debugMessage ("p","YU Overdue Assignments");
			require_once("plugins/YU_overdueAssignmentsToZero.php");
		}

		

		debugMessage ("p","clearing qued course");
		$DB->execute('DELETE FROM mdl_rtms_courseque WHERE courseid='.$course->id);

	}

}


debugMessage ("p","clearing qued course:".$theLockId);
$DB->execute('DELETE FROM mdl_rtms_coursequelocks WHERE id='.$theLockId);

debugMessage ("p", '<hr />');
debugMessage ("p", '<h1 style="color:green;">COMPLETED '.$RtmsAmountToProcess.' JOBS SUCCESSFULLY: '.(microtime(true) - $startTime).' seconds</h1>');

$runtime = "".round( (microtime(true) - $startTime), 5);

echo "completed $RtmsAmountToProcess jobs in: ".$runtime." seconds";


// ADD LOG
// ---------------
$rtmsLog = new stdClass();
$rtmsLog->time = time();
$rtmsLog->runtime = $runtime;
$rtmsLog->amount = $RtmsAmountToProcess;

var_dump($rtmsLog);
//$lastinsertid = $DB->insert_record('rtms_logs', $rtmsLog, false);
$DB->execute("INSERT INTO {rtms_logs} (time, runtime, amount) VALUES ($rtmsLog->time, $rtmsLog->runtime, $rtmsLog->amount)");



?>
