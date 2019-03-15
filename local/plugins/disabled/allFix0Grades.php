<?php 



// * * * * * php -f /var/www/courses.yorkvilleu.ca/www/local/overdue/all.php?key=FF77aazzFF8ajjejejgvyzlkqjweFDDFF77aazzFF8ajjejejgvyzlkqjweFDD >/dev/null 2>&1
// * * * * * wget -O - https://courses.yorkvilleu.ca/local/overdue/all.php?key=FF77aazzFF8ajjejejgvyzlkqjweFDDFF77aazzFF8ajjejejgvyzlkqjweFDD >/dev/null 2>&1

// USEFULL STUFF
// SELECT * FROM mdl_grade_grades WHERE overridden >= 1535760000 LIMIT 1000

// FOR DUPLICATE GRADES
// SELECT * FROM mdl_grade_grades WHERE itemid=104859 AND userid=11289


if($_GET['key'] != "FF77aazzFF8ajjejejgvyzlkqjweFDDFF77aazzFF8ajjejejgvyzlkqjweFDD"){
	die();
}



require("../../config.php");

$debug = false;
if($debug){
	@error_reporting(1023);  // NOT FOR PRODUCTION SERVERS!
	@ini_set('display_errors', '1'); // NOT FOR PRODUCTION SERVERS!
	$CFG->debug = 32767;         // DEBUG_DEVELOPER // NOT FOR PRODUCTION SERVERS!
	// // for Moodle 2.0 - 2.2, use:  $CFG->debug = 38911;
	$CFG->debugdisplay = true;   // NOT FOR PRODUCTION SERVERS!
	ini_set('error_reporting', E_ALL);
	ini_set('display_errors', 'On');  //On or Off
}

require_once $CFG->libdir.'/gradelib.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->dirroot.'/grade/report/lib.php';

require_once $CFG->dirroot.'/mod/assign/externallib.php'; // New attempt to save external grades -- ONLY works for assignments darnit...
$externalGrade = new mod_assign_external;

require_once $CFG->dirroot.'/mod/quiz/lib.php'; // Maybe we can pull directly from this lib?

global $USER, $DB;




// DATABASE CLEARING FOR TEST PURPOSES ONLY
//$DB->execute('DELETE FROM mdl_grade_grades'); var_dump("cleared grades"); die(); 

$refillQue = "empty";
$ques = $DB->get_records_sql('SELECT id FROM mdl_x_overdueque LIMIT 1', array(1));
foreach($ques as $que){
	$refillQue = "full";
}
echo "<h1>Overdue que status: ".$refillQue."</h1>";

if($refillQue == "empty"){

	echo "<h2>Refilling Que with ALL Courses!</h2>";
	$courses = $DB->get_records_sql('SELECT id FROM mdl_course', array(1));
	foreach($courses as $course){
		
		$theCourse = new stdClass();
		$theCourse->courseid = $course->id;
		$theCourse->timeAdded = time();
		//var_dump($theCourse);
		$lastinsertid = $DB->insert_record('x_overdueque', $theCourse, false);
	}

}else{
	echo "<h2>Que has data, process!</h2>";
}



// CHECK FOR LOCKS
$queLocks = $DB->get_records_sql('SELECT * FROM mdl_x_overdueque_lock', array(1));
foreach($queLocks as $queLock){
	var_dump("Previous Que Still Running. Exit.");
	var_dump($queLock);
	
	var_dump("Lock Duration: ". (time() - $queLock->time));
	if((time() - $queLock->time) >= 10){ //Five Minutes = 300s
		var_dump("Que has been running for some time and is perhaps stuck. Removing lock");
		$DB->delete_records_select('x_overdueque_lock WHERE id='.$queLock->id);
	}
	die();
}

// CREATE NEW LOCK
$theLock = new stdClass();
$theLock->queChannel = 1;
$theLock->status = "Running";
$theLock->time = time();
$theLockId = $DB->insert_record('x_overdueque_lock', $theLock, true);
var_dump("LOCKING CHANNEL!");
var_dump($theLockId);

