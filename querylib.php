<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *  Library with functions that make queries to the database to extract data. 
 *
 * @package     core_privacy
 * @category    local plugin
 * @copyright   2021 Jose Julio Sanchez <jose.sanchez25@upr.edu>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
 * Gets the name of the students in the specified course.
 * @param $id_of_course the id of the course.
 */
function get_students_of_course(int $id_of_course){
    global $DB;
    $courseid = $id_of_course;
    $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
    
    $student_ids = get_student_ids_by_course($courseid);   
    
    //$users = $DB->get_records('user', null, 'id, firstname, lastname');
    //$users = $DB->get_records_sql('SELECT firstname, lastname FROM {user} WHERE id IN ('.$student_ids.')');    
    
    $users = $DB->get_recordset_list('user', 'id', $student_ids);
    
    $student_names = array();    
    foreach($users as $user){
        $student_names[$user->id] = $user->firstname.' '.$user->lastname;
    }
   
    $users->close();
    
    //cleaning variables
    unset($student_ids);
    unset($users);    
    
    return $student_names;
}

/*
 * Gets the ids of the students in the course specified.
 * @param $id_of_course the id of the course.
 */
function get_student_ids_by_course(int $id_of_course){
    global $DB;
    $courseid = $id_of_course;
    
    $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
    
    $enrolment_type = $DB->get_recordset('enrol', array('courseid' => $courseid, 'roleid' => $roleid), '', 'id');
        
    $enrolment_ids = array();
    foreach($enrolment_type as $enrol){
        $enrolment_ids[] = $enrol->id;
    }
    
    $enrolment_type->close();
    
    $student_id_values = array();
    
    //$enrolment_records = $DB->get_records_sql("SELECT userid FROM {user_enrolments} WHERE enrolid IN ({implode(?)})", $enrolment_ids);
    
    //retrieving the enrolment ids used by the students of id userid
    $enrolment_records = $DB->get_recordset_list('user_enrolments', 'enrolid', $enrolment_ids, '', 'userid');   
    
    foreach($enrolment_records as $records){
        $student_id_values[] = $records->userid;
    }
    
    /*
    foreach($enrolment_type as $type){
        $student_ids = $DB->get_records('user_enrolments', array('enrolid' => $type->id));
        
        if(!empty($student_ids)){
            foreach($student_ids as $students){
                $student_id_values[] = $students->userid;
            }
        }        
    }
    */
    $enrolment_records->close();
    
    //cleaning variables
    unset($enrolment_type);
    unset($enrolment_records);
    
    return $student_id_values;
}

/*
 * Database call the retrieves the names of the modules of a course based on activity type.
 * @param int courseid the id of the course.
 * @param string $moduletype the activity type from which the module names will be extracted.
 */
function get_module_names_by_course(int $courseid, string $moduletype){
    global $DB;
    
    $modules = $DB->get_recordset($moduletype, array('course' => $courseid));
    
    $modulenames = array();
    foreach($modules as $mod){
        $modulenames[] = $mod->name;
    }
    $modules->close();
    unset($modules);
    return $modulenames;
}

/*
 * Returns an array of the type of modules on a course.
 * @param $id_of_course the id of the course.
 */
function get_module_types(int $id_of_course){
    global $DB;
    $courseid = $id_of_course;
    $index = 0;
    $module_types = $DB->get_recordset('modules');
    $course_modules = $DB->get_records('course_modules', array('course' => $courseid), '', 'DISTINCT module');
   
    $course_module_types = array();
    
    foreach ($module_types as $modules) {
        foreach ($course_modules as $course) {
            if ($modules->id == $course->module) {
                
                switch ($modules->name) {
                    case 'assign':
                        $course_module_types[$index] = $modules->name;
                        $index++;
                        break;
                    case 'book':
                        $course_module_types[$index] = $modules->name;
                        $index++;                        
                        break;
                    case 'page':
                        $course_module_types[$index] = $modules->name;
                        $index++;                        
                        break;
                    case 'quiz':
                        $course_module_types[$index] = $modules->name;
                        $index++;                        
                        break;
                }
                continue;
            }
        }
    } 
    
    $module_types->close();
    
    unset($module_types);
    unset($course_modules);
    
    return $course_module_types;
}

