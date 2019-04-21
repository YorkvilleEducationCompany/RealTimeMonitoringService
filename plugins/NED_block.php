<?php


// BEGIN
// ------------------

debugMessage ("p", "<span style='color:red;'>NED CONTROLLER PLUGIN RUNNING:</span>" );

global $DB, $USER, $CFG;
$moodleContext = get_context_instance(CONTEXT_COURSE, $course->id);

require_once($CFG->dirroot . '/blocks/ned_teacher_tools/lib.php');
require_once($CFG->dirroot . '/course/lib.php');


$supportedmodules = block_ned_teacher_tools_supported_mods();
//debugMessage ("p", var_dump($supportedmodules) ); //ok!



$supportedmodules = block_ned_teacher_tools_supported_mods();

$filtercourses = block_ned_teacher_tools_get_setting_courses();
$userfilters = block_ned_teacher_tools_get_user_filter();

$courseid = $course->id; //Adapter variable from the lib to rtms


/*
debugMessage ("p", "---------" );
debugMessage ("p", var_dump($filtercourses) );
debugMessage ("p", "---------" );
debugMessage ("p", var_dump($userfilters) );
debugMessage ("p", "---------" );

if (is_array($userfilters)) {
    $filtercourses = array_intersect($filtercourses, $userfilters); 
}
$cachecourses = array();

debugMessage ("p", var_dump($cachecourses) );
debugMessage ("p", "---------" );

debugMessage ("p", var_dump($courseid) );
debugMessage ("p", "---------" );


if (in_array($courseid, $filtercourses)) {
    $cachecourses[] = $courseid;
}
*/

/*
if ($courseid == SITEID) {
    if ($teachercourses = block_ned_teacher_tools_teacher_courses($USER->id)) {
        foreach ($teachercourses as $teachercourse) {
            if (empty($filtercourses) && is_array($userfilters)) {
                continue;
            }
            if (in_array($teachercourse->courseid, $filtercourses)) {
                $cachecourses[] = $teachercourse->courseid;
            }
        }
    } else if (is_siteadmin()) {
        $cachecourses = $filtercourses;
    }
}
*/



debugMessage ("p", "----- +++++ ----" );
$cachecourses =  [$course->id];
debugMessage ("p", var_dump($cachecourses) );
debugMessage ("p", "---------" );

foreach ($cachecourses as $filtercourse) {
    if ($course = $DB->get_record('course', array('id' => $filtercourse))) {
        
        $context = context_course::instance($course->id);
        $teachers = get_users_by_capability($context, 'moodle/grade:viewall');

        foreach ($supportedmodules as $supportedmodule => $file) {
            debugMessage ("p", "<span style='color:red;'>CHECKING SUPPORTED MODULE</span>" );
            $summary = block_ned_teacher_tools_count_unmarked_activities($course, 'unmarked', $supportedmodule);
            $numunmarked = $summary['unmarked'];
            $nummarked = $summary['marked'];
            $numunsubmitted = $summary['unsubmitted'];
            $numsaved = $summary['saved'];

            $rec = new stdClass();
            $rec->courseid = $course->id;
            $rec->modname = $supportedmodule;
            $rec->unmarked = $numunmarked;
            $rec->marked = $nummarked;
            $rec->unsubmitted = $numunsubmitted;
            $rec->saved = $numsaved;
            $rec->timecreated = time();
            $rec->expired = 0;



            if ($modcache = $DB->get_record('block_ned_teacher_tools_cach',
                array('courseid' => $course->id, 'modname' => $supportedmodule, 'userid' => 0))) {
                $rec->id = $modcache->id;
                debugMessage ("p", "<span style='color:red;'>UPDATING DATABASE 1:</span>" );
                debugMessage ("p", var_dump($rec) );
                $DB->update_record('block_ned_teacher_tools_cach', $rec);
            } else {
                debugMessage ("p", "<span style='color:red;'>UPDATING DATABASE 1:</span>" );
                debugMessage ("p", var_dump($rec) );
                $DB->insert_record('block_ned_teacher_tools_cach', $rec);
            }

            // Teachers in a group.
            if ($teachers) {
                foreach ($teachers as $teacher) {
                    if ($groupstudents = block_ned_teacher_tools_mygroup_members($course->id, $teacher->id)) {
                        $summary = block_ned_teacher_tools_count_unmarked_activities($course, 'unmarked', $supportedmodule, $teacher->id);
                        $numunmarked = $summary['unmarked'];
                        $nummarked = $summary['marked'];
                        $numunsubmitted = $summary['unsubmitted'];
                        $numsaved = $summary['saved'];

                        $rec = new stdClass();
                        $rec->courseid = $course->id;
                        $rec->modname = $supportedmodule;
                        $rec->unmarked = $numunmarked;
                        $rec->marked = $nummarked;
                        $rec->unsubmitted = $numunsubmitted;
                        $rec->saved = $numsaved;
                        $rec->timecreated = time();
                        $rec->expired = 0;
                        $rec->userid = $teacher->id;

                        if ($modcache = $DB->get_record('block_ned_teacher_tools_cach',
                            array('courseid' => $course->id, 'modname' => $supportedmodule, 'userid' => $teacher->id))) {
                            $rec->id = $modcache->id;
                            debugMessage ("p", "<span style='color:red;'>UPDATING DATABASE 2:</span>" );
                            debugMessage ("p", var_dump($rec) );
                            $DB->update_record('block_ned_teacher_tools_cach', $rec);
                        } else {
                            debugMessage ("p", "<span style='color:red;'>INSERTING DATABASE 2:</span>" );
                            debugMessage ("p", var_dump($rec) );
                            $DB->insert_record('block_ned_teacher_tools_cach', $rec);
                        }
                    }
                }
            }

          
        }

    }


}

return true;

