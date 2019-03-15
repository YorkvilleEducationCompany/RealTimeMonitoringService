<?php 

		
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
		// QUIZZES
		// ===================
		
		// USE THIS TO CLEAR DATA -- TESTING ONLY!
		//$DB->execute('DELETE FROM mdl_grade_grades WHERE itemid='.$gradeItem->id.' AND userid='.$student);
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

	}
				
?>
