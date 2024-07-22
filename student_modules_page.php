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
 * Page for student focused information. Receives parameters from student_logger.php and
 *  displays the information accordingly.
 *
 * @package     core_privacy
 * @category    test
 * @copyright   2021 Jose Julio Sanchez <jose.sanchez25@upr.edu>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('ASSIGN_MODULE_TYPE', 1);
define('BOOK_MODULE_TYPE', 3);
define('PAGE_MODULE_TYPE', 16);
define('QUIZ_MODULE_TYPE', 17);

require_once(__DIR__.'/../../config.php');
require_once('modules_form.php');
require_once('querylib.php');

$type                = optional_param('type', ' ', PARAM_TEXT);
$student_selected    = optional_param('name', ' ', PARAM_TEXT);
$courseid            = optional_param('id', 0, PARAM_INT);

// Basic access checks
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    throw new moodle_exception('invalidcourseid');
}

require_login($course);
$PAGE->set_url(new moodle_url('/local/activitytracker/student_modules_page.php', array('id' => $courseid)));
$PAGE->set_heading('Activity Tracker - Student Logger');
$PAGE->set_title('Activity Tracker - Student Logger');

if(empty($type)){
    
    echo $OUTPUT->header();
    
    $templatecontext = (object)[];
    
    echo $OUTPUT->render_from_template('local_activitytracker/student_logger', $templatecontext);
    
    echo $OUTPUT->footer();
} else{
    switch($type){
        case 'assign':
            $modtype = $DB->get_field('modules', 'id', array('name' => $type)); 
            $graded_assignment_names = get_module_grade_names($courseid, $student_selected, $modtype);
            //$assignment_names = get_module_names_by_course($courseid, $type);
            $assign_grades = local_activitytracker_get_activity_grades_by_student($courseid, $student_selected, $modtype);
            
            //set url for when assign is chosen
            $activity_url = new moodle_url('/local/activitytracker/student_modules_page.php',
                array('type' => $type, 'name' => $student_selected, 'id'=> $courseid));
            
            $activity_modules_form = new modules_form($activity_url, $graded_assignment_names);
            
            
            if(empty($assign_grades)){
                //STUDENT HAS NOT COMPLETED ANY ASSIGNMENTS 
                \core\notification::info('Student has not completed any assignments');
                echo $OUTPUT->header();
                
                $templatecontext = (object)[
                    'student_selected' => $student_selected,
                ];
                
                echo $OUTPUT->render_from_template('local_activitytracker/student_logger', $templatecontext);
                
                $activity_modules_form->display();
                
                echo $OUTPUT->footer();
            }else{
                if($modules_form = $activity_modules_form->get_data()){
                    //ASSIGN MODULES FORM SUBMITTED
                    
                    if($modules_form->checkbox_controller1 == 1){
                        //SHOW UNFILTERED ASSIGNMENTS                       

                        $assign_series = array();
                        foreach($assign_grades as $assign_count){
                            $assign_series[] = $assign_count;
                        }
                        $activity_completion = new core\chart_series('Number of students', $assign_series);
                        
                        $assign_bar_chart = generate_bar_chart($activity_completion, $graded_assignment_names, 20);
                        
                        echo $OUTPUT->header();
                        
                        $templatecontext = (object)[];
                        
                        echo $OUTPUT->render_from_template('local_activitytracker/student_logger', $templatecontext);
                        
                        $activity_modules_form->display();
                        
                        echo $OUTPUT->render($assign_bar_chart);
                        echo $OUTPUT->footer();                        
                    }else{
                        //SHOW FILTERED ASSIGNMENTS
                        
                        $filtered_assignments = filter_modules($modules_form, $graded_assignment_names);
                        $count = filter_count($filtered_assignments, $assign_grades, $graded_assignment_names);
                        
                        if($filtered_assignments == array()){
                            //NO ASSIGNMENT WAS SELECTED
                            echo $OUTPUT->header();
                            
                            $templatecontext = (object)[
                                'student_selected' => $student_selected,
                            ];
                            
                            echo $OUTPUT->render_from_template('local_activitytracker/student_logger', $templatecontext);
                            
                            $activity_modules_form->display();
                            
                            echo $OUTPUT->footer();
                        }else{
                            //ASSIGNMENTS WERE SELECTED
                            
                            $activity_completion = new core\chart_series('Number of students', $count);
                            $assign_bar_chart = generate_bar_chart($activity_completion, $filtered_assignments, 20);
                            
                            echo $OUTPUT->header();
                            
                            $templatecontext = (object)[
                                'student_selected' => $student_selected,
                            ];
                            
                            echo $OUTPUT->render_from_template('local_activitytracker/student_logger', $templatecontext);
                            
                            $activity_modules_form->display();
                            
                            echo $OUTPUT->render($assign_bar_chart);
                            echo $OUTPUT->footer();
                        }
                    }
                }else{
                    //ASSIGN MODULES FORM NOT SUBMITTED
                    $activity_completion = new core\chart_series('Student Score', $assign_grades);
                    
                    //$assignment_scores = generate_bar_chart_series($assignment_names, 100);
                    $assign_bar_chart = generate_bar_chart($activity_completion, $graded_assignment_names, 20);
                    
                    echo $OUTPUT->header();
                    
                    $templatecontext = (object)[
                        'student_selected' => $student_selected,
                    ];
                    
                    echo $OUTPUT->render_from_template('local_activitytracker/student_logger', $templatecontext);
                    
                    $activity_modules_form->display();
                    
                    echo $OUTPUT->render($assign_bar_chart);
                    //echo $OUTPUT->render($pie_chart);
                    
                    echo $OUTPUT->footer();
                }
            }          
            break;
        case 'book':
            $modtype = $DB->get_field('modules', 'id', array('name' => $type));            
            $book_names = get_module_names_by_course($courseid, $type);
            $book_statuses = local_activitytracker_get_activity_grades_by_student($courseid, $student_selected, $modtype);
            
            //set url for when assign is chosen
            $activity_url = new moodle_url('/local/activitytracker/student_modules_page.php',
                array('type' => $type, 'name' => $student_selected, 'id'=> $courseid));
            $activity_modules_form = new modules_form($activity_url, $book_names);
            
            if(empty($book_statuses)){
                //STUDENT HAS NOT READ ANY BOOKS 
                \core\notification::info('Student has not completed any book activities');                
                echo $OUTPUT->header();
                
                $templatecontext = (object)[
                    'student_selected' => $student_selected,
                ];
                
                echo $OUTPUT->render_from_template('local_activitytracker/student_logger', $templatecontext);
                
                $activity_modules_form->display();
                
                echo $OUTPUT->footer();
            }else{
                if($modules_form = $activity_modules_form->get_data()){
                    //ASSIGN MODULES FORM SUBMITTED
                    
                    if($modules_form->checkbox_controller1 == 1){
                        //SHOW UNFILTERED ASSIGNMENTS                        
                        
                        $book_series = array();
                        foreach($book_statuses as $assign_count){
                            $book_series[] = $assign_count;
                        }
                        $activity_completion = new core\chart_series('Number of students', $book_series);
                        
                        //$book_scores = generate_bar_chart_series($book_names, 100);
                        $books_bar_chart = generate_bar_chart($activity_completion, $book_names, 1);
                        
                        echo $OUTPUT->header();
                        
                        $templatecontext = (object)[
                            'student_selected' => $student_selected,
                        ];
                        
                        echo $OUTPUT->render_from_template('local_activitytracker/student_logger', $templatecontext);
                        
                        $activity_modules_form->display();
                        
                        echo $OUTPUT->render($books_bar_chart);
                        echo $OUTPUT->footer();
                        
                    }else{
                        //SHOW FILTERED BOOKS
                        
                        $filtered_books = filter_modules($modules_form, $book_names);
                        $count = filter_count($filtered_books, $book_statuses, $book_names);
                        
                        if($filtered_books == array()){
                            //NO BOOK WAS SELECTED
                            echo $OUTPUT->header();
                            
                            $templatecontext = (object)[
                                'student_selected' => $student_selected,
                            ];
                            
                            echo $OUTPUT->render_from_template('local_activitytracker/student_logger', $templatecontext);
                            
                            $activity_modules_form->display();
                            
                            echo $OUTPUT->footer();
                        }else{
                            //BOOKS WERE SELECTED
                            
                            $activity_completion = new core\chart_series('Number of students', $count);
                            $book_bar_chart = generate_bar_chart($activity_completion, $filtered_books, 1);
                            
                            echo $OUTPUT->header();
                            
                            $templatecontext = (object)[
                                'student_selected' => $student_selected,
                            ];
                            
                            echo $OUTPUT->render_from_template('local_activitytracker/student_logger', $templatecontext);
                            
                            $activity_modules_form->display();
                            
                            echo $OUTPUT->render($book_bar_chart);
                            echo $OUTPUT->footer();
                        }
                    }
                }else{
                    //BOOK MODULES FORM NOT SUBMITTED
                    $activity_completion = new core\chart_series('View status', $book_statuses);
                    
                    //$book_scores = generate_bar_chart_series($book_names, 100);
                    $book_bar_chart = generate_bar_chart($activity_completion, $book_names, 1);
                    
                    echo $OUTPUT->header();
                    
                    $templatecontext = (object)[
                        'student_selected' => $student_selected,
                    ];
                    
                    echo $OUTPUT->render_from_template('local_activitytracker/student_logger', $templatecontext);
                    
                    $activity_modules_form->display();
                    
                    echo $OUTPUT->render($book_bar_chart);
                    //echo $OUTPUT->render($pie_chart);
                    
                    echo $OUTPUT->footer();
                }
            }           
            break;
        case 'page':
            $modtype = $DB->get_field('modules', 'id', array('name' => $type));            
            $page_names = get_module_names_by_course($courseid, $type);
            $page_statuses = local_activitytracker_get_activity_grades_by_student($courseid, $student_selected, $modtype);
            
            //set url for when assign is chosen
            $activity_url = new moodle_url('/local/activitytracker/student_modules_page.php',
                array('type' => $type, 'name' => $student_selected, 'id'=> $courseid));
            $activity_modules_form = new modules_form($activity_url, $page_names);
            
            if(empty($page_statuses)){
                //STUDENT HAS NOT COMPLETED ANY PAGE ACTIVITIES  
                \core\notification::info('Student has not viewed any pages');                
                echo $OUTPUT->header();
                
                $templatecontext = (object)[
                    'student_selected' => $student_selected,
                ];
                
                echo $OUTPUT->render_from_template('local_activitytracker/student_logger', $templatecontext);
                
                $activity_modules_form->display();
                
                echo $OUTPUT->footer();
            }else{
                if($modules_form = $activity_modules_form->get_data()){
                    //ASSIGN MODULES FORM SUBMITTED
                    if($modules_form->checkbox_controller1 == 1){
                        //SHOW UNFILTERED ASSIGNMENTS IF TRUE
                        
                        $page_series = array();
                        foreach($page_statuses as $page_count){
                            $page_series[] = $page_count;
                        }
                        $activity_completion = new core\chart_series('Number of students', $page_series);
                        
                        //$page_scores = generate_bar_chart_series($page_names, 100);
                        $pages_bar_chart = generate_bar_chart($activity_completion, $page_names, 1);
                        
                        echo $OUTPUT->header();
                        
                        $templatecontext = (object)[
                            'student_selected' => $student_selected,
                        ];
                        
                        echo $OUTPUT->render_from_template('local_activitytracker/student_logger', $templatecontext);
                        
                        $activity_modules_form->display();
                        
                        echo $OUTPUT->render($pages_bar_chart);
                        echo $OUTPUT->footer();
                        
                    }else{
                        //SHOW FILTERED ASSIGNMENTS
                        
                        $filtered_pages = filter_modules($modules_form, $page_names);
                        $count = filter_count($filtered_pages, $page_statuses, $page_names);
                        
                        if($filtered_pages == array()){
                            //NO ASSIGNMENT WAS SELECTED
                            echo $OUTPUT->header();
                            
                            $templatecontext = (object)[
                                'student_selected' => $student_selected,
                            ];
                            
                            echo $OUTPUT->render_from_template('local_activitytracker/student_logger', $templatecontext);
                            
                            $activity_modules_form->display();
                            
                            echo $OUTPUT->footer();
                        }else{
                            //PAGES WERE SELECTED
                            $activity_completion = new core\chart_series('Number of students', $count);
                            $pages_bar_chart = generate_bar_chart($activity_completion, $filtered_pages, 1);
                            
                            echo $OUTPUT->header();
                            
                            $templatecontext = (object)[
                                'student_selected' => $student_selected,
                            ];
                            
                            echo $OUTPUT->render_from_template('local_activitytracker/student_logger', $templatecontext);
                            
                            $activity_modules_form->display();
                            
                            echo $OUTPUT->render($pages_bar_chart);
                            echo $OUTPUT->footer();
                        }
                    }
                }else{
                    //PAGE MODULES FORM NOT SUBMITTED                    
                    $activity_completion = new core\chart_series('View status', $page_statuses);
                    
                    //$page_scores = generate_bar_chart_series($page_names, 100);
                    $pages_bar_chart = generate_bar_chart($activity_completion, $page_names, 1);
                    
                    echo $OUTPUT->header();
                    
                    $templatecontext = (object)[
                        'student_selected' => $student_selected,
                    ];
                    
                    echo $OUTPUT->render_from_template('local_activitytracker/student_logger', $templatecontext);
                    
                    $activity_modules_form->display();
                    
                    echo $OUTPUT->render($pages_bar_chart);
                    //echo $OUTPUT->render($pie_chart);
                    
                    echo $OUTPUT->footer();
                }
            }           
            break;
        case 'quiz':
            $modtype = $DB->get_field('modules', 'id', array('name' => $type));  
            $graded_quiz_names = get_module_grade_names($courseid, $student_selected, $modtype);      
            
            //$quiz_names = get_module_names_by_course($courseid, $type);
            $quiz_grades = local_activitytracker_get_activity_grades_by_student($courseid, $student_selected, $modtype);   
            
            
            
            //set url for when quiz is chosen
            $activity_url = new moodle_url('/local/activitytracker/student_modules_page.php',
                array('type' => $type, 'name' => $student_selected, 'id'=> $courseid));
            $activity_modules_form = new modules_form($activity_url, $graded_quiz_names);
            
            if(empty($quiz_grades)){
                //STUDENT HAS NOT COMPLETED ANY QUIZES
                \core\notification::info('Student has not completed any quizes');                
                echo $OUTPUT->header();
                
                $templatecontext = (object)[
                    'student_selected' => $student_selected,
                ];
                
                echo $OUTPUT->render_from_template('local_activitytracker/student_logger', $templatecontext);
                
                $activity_modules_form->display();
                
                echo $OUTPUT->footer();
            }else{
                if($modules_form = $activity_modules_form->get_data()){
                    //QUIZ MODULES FORM SUBMITTED
                    
                    if($modules_form->checkbox_controller1 == 1){
                        //SHOW UNFILTERED QUIZES
                        
                        $quiz_series = array();
                        foreach($quiz_grades as $quiz_count){
                            $quiz_series[] = $quiz_count;
                        }
                        $activity_completion = new core\chart_series('Number of students', $quiz_series);
                        
                        //$quiz_scores = generate_bar_chart_series($quiz_names, 100);
                        $quizzes_bar_chart = generate_bar_chart($activity_completion, $graded_quiz_names, 20);
                        
                        echo $OUTPUT->header();
                        
                        $templatecontext = (object)[
                            'student_selected' => $student_selected,
                        ];
                        
                        echo $OUTPUT->render_from_template('local_activitytracker/student_logger', $templatecontext);
                        
                        $activity_modules_form->display();
                        
                        echo $OUTPUT->render($quizzes_bar_chart);
                        echo $OUTPUT->footer();
                        
                    }else{
                        //SHOW FILTERED QUIZES
                        
                        $filtered_quizzes = filter_modules($modules_form, $graded_quiz_names);
                        $count = filter_count($filtered_quizzes, $quiz_grades, $graded_quiz_names);
                        
                        if($filtered_quizzes == array()){
                            //NO QUIZ WAS SELECTED
                            echo $OUTPUT->header();
                            
                            $templatecontext = (object)[
                                'student_selected' => $student_selected,
                            ];
                            
                            echo $OUTPUT->render_from_template('local_activitytracker/student_logger', $templatecontext);
                            
                            $activity_modules_form->display();
                            
                            echo $OUTPUT->footer();
                        }else{
                            //QUIZES WERE SELECTED
                            $activity_completion = new core\chart_series('Number of students', $count);
                            $quizzes_bar_chart = generate_bar_chart($activity_completion, $filtered_quizzes, 20);
                            
                            echo $OUTPUT->header();
                            
                            $templatecontext = (object)[
                                'student_selected' => $student_selected,
                            ];
                            
                            echo $OUTPUT->render_from_template('local_activitytracker/student_logger', $templatecontext);
                            
                            $activity_modules_form->display();
                            
                            echo $OUTPUT->render($quizzes_bar_chart);
                            echo $OUTPUT->footer();
                        }
                    }
                }else{
                    //QUIZ MODULES FORM NOT SUBMITTED
                    $activity_completion = new core\chart_series('Student Score', $quiz_grades);
                    $quizzes_bar_chart = generate_bar_chart($activity_completion, $graded_quiz_names, 20);
                    
                    echo $OUTPUT->header();
                    
                    $templatecontext = (object)[
                        'student_selected' => $student_selected,
                    ];
                    
                    echo $OUTPUT->render_from_template('local_activitytracker/student_logger', $templatecontext);
                    
                    $activity_modules_form->display();
                    
                    echo $OUTPUT->render($quizzes_bar_chart);
                    //echo $OUTPUT->render($pie_chart);
                    
                    echo $OUTPUT->footer();
                }
            }           
            break;
        } 
}

