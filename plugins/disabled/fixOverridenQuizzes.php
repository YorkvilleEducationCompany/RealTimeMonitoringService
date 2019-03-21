<?php 


	$triggerRegrade = false;
	
	$moodleContext = context_course::instance($course->id);

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

		/*

		if($gradeItem->iteminstance === NULL){
			return;
		}
	
		$assignments = $DB->get_records_sql('SELECT * FROM mdl_assign WHERE id = '.$gradeItem->iteminstance, array(1));
		foreach($assignments as $assignment){
			
			
			$processBuggedAssignment = array();

			debugMessage("p", "SCANNING ASSIGNMENT: ".$assignment->duedate);
								
			debugMessage("p",$assignment);
		
			debugMessage("p", "BUGGED ASSIGNMENT, CHECK STUDENTS:");
			foreach($students as $student){
				
				$studentAssignmentFound = false;
				debugMessage("p", "STUDENT ID: ".$student);
				
				$grades = $DB->get_records_sql('SELECT * FROM mdl_grade_grades WHERE (itemid='.$gradeItem->id.' AND userid='.$student . ' AND finalgrade >= 0) OR (itemid='.$gradeItem->id.' AND userid='.$student . ' AND finalgrade = "0.00000") OR (itemid='.$gradeItem->id.' AND userid='.$student . ' AND finalgrade IS NULL) ', array(1));
				foreach($grades as $grade){
					debugMessage("p", "ASSIGNMENT GRADE FOUND: ".$grade->finalgrade);
					debugMessage("p", $grade);
					
					if((int)$grade->finalgrade == 0){
						debugMessage("p","<h6 style='color:red'>CLEAR THIS RECORD</h6>");
						array_push($processBuggedAssignment,0);
					}
					
					if((int)$grade->finalgrade >= 1){
						debugMessage("p", "<h6 style='color:green'>SAVE THIS RECORD</h6>");
						array_push($processBuggedAssignment,1);
					}
					
				}
				
			}
			
			
			
			//var_dump($processBuggedAssignment);
			$anyGradedItemsFound = 0;
			foreach($processBuggedAssignment as $check){

				debugMessage("p", "CHECK: " . $check);
			
				if($check == 1){
					$anyGradedItemsFound = 1;
				}
				
			
			}
			
			
			if($anyGradedItemsFound == 1){
				debugMessage("p", "<h6 style='color:green'>KEEP THIS RECORD</h6>");
			}
			if($anyGradedItemsFound == 0){
				debugMessage("p", "<h6 style='color:red'>CLEARING BUGGED 0's</h6>");
				
				foreach($students as $student){
					
					
					$grades = $DB->get_records_sql('SELECT * FROM mdl_grade_grades WHERE (itemid='.$gradeItem->id.' AND userid='.$student . ' AND finalgrade >= 0) OR (itemid='.$gradeItem->id.' AND userid='.$student . ' AND finalgrade = "0.00000") OR (itemid='.$gradeItem->id.' AND userid='.$student . ' AND finalgrade IS NULL) ', array(1));
					foreach($grades as $grade){
						debugMessage("p", $grade);
						if($grade->timemodified >= 1536710400){
					
							debugMessage("p", "<h6>ACCIDENTAL ZERO -- CLEARING RECORD</h6>");
							debugMessage("p", 'DELETE FROM mdl_grade_grades WHERE itemid='.$gradeItem->id.' AND userid='.$student);
							
							$DB->execute('DELETE FROM mdl_grade_grades WHERE itemid='.$gradeItem->id.' AND userid='.$student);
							$triggerRegrade = true;
							
						}
						
					}
					
				}
				
			}
			
			
			
			// FORCE REGRADING!
			if($triggerRegrade){
				debugMessage("p","FORCING REGRADE");
				$grade_item = grade_item::fetch(array('id'=>$gradeItem->id, 'courseid'=>$_GET['courseid']));
				$grade_item->force_regrading();
				debugMessage("p","REGRADE COMPLETE");
			}
			
			
		
		}	
		*/

		
		// ===================
		// QUIZZES
		// ===================
		
		// USE THIS TO CLEAR DATA -- TESTING ONLY!
		//$DB->execute('DELETE FROM mdl_grade_grades WHERE itemid='.$gradeItem->id.' AND userid='.$student);
		
		//debugMessage("p", $gradeItem);

		if($gradeItem->itemmodule=="quiz"){
			
			$quizes = $DB->get_records_sql('SELECT * FROM mdl_quiz WHERE id = '.$gradeItem->iteminstance, array(1));
			foreach($quizes as $quiz){
				
				debugMessage("h1", "QUIZ:");
				//debugMessage("p", $quiz);

				// Here's the fix:
				// SELECT * FROM mdl_grade_grades WHERE rawgrade != finalgrade AND overridden = 1

				foreach($students as $student){

					debugMessage("p", 'SELECT * FROM mdl_grade_grades WHERE  rawgrade != finalgrade AND overridden = 1 AND userid='.$student);
					debugMessage("p", 'SELECT * FROM mdl_grade_grades WHERE itemid='.$gradeItem->id.' AND userid='.$student);

					$grades = $DB->get_records_sql('SELECT * FROM mdl_grade_grades WHERE  rawgrade != finalgrade AND overridden = 1 AND userid='.$student, array(1));
						foreach($grades as $grade){
						debugMessage("h1", "ERROR FOUND -- CORRECTING");
						$DB->execute('UPDATE mdl_grade_grades SET finalgrade='.$grade->rawgrade.' WHERE itemid='.$gradeItem->id.' AND userid='.$student);
					}	

				}

			
				
			}
		
		}
			
	}
	

?>
