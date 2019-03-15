<?php

$triggerRegrade = false;

debugMessage ("p", 'Processing courseId:'.$course->id);
debugMessage ("p", '<hr />');

$moodleContext = get_context_instance(CONTEXT_COURSE, $course->id);
debugMessage ("p","Moodle context:");
debugMessage ("p",$moodleContext);

debugMessage ("p","building students list");
debugMessage ("p",'SELECT userid,contextid FROM mdl_role_assignments WHERE contextid = '.$moodleContext->id);
$students = [];
$studentEnrolments = $DB->get_records_sql('SELECT userid,contextid FROM mdl_role_assignments WHERE contextid = '.$moodleContext->id .'', array(1));
foreach($studentEnrolments as $studentEnrolment){
    //debugMessage ("p", var_dump($studentEnrolment) );
    array_push($students, $studentEnrolment->userid);
}
debugMessage ("p", $students );

$gradeItems = $DB->get_records_sql('SELECT id,courseid,itemmodule,iteminstance FROM mdl_grade_items WHERE courseid = '.$course->id, array(1));
foreach($gradeItems as $gradeItem){

    debugMessage ("p",'gradeItem:');
    debugMessage ("p", $gradeItem );

    // USE THIS TO CLEAR DATA -- TESTING ONLY!
    //$DB->execute('DELETE FROM mdl_grade_grades WHERE itemid='.$gradeItem->id.' AND userid='.$student);

    if($gradeItem->itemmodule=="quiz"){

        $quizes = $DB->get_records_sql('SELECT * FROM mdl_quiz WHERE id = '.$gradeItem->iteminstance, array(1));
        foreach($quizes as $quiz){

            debugMessage ("p", "QUIZ:");
            //debugMessage ("p", var_dump($quiz) );

            if( (int)$quiz->timeclose > 0 && time() - (int)$quiz->timeclose >= 0 ){
                debugMessage ("p", "OVERDUE QUIZ DETECTED");
            }else{
                debugMessage ("p", "Not overdue yet");
            }


            debugMessage ("p", "Checking Student Quiz:");
            foreach($students as $student){

                $studentQuizFound = false;
                debugMessage ("p","STUDENT ID: ".$student);

                $grades = $DB->get_records_sql('SELECT * FROM mdl_grade_grades WHERE (itemid='.$gradeItem->id.' AND userid='.$student . ' AND finalgrade >= 0) OR (itemid='.$gradeItem->id.' AND userid='.$student . ' AND finalgrade = "0.0000") OR (itemid='.$gradeItem->id.' AND userid='.$student . ' AND finalgrade IS NULL) ', array(1));
                foreach($grades as $grade){
                    debugMessage ("p", "QUIZ FOUND:");

                    debugMessage ("p", $grade);
                    $studentQuizFound = true;
                    // Additional check is required for some reason, a grade can be inserted as null?
                    if(is_null($grade->finalgrade)){
                        debugMessage ("p","WAS NULL");
                        $DB->execute('DELETE FROM mdl_grade_grades WHERE id='.$grade->id);
                        $studentQuizFound = false;
                    }
                }

                if($studentQuizFound == false){

                    // Make sure it's actually past due date
                    if( (int)$quiz->timeclose > 0 && time() - (int)$quiz->timeclose >= 0 ){

                        debugMessage ("p","QUIZ PAST DUE DATE, GIVE ZERO: ".$quiz->id." with student: ".$student);

                        $theGrade = new stdClass();
                        $theGrade->itemid = $gradeItem->id;
                        $theGrade->userid = $student;
                        $theGrade->usermodified = 2;
                        $theGrade->finalgrade = floatval(0.00);
                        $theGrade->aggregationstatus = "novalue";
                        $theGrade->overridden   = 1;
                        $theGrade->timemodified   = time();


                        $lastinsertid = $DB->insert_record('grade_grades', $theGrade, false);


                        // UPDATE THE GRADE -- hopefully the API will simply update the course total?
                        //$quiz->grade = 0;
                        //quiz_grade_item_update($quiz, $student, 0);

                        //grade_update('mod/quiz', $course->id, 'mod', 'quiz', $gradeItem->iteminstance, 0, 0, 0);
                        //$externalGrade->save_grade("62847",$student,0,1,false,"",true,array(),array()); //ONLY WORKS FOR ASSIGNMENTS

                        $triggerRegrade = true;

                    }

                }



            }

        }

    }

    // FORCE REGRADING!
    if($triggerRegrade == true){
        $grade_item = grade_item::fetch(array('id'=>$gradeItem->id, 'courseid'=>$course->id));
        $grade_item->force_regrading();
        debugMessage ("p","REGRADE COMPLETE");
    }


}