/*
 * Filters the modules not checked by the user.
 * @param $form_data is the array containing the values of the checkboxes selected.
 * @param $module_names is an array containing the names of the modules in the form.
 */
function filter_modules(&$form_data, &$module_names){
    $filtered_modules = array();
    
    $index = 0;
    foreach($form_data as $assignment){
        if($assignment == '1')     //element must be an integer with value of 1
            $filtered_modules[] = $module_names[$index];
            $index++;
    }
    return $filtered_modules;
}

/*
 * Filters the completion values of the modules selected by the user.
 * @param $filtered_modules are the modules selected by the user.
 * @param $module_counts are the amount of module completions of the completed modules.
 */
function filter_count(&$filtered_modules, &$module_grades, &$module_names){
    
    $module_objects = array();
    $count = array();
    $index = 0;
    
    $mod_name_keys = array_keys($module_grades);
    
    foreach($module_grades as $module){
        $module_objects[$index] = (object)[$module_names[$index] => $module];
        $index++;
    }
    
    foreach($filtered_modules as $filtered){
        foreach($module_objects as $obj){
            if($filtered == key($obj)){
                $count[] = $obj->$filtered;
                break;
            }
        }
    }
    return $count;
}

function generate_bar_chart(&$bar_series, &$bar_lables, $step){
    //Bar Chart - setup
    $bar_chart = new core\chart_bar();
    $bar_chart->set_title('Activity Scores');
    $bar_chart->add_series($bar_series);
    $bar_chart->set_labels($bar_lables);
    $bar_chart->set_legend_options(['position' => 'top']);  // Change legend position to top side.
    
    $yaxis = $bar_chart->get_yaxis(0, true);
    $yaxis->set_stepsize($step);
    
    return $bar_chart;
}