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
 *  Main page of the activity tracker.
 *
 * @package     core_privacy
 * @category    local plugin
 * @copyright   2021 Jose Julio Sanchez <jose.sanchez25@upr.edu>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once('activity_type_form.php');

$courseid = optional_param('id', 0, PARAM_INT);

// Basic access checks.
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    throw new moodle_exception('invalidcourseid');
}

require_login($course);

$PAGE->set_url(new moodle_url('/local/activitytracker/main_logger.php', array('id' => $courseid)));
$PAGE->set_heading('Activity Tracker - Main Logger');
$PAGE->set_title('Activity Tracker - Main Logger');

$form = new activity_type_form(new moodle_url('/local/activitytracker/main_logger.php', array('id' => $courseid)));

// --------------------ACTIVITY TYPE DATA SUBMISSION-----------------------
if ($form->get_data()) {
    
    $atype = get_module_types($courseid);   // Returns array of modules.  
    
    $url = new moodle_url('/local/activitytracker/activity_modules_page.php', 
        array('type' => $atype[$form->get_data()->modules + 0], 
            'id' => $courseid));
    
    redirect($url);
    
} else {
    // ---------ONLY RENDER FORM-------------
    echo $OUTPUT->header();
    
    $templatecontext = (object)[];
    
    echo $OUTPUT->render_from_template('local_activitytracker/main_logger', $templatecontext);
    
    $form->display();
    
    echo $OUTPUT->footer();    
}