/*
 * Returns an array containing the ids of the modules completed.
 * @param int $courseid is the id of the course of the modules to be extracted.
 */
function local_activitytracker_get_modules_completed_by_course(int $courseid){
    global $DB;
    
    $coursemodules = $DB->get_recordset('course_modules', array('course' => $courseid));    
    
    $coursemoduleids = array();
    foreach ($coursemodules as $cm) {
        $coursemoduleids[] = $cm->id;
    }    
    
    $modules_completed = $DB->get_recordset_list('course_modules_completion', 'coursemoduleid', $coursemoduleids, 
        $sort = 'coursemoduleid');
    //$modules_completed = $DB->get_records('course_modules_completion');    
    
    $completedmoduleids = array();    
    foreach ($modules_completed as $mc) {
        $completedmoduleids[] = $mc->coursemoduleid;
    }
    /*
    $course_modules_completed = array();
    foreach($coursemodules as $cm){
        foreach($modules_completed as $mod_completed){
            if($cm->id == $mod_completed->coursemoduleid){
                $course_modules_completed[] = $mod_completed;
            }
        }
    }
    */
    $coursemodules->close();
    $modules_completed->close();
    return $completedmoduleids;
}

/*
 * Returns an object array with the activities completed on a course.
 * @param int $courseid the id of the course from which the activities will be extracted.
 * @param int $module_type the value which specifies the type of module to be extracted.
 */
function local_activitytracker_get_modules_completed_by_course_and_type(int $courseid, int $module_type){
    global $DB;
    $index = 0;
    
    $completedcoursemoduleids = local_activitytracker_get_modules_completed_by_course($courseid);
    
    $activity_modules = $DB->get_records('course_modules', array('course' => $courseid, 'module' => $module_type));    
    $completed_activity_modules = array();    
    //filter the completed course modules by activity type
    foreach($activity_modules as $activity){
        foreach($completedcoursemoduleids as $cmc){
            if($activity->id == $cmc){
                $completed_activity_modules[$index++] = $activity;
                $index++;
            }
        }
    }
    return $completed_activity_modules;
}


function local_activitytracker_count_modules_completed(int $courseid, string $moduletype){
    global $DB;
    $index = 0;
    
    // get activity type id with the given activity type name.
    $modtype = $DB->get_field('modules', 'id', array('name' => $moduletype));;
    $modules = $DB->get_recordset($moduletype, array('course' => $courseid));
        
    $completedcoursemoduleids = local_activitytracker_get_modules_completed_by_course($courseid);
    
    $activity_modules = $DB->get_records('course_modules', array('course' => $courseid, 'module' => $modtype));
    
    $completed_activity_modules = array();
    
    //filter the completed course modules by activity type
    foreach($activity_modules as $activity){
        foreach($completedcoursemoduleids as $cmc){
            if($activity->id == $cmc){
                $completed_activity_modules[$index++] = $activity;
                $index++;
            }
        }
    }
    
    $count_of_modules_completed = array();    
    foreach($modules as $mod){
        foreach($completed_activity_modules as $ctm){
            if($ctm->instance == $mod->id){
                $count_of_modules_completed[$mod->name] = $DB->count_records('course_modules_completion',
                    array('coursemoduleid' => $ctm->id));
                break;
            }
            else{
                $count_of_modules_completed[$mod->name] = 0;
            }
        }
    }
    $modules->close();
    return $count_of_modules_completed;
}

/*
 * Returns an array wth the grades of the activities that have been completed by a student in a course.
 * @param int $courseid the id of the course from which the grades will be extracted.
 * @param strin $student_name the name of the student who completed the activities.
 */