// BEGIN OVERDUE SCANNING
$quedCourses = $DB->get_records_sql('SELECT * FROM mdl_x_overdueque LIMIT 50', array(1));
foreach($quedCourses as $theCourse){

	$courses = $DB->get_records_sql('SELECT id FROM mdl_course WHERE id='.$theCourse->courseid, array(1));
	
foreach($courses as $course){
		
		$triggerRegrade = false;
		
		echo '<h1>Processing courseId:'.$course->id.'</h1>';
		echo '<hr />';

		$moodleContext = get_context_instance(CONTEXT_COURSE, $course->id);

		$students = [];
		$studentEnrolments = $DB->get_records_sql('SELECT userid,contextid FROM mdl_role_assignments WHERE contextid = '.$moodleContext->id .'', array(1));
		foreach($studentEnrolments as $studentEnrolment){
			array_push($students, $studentEnrolment->userid);
		}

		$gradeItems = $DB->get_records_sql('SELECT id,courseid,itemmodule,iteminstance FROM mdl_grade_items WHERE courseid = '.$course->id, array(1));	
		foreach($gradeItems as $gradeItem){
			
			
			// ===================
			// REGULAR ASSIGNMENT
			// ===================
			
				if($gradeItem->iteminstance){
				
					$assignments = $DB->get_records_sql('SELECT * FROM mdl_assign WHERE id = '.$gradeItem->iteminstance, array(1));
					foreach($assignments as $assignment){
						
						
						$processBuggedAssignment = array();

						echo "<h3>SCANNING ASSIGNMENT: ".$assignment->duedate."</h3>";
											
							var_dump($assignment);
						
							echo "<h4>BUGGED ASSIGNMENT, CHECK STUDENTS:</h4>";
							foreach($students as $student){
								
								$studentAssignmentFound = false;
								//var_dump("STUDENT ID: ".$student);
								
								$grades = $DB->get_records_sql('SELECT * FROM mdl_grade_grades WHERE (itemid='.$gradeItem->id.' AND userid='.$student . ' AND finalgrade >= 0) OR (itemid='.$gradeItem->id.' AND userid='.$student . ' AND finalgrade = "0.00000") OR (itemid='.$gradeItem->id.' AND userid='.$student . ' AND finalgrade IS NULL) ', array(1));
								foreach($grades as $grade){
									//echo "<h5>ASSIGNMENT GRADE FOUND: ".$grade->finalgrade."</h5>";
									//var_dump($grade);
									
									if((int)$grade->finalgrade == 0){
										//echo "<h6 style='color:red'>CLEAR THIS RECORD</h6>";
										array_push($processBuggedAssignment,0);
									}
									
									if((int)$grade->finalgrade >= 1){
										//echo "<h6 style='color:green'>SAVE THIS RECORD</h6>";
										array_push($processBuggedAssignment,1);
									}
									
								}
								
							}
						
						
						
						//var_dump($processBuggedAssignment);
						$anyGradedItemsFound = 0;
						foreach($processBuggedAssignment as $check){

							echo "<p>CHECK: " . $check ."</p>";
						
							if($check == 1){
								$anyGradedItemsFound = 1;
							}
							
						
						}
						
						
						if($anyGradedItemsFound == 1){
							echo "<h6 style='color:green'>KEEP THIS RECORD</h6>";
						}
						if($anyGradedItemsFound == 0){
							echo "<h6 style='color:red'>BUGGED 0's DETECTED!</h6>";
							
							foreach($students as $student){
								
								
								$grades = $DB->get_records_sql('SELECT * FROM mdl_grade_grades WHERE (itemid='.$gradeItem->id.' AND userid='.$student . ' AND finalgrade >= 0) OR (itemid='.$gradeItem->id.' AND userid='.$student . ' AND finalgrade = "0.00000") OR (itemid='.$gradeItem->id.' AND userid='.$student . ' AND finalgrade IS NULL) ', array(1));
								foreach($grades as $grade){
									
									if( (int)$grade->timemodified >= 1536710400){
								
										echo "<h6>ACCIDENTAL ZERO -- CLEARING RECORD BECAUSE MODIFIED TIME IS: ".(int)$grade->timemodified."</h6>";
										var_dump('DELETE FROM mdl_grade_grades WHERE itemid='.$gradeItem->id.' AND userid='.$student);
										
										$DB->execute('DELETE FROM mdl_grade_grades WHERE itemid='.$gradeItem->id.' AND userid='.$student);
										$triggerRegrade = true;
									
									}
										
									
								}
								
							}
							
						}
						
						
						
						// FORCE REGRADING!
						if($triggerRegrade){
							var_dump("FORCING REGRADE");
							$grade_item = grade_item::fetch(array('id'=>$gradeItem->id, 'courseid'=>$course->id));
							$grade_item->force_regrading();
							var_dump("REGRADE COMPLETE");
						}
						
					
					}				
				}
			
			// ===================
			// QUIZZES
			// ===================
			
			// USE THIS TO CLEAR DATA -- TESTING ONLY!
			//$DB->execute('DELETE FROM mdl_grade_grades WHERE itemid='.$gradeItem->id.' AND userid='.$student);
			/*
			var_dump($gradeItem);
			if($gradeItem->itemmodule=="quiz"){
				
				$quizes = $DB->get_records_sql('SELECT * FROM mdl_quiz WHERE id = '.$gradeItem->iteminstance, array(1));
				foreach($quizes as $quiz){
					
					echo "<h3>QUIZ:</h3>";
					var_dump($quiz);
					
					echo "<h4>Checking Student Quiz:</h4>";
					foreach($students as $student){
						
						$studentQuizFound = false;
						var_dump("STUDENT ID: ".$student);
						
						//var_dump('SELECT * FROM mdl_grade_grades WHERE itemid='.$gradeItem->id.' AND userid='.$student);
						$grades = $DB->get_records_sql('SELECT * FROM mdl_grade_grades WHERE  itemid='.$gradeItem->id.' AND userid='.$student, array(1));
						$i = 0;
						foreach($grades as $grade){
							$i++;
							echo "<h4>QUIZ FOUND: ".$i."</h4>";
							
							var_dump($grade);
						}
						
						
					}
					
				}
			
			}
			*/
				
			
			echo "<hr />NEXT GRADE ITEM<hr /><hr /><hr /><hr /><hr /><hr /><hr /><hr /><hr /><hr /><hr /><hr /><hr />";
		}
		
		
		var_dump("clearing qued course");
		$DB->execute('DELETE FROM mdl_x_overdueque WHERE courseid='.$course->id);
		
	}
	

	
	
}


var_dump("clearing qued course:".$theLockId);
$DB->delete_records_select('x_overdueque_lock WHERE id='.$theLockId);

echo '<hr />';
echo '<h1 style="color:green;">COMPLETED SUCCESSFULLY</h1>';



// CHANGE THE OVERRIDDEN TO 0
$overriddens = $DB->get_records_sql('SELECT * FROM mdl_grade_grades WHERE overridden >= 1535760000 LIMIT 1000', array(1)); // Only modify records forward from September 2018
foreach($overriddens as $overridden){
	var_dump($overridden);
	var_dump('UPDATE mdl_grade_grades SET overridden = 0 WHERE id = '.$overridden->id);
	$DB->execute('UPDATE mdl_grade_grades SET overridden = 0 WHERE id = '.$overridden->id);
}



?>
