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
 * Form used for the main logger page.
 *
 * @package     core_privacy
 * @category    test
 * @copyright   2021 Jose Julio Sanchez <jose.sanchez25@upr.edu>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");
require_once('querylib.php');

class activity_type_form extends moodleform {
    // Add elements to form.
    public function definition() {
        $courseid = optional_param('id', 0, PARAM_INT);

        $modules = get_module_types($courseid);
        
        $mform = $this->_form; // Don't forget the underscore!
        
        $mform->addElement('header', 'Activities', 'Activities');               
        
        $mform->addElement('select', 'modules', 'Select an activity type', $modules); // Add elements to your form.
        
        $mform->addHelpButton('modules', 'modulehelp','local_activitytracker','block_extsearch');
        $mform->setType('activity', PARAM_NOTAGS);                 // Set type of element.
        $mform->setDefault('activity', 'Select an activity');
        
        $this->add_action_buttons(false, 'Confirm');
    }
    
    // Custom validation should be added here.
    function validation($data, $files) {
        return array();
    }    
}