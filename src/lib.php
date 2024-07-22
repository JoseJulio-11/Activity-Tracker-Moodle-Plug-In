<?php
/**
 * Library file containing navigation hooks.
 *
 * @package     core_privacy
 * @category    test
 * @copyright   2021 Jose Julio Sanchez <jose.sanchez25@upr.edu>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Insert a link to main_logger.php on the course page settings navigation menu.
 *
 * @param navigation_node $navigation Node representing the course page in the navigation tree.
 * @param $course the course object.
 * @param $context the context object used to know what the template will render on the page.
 */
function local_activitytracker_extend_navigation_course(navigation_node $navigation, $course, $context) { 
    if(has_capability('moodle/course:managefiles', $context)){
        $activitytrackernode = $navigation->add('Activity Tracker', new moodle_url('/local/activitytracker/main_logger.php',
            array('id' => $course->id)),
            navigation_node::TYPE_CUSTOM,
            null,
            null,
            new pix_icon('i/report', ''));
        
        $studentactivitytrackernode = $navigation->add('Student Logger', 
            new moodle_url('/local/activitytracker/student_logger.php',
            array('id' => $course->id)),
            navigation_node::TYPE_CUSTOM,
            null,
            null,
            new pix_icon('i/report', ''));
    }   
}