function local_activitytracker_get_activity_grades_by_student(int $courseid, string $student_name, int $activity_type){
    global $DB;
    $name = $student_name;
    $index = 0;
    
    $completed_activity_modules = local_activitytracker_get_modules_completed_by_course_and_type($courseid, $activity_type);
    //$modules_completed = local_activitytracker_get_modules_completed_by_course($courseid);
    
    $student = $DB->get_recordset('user');
    $modtypename = $DB->get_field('modules', 'name', array('id' => $activity_type));
    
    //selected student is extracted into a new array from the user records.
    $student_record = array();
    foreach($student as $user){
        if($user->firstname.' '.$user->lastname == $name){
            $student_record = $user;
        }
    }
    
    $student->close();
    unset($student);
    
    $modules_completed_by_student = $DB->get_recordset('course_modules_completion', array('userid' => $student_record->id));
    
    $modulescompleted = array();
    foreach ($modules_completed_by_student as $mcs) {
        $modulescompleted[] = $mcs->coursemoduleid;
    }
    $grades = array();
    
    switch($activity_type){
        case 1:     //GET ASSIGNMENT GRADES            
            
            //$assignment_grades = $DB->get_recordset('assign_grades', array('userid' =>$student_record->id), $sort='assignment');
            //$assignments = $DB->get_records('assign', array('course' => $courseid));
            
            // /*
            $all_student_grades = $DB->get_records('grade_grades', array('userid' => $student_record->id), '', $fields = 'id, itemid, finalgrade');
            $course_assign_grades = $DB->get_recordset('grade_items', array('courseid' => $courseid, 'itemmodule' => $modtypename));            
            
            $index = 0;          
            
            /*
            $all_student_grades_final_grade = array();
            $all_student_grades_itemid = array();            
            $grade_items_reduced = array();
            $grade_items_ids = array();
            $grade_items_instance = array();
            */
            
            /*
            foreach($course_assign_grades as $cgrades){
                $grade_items_ids[] = $cgrades->id;
                $grade_items_instance[] = $cgrades->iteminstance;
            }
            */
            
            foreach($course_assign_grades as $cgrades){
                foreach($all_student_grades as $a){
                    if(($a->itemid == $cgrades->id) && $a->finalgrade != null){
                        $grades[] = $a->finalgrade;
                        continue;
                    }
                }
            }          
            $course_assign_grades->close();
            
            /*
            print_r('Grade items ids are: ');
            print_r($grade_items_ids);
            
            print_r('Grade items instances are: ');
            print_r($grade_items_instance);
            */
            
            
            //filter the assignments completed by the ones completed by the selected student
            //and store in new array.
            /*
            $student_assignments = array();
            foreach($completed_activity_modules as $cam){
                foreach($modulescompleted as $mod){
                    if(($cam->id == $mod)){
                        $student_assignments[] = $cam->instance;
                    }
                }
            }
            */
            
            
            /*
            print_r("Student assignments are: ");
            print_r($student_assignments);
            
            print_r('Grade grades final grades are: ');
            print_r($all_student_grades_final_grade);
            
            print_r('Grade grades itemids are: ');
            print_r($all_student_grades_itemid);
            */
            
            
            /*
            foreach($grade_items_ids as $assign_grades){
                if($grade_items_instance[$index] == $student_assignments[$index]){
                    if($assign_grades == $all_student_grades_itemid[$index]){   //assignment was completed
                        $grades[] = $all_student_grades_final_grade[$index];
                    }
                    $index++;
                }
                else{
                    $grades[] = 0.00000;        //assignment was not completed
                    $index++;  
                }
                             
            }
            */
            
            // */
             /*
            //create array with assignment scores completed by the student
            $index = 0;            
            $assign_grades_assignments = array();
            $assign_grades_grades = array();
            
            foreach($assignment_grades as $a){
                $assign_grades_assignments[] = $a->assignment;
                $assign_grades_grades[] = $a->grade;
            }
            
            print_r('Assign grade asignments are: ');
            print_r($assign_grades_assignments);
            
            print_r('Assign grades are: ');
            print_r($assign_grades_grades);
            
            foreach($assignments as $assign){
                if($assign->id == $assign_grades_assignments[$index]){     //checks if the assignment was completed
                    if($student_assignments[$index] == $assign_grades_assignments[$index]){   //assignment was completed
                        $grades[] = $assign_grades_grades[$index];
                    }
                    $index++;                    
                }             
                else{
                    $grades[] = 0.00000;        //assignment was not completed
                }
            }
            
            print_r('Grades are: ');
            print_r($grades);
            
             */
            break;
        case 3:     //GET BOOK VIEW STATUS
            //filter the books completed by the ones completed by the selected student
            //and store in new array.
            $student_books = array();
            foreach($completed_activity_modules as $cam){
                foreach($modulescompleted as $mod){
                    if($cam->id == $mod){
                        $student_books[$index] = $cam;
                        $index++;
                    }
                }
            }
            $books = $DB->get_records('book', array('course' =>$courseid));
            
            //create array with book scores completed by the student
            $index = 0;
            foreach($books as $book){
                foreach($student_books as $sb){
                    if($sb->instance == $book->id){   //book was completed
                        $grades[$index] = 1;
                        break;
                    }
                    $grades[$index] = 0;        //book was not completed
                }
                $index++;
            }
            break;
        case 15:    //GET PAGE VIEW STATUS
            
            //filter the pages completed by the ones completed by the selected student
            //and store in new array.
            $student_pages = array();
            foreach($completed_activity_modules as $cam){
                foreach($modulescompleted as $mod){
                    if($cam->id == $mod){
                        $student_pages[$index] = $cam;
                        $index++;
                    }
                }
            }
            
            $pages = $DB->get_records('page', array('course' => $courseid));
            
            //create array with page scores completed by the student
            $index = 0;
            foreach($pages as $page){
                foreach($student_pages as $sp){
                    if($sp->instance == $page->id){   //page was completed
                        $grades[$index] = 1;
                        break;
                    }
                    $grades[$index] = 0;        //page was not completed
                }
                $index++;
            }
            break;
        case 16:    //GET QUIZ GRADES
            
            $all_student_grades = $DB->get_records('grade_grades', array('userid' => $student_record->id), '', $fields = 'id, itemid, finalgrade');
            $course_assign_grades = $DB->get_recordset('grade_items', array('courseid' => $courseid, 'itemmodule' => $modtypename)); 
            
            foreach($course_assign_grades as $cgrades){
                foreach($all_student_grades as $a){
                    if(($a->itemid == $cgrades->id) && $a->finalgrade != null){
                        $grades[] = $a->finalgrade;
                        continue;
                    }
                }
            }
            $course_assign_grades->close();            
            break;
    }
    $modules_completed_by_student->close();
    return $grades;
}

