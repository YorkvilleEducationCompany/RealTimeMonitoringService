<?php

// Load RTMS
// ---------

var_dump("FOUND");
die();

require_once("config.php");

if($_GET['key'] != $KEY){
	echo "invalid key";
	die();
}




// Start Moodle & Libraries
// ------------------------

require_once("../../config.php");
require_once $CFG->libdir.'/gradelib.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->dirroot.'/grade/report/lib.php';

require_once $CFG->dirroot.'/mod/assign/externallib.php'; // New attempt to save external grades -- ONLY works for assignments darnit...
$externalGrade = new mod_assign_external;

require_once $CFG->dirroot.'/mod/quiz/lib.php'; // Maybe we can pull directly from this lib?

global $USER, $DB;


// RTMS Que Check & Build
// ----------------------

$refillQue = "empty";
$ques = $DB->get_records_sql('SELECT id FROM mdl_RTMS_courseQue LIMIT 1', array(1));
foreach($ques as $que){
	$refillQue = "full";
}
debugMessage ("h1", "Overdue que status: ".$refillQue);

if($refillQue == "empty"){

	debugMessage ("h2", "Refilling Que with ALL Courses!");
	$courses = $DB->get_records_sql('SELECT id FROM mdl_course', array(1));
	foreach($courses as $course){

		$theCourse = new stdClass();
		$theCourse->courseid = $course->id;
		$theCourse->timeAdded = time();

		$lastinsertid = $DB->insert_record('RTMS_courseQue', $theCourse, false);
	}

}else{
	debugMessage("h2", "Que has data, process!");
}

// CHECK FOR LOCKS, CLEAR OR DIE
// -----------------------------
$queLocks = $DB->get_records_sql('SELECT * FROM RTMS_courseQueLocks', array(1));
foreach($queLocks as $queLock){
	debugMessage ("h1", "Previous Que Still Running. Exit.");
	debugMessage ("p", var_dump($queLock) );
	debugMessage("h2", "Lock Duration: ". (time() - $queLock->time));

	if((time() - $queLock->time) >= 10){ //Five Minutes = 300s
		debugMessage("h2", "Que has been running for some time and is perhaps stuck. Removing lock");
		$DB->execute('DELETE FROM RTMS_courseQueLocks WHERE id='.$queLock->id);
	}
	die();
}

// CREATE NEW LOCK
// ---------------
$theLock = new stdClass();
$theLock->queChannel = 1;
$theLock->status = "Running";
$theLock->time = time();
$theLockId = $DB->insert_record('RTMS_courseQueLocks', $theLock, true);
debugMessage ("p","LOCKING CHANNEL!");
debugMessage ("p",$theLockId);

// BEGIN OVERDUE SCANNING
$quedCourses = $DB->get_records_sql('SELECT * FROM RTMS_courseQue LIMIT 50', array(1));
debugMessage ("p","Processing: " );
debugMessage ("p", count($quedCourses) );
debugMessage ("p","===========================");


foreach($quedCourses as $theCourse){

	$courses = $DB->get_records_sql('SELECT id FROM mdl_course WHERE id='.$theCourse->courseid, array(1));
	if($courses){
		debugMessage ("p","COURSE FOUND, YOU MAY PROCEED");
	}else{
		debugMessage ("p","NO COURSE WAS FOUND -- THE COURSE WAS LIKELY DELETED BETWEEN REAL TIME CYCLE -- REMOVING FROM QUE");
		$DB->execute('DELETE FROM RTMS_courseQue WHERE courseid='.$theCourse->courseid);
	}

	foreach($courses as $course){

		foreach (glob("plugins/enabled/*.php") as $filename){
		    include $filename;
		}

		debugMessage ("p","clearing qued course");
		$DB->execute('DELETE FROM RTMS_courseQue WHERE courseid='.$course->id);

	}

}


debugMessage ("p","clearing qued course:".$theLockId);
$DB->execute('DELETE FROM mdl_RTMS_courseQueLocks WHERE id='.$theLockId);

debugMessage ("p", '<hr />');
debugMessage ("p", '<h1 style="color:green;">COMPLETED SUCCESSFULLY</h1>');

echo "complete";


?>
