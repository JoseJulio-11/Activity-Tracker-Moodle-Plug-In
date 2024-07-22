<?php

/**
 * Page for tracking student activity information. 
 * This page redriects to student_modules_page.php with the corresponding
 *  student and activity type as parameter that the user selected.
 *
 * @package     core_privacy
 * @category    test
 * @copyright   2021 Jose Julio Sanchez <jose.sanchez25@upr.edu>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__.'/../../config.php');
require_once('student_modules_form.php');

$courseid = optional_param('id', 0, PARAM_INT);

// Basic access checks
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    throw new moodle_exception('invalidcourseid');
}

require_login($course);

$PAGE->set_url(new moodle_url('/local/activitytracker/student_logger.php', array('id' => $courseid)));
$PAGE->set_heading('Activity Tracker - Student Logger');
$PAGE->set_title('Activity Tracker - Student Logger');

$studentform = new student_modules_form(new moodle_url('/local/activitytracker/student_logger.php', array('id' => $courseid)));

//--------------FORM DATA HANDLING------------------------------------
if($studentform->get_data()) {
    
    $activitytypechosen = get_module_types($courseid);
    $studentselected = get_students_of_course($courseid);
    
    //set url for when assign is chosen
    $url = new moodle_url('/local/activitytracker/student_modules_page.php',
        array('type' => $activitytypechosen[$studentform->get_data()->modules + 0],
            'name' => $studentselected[$studentform->get_data()->student + 0],
            'id'=> $courseid));
        
    redirect($url);    
} 
else {
    //---------ONLY RENDER FORM-------------
    echo $OUTPUT->header();
    
    $templatecontext = (object)[];
    
    echo $OUTPUT->render_from_template('local_activitytracker/student_logger', $templatecontext);
    
    $studentform->display();
    
    echo $OUTPUT->footer();
}