function get_module_grade_names(int $courseid, string $student_name, int $activity_type){
    global $DB;
    $name = $student_name;
    $student = $DB->get_recordset('user');
    
    foreach($student as $user){
        if($user->firstname.' '.$user->lastname == $name){
            $student_record = $user;
        }
    }
    
    $student->close();
    unset($student);
    
    $modtypename = $DB->get_field('modules', 'name', array('id' => $activity_type));    
    
    $all_student_grades = $DB->get_records('grade_grades', array('userid' => $student_record->id));
    $course_assign_grades = $DB->get_recordset('grade_items', array('courseid' => $courseid, 'itemmodule' => $modtypename));
    
    $grade_names = array();
    foreach($course_assign_grades as $cgrades){
        foreach($all_student_grades as $a){
            if(($a->itemid == $cgrades->id) && $a->finalgrade != null){
                $grade_names[] = $cgrades->itemname;
                continue;
            }
        }
    }
    $course_assign_grades->close();
    return $grade_names;
}


/*
function local_activitytracker_get_grades(int $courseid, int $moduletype){
    global $DB;
    $name = $student_name;
    $index = 0;
    
    $completed_activity_modules = local_activitytracker_get_modules_completed_by_course_and_type($courseid, $activity_type);
    $modules_completed = local_activitytracker_get_modules_completed_by_course($courseid);
    $student = $DB->get_recordset('user');
    
    //selected student is extracted into a new array from the user records.
    $student_record = array();
    foreach($student as $user){
        if($user->firstname.' '.$user->lastname == $name){
            $student_record = $user;
        }
    }
    
    $student->close();
    unset($student);
    
    $modules_completed_by_student = $DB->get_recordset('course_modules_completion', array('userid' => $student_record->id));
    
    $modulescompleted = array();
    foreach ($modules_completed_by_student as $mcs) {
        $modulescompleted[] = $mcs->coursemoduleid;
    }
    $grades = array();
    switch($moduletype){
        case 1:
            break;
        case 17:
            break;
    }
    
}
*/