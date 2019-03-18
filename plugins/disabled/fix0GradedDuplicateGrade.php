<?php 

// * * * * * php -f /var/www/courses.yorkvilleu.ca/www/local/overdue/all.php?key=FF77aazzFF8ajjejejgvyzlkqjweFDDFF77aazzFF8ajjejejgvyzlkqjweFDD >/dev/null 2>&1
// * * * * * wget -O - https://courses.yorkvilleu.ca/local/overdue/all.php?key=FF77aazzFF8ajjejejgvyzlkqjweFDDFF77aazzFF8ajjejejgvyzlkqjweFDD >/dev/null 2>&1

/*

https://courses.yorkvilleu.ca/grade/report/grader/index.php?id=9568
https://courses.yorkvilleu.ca/grade/report/grader/index.php?id=9497
https://courses.yorkvilleu.ca/grade/report/grader/index.php?id=9566
https://courses.yorkvilleu.ca/grade/report/grader/index.php?id=9514
https://courses.yorkvilleu.ca/grade/report/grader/index.php?id=9658
https://courses.yorkvilleu.ca/grade/report/grader/index.php?id=9505
https://courses.yorkvilleu.ca/grade/report/grader/index.php?id=9505
https://courses.yorkvilleu.ca/grade/report/grader/index.php?id=9503
https://courses.yorkvilleu.ca/grade/report/grader/index.php?id=9666
 
 
https://courses.yorkvilleu.ca/grade/report/grader/index.php?id=9478 - this page has zeros for Case Analysis 1  regardless of submission status
https://courses.yorkvilleu.ca/grade/report/grader/index.php?id=9563 - this page has zeros for Case Analysis 2 regardless of submission status

*/


if($_GET['key'] != "FF77aazzFF8ajjejejgvyzlkqjweFDDFF77aazzFF8ajjejejgvyzlkqjweFDD"){
	die();
}

require("../../config.php");
require_once $CFG->libdir.'/gradelib.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->dirroot.'/grade/report/lib.php';

require_once $CFG->dirroot.'/mod/assign/externallib.php'; // New attempt to save external grades -- ONLY works for assignments darnit...
$externalGrade = new mod_assign_external;

require_once $CFG->dirroot.'/mod/quiz/lib.php'; // Maybe we can pull directly from this lib?

global $USER, $DB;


// BEGIN OVERDUE SCANNING

	$courses = $DB->get_records_sql('SELECT id FROM mdl_course WHERE id='.$_GET['courseid'], array(1));
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
						echo "<h6 style='color:red'>CLEARING BUGGED 0's</h6>";
						
						foreach($students as $student){
							
							
							$grades = $DB->get_records_sql('SELECT * FROM mdl_grade_grades WHERE (itemid='.$gradeItem->id.' AND userid='.$student . ' AND finalgrade >= 0) OR (itemid='.$gradeItem->id.' AND userid='.$student . ' AND finalgrade = "0.00000") OR (itemid='.$gradeItem->id.' AND userid='.$student . ' AND finalgrade IS NULL) ', array(1));
							foreach($grades as $grade){
								var_dump($grade);
								if($grade->timemodified >= 1536710400){
							
									echo "<h6>ACCIDENTAL ZERO -- CLEARING RECORD</h6>";
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
						$grade_item = grade_item::fetch(array('id'=>$gradeItem->id, 'courseid'=>$_GET['courseid']));
						$grade_item->force_regrading();
						var_dump("REGRADE COMPLETE");
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
		
		
	}
	

?>
