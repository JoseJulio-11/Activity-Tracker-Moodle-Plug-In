<?php

/**
 *Page for student focused information.
 *
 * @package     core_privacy
 * @category    test
 * @copyright   2021 Jose Julio Sanchez <jose.sanchez25@upr.edu>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/local/activitytracker/classes/form/activities_logger.php');
$PAGE->set_url(new moodle_url('/local/activitytracker/activities_logger.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title('Activity logger - Activity');

//Charts series and labels
$students = new core\chart_series('Number of students', [4, 10, 7, 12]);
$student_grades = new core\chart_series('Number of grades', [2, 5, 8, 0, 1]);

$mform = new student_logger_form();

//Bar Chart - setup
$bar_chart = new core\chart_bar();
$bar_chart->set_title('Activity Completion by Students');
$bar_chart->add_series($students);
$bar_chart->set_labels(['Quiz #1', 'Quiz #2', 'Quiz #3', 'Exam #1']);


//Pie Chart - setup
$pie_chart = new \core\chart_pie();
$pie_chart->set_title('Student Grades Distribution');
$pie_chart->add_series($student_grades); // On pie charts we just need to set one series.
$pie_chart->set_labels(['A', 'B', 'C', 'D', 'F']);


echo $OUTPUT->header();

$templatecontext = (object)[
    'texttodisplay' => 'Activity page text',
];

echo $OUTPUT->render_from_template('local_activitytracker/activities_logger', $templatecontext);

$mform->display();

echo $OUTPUT->render($bar_chart);
echo $OUTPUT->render($pie_chart);

echo $OUTPUT->footer();