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
 *  Main page of the activity logger with general information.
 *
 * @package     core_privacy
 * @category    local plugin
 * @copyright   2021 Jose Julio Sanchez <jose.sanchez25@upr.edu>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


 //Original Length: 525 lines of code
require_once(__DIR__.'/../../config.php');
require_once('modules_form.php');
require_once('querylib.php');

$type       = optional_param('type', array(), PARAM_TEXT);
$courseid   = optional_param('id', 0, PARAM_INT);

// Basic access checks.
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    throw new moodle_exception('invalidcourseid');
}

require_login($course);

$PAGE->set_url(new moodle_url('/local/activitytracker/activity_modules_page.php', array('id' => $courseid)));
$PAGE->set_heading('Activity Tracker - Main Logger');
$PAGE->set_title('Activity Tracker - Main Logger');

if(empty($type)){
   
    echo $OUTPUT->header();
    
    $templatecontext = (object)[];
    
    echo $OUTPUT->render_from_template('local_activitytracker/main_logger', $templatecontext);
    
    echo $OUTPUT->footer();
} else{
    switch($type){
        case 'assign':
            assignments_chart($courseid, $type);     
            break;
        case 'book':
            $url = new moodle_url('/local/activitytracker/activity_modules_page.php',
                array('type' => $type, 'id' => $courseid));
            
            $book_names = get_module_names_by_course($courseid, $type);
            $form = new modules_form($url, $book_names);
            $books = local_activitytracker_count_modules_completed($courseid, $type);
            if($fromform = $form->get_submitted_data()){
                //FORM WAS SUBMITTED                
                
                if($fromform->checkbox_controller1 == 1){
                    //SHOW UNFILTERED BOOKS                    
                    show_unfiltered_modules($form, $book_names);
                   
                }else{
                    //SHOW FILTERED BOOKS
                    $filteredmodules = filter_modules($fromform, $book_names);
                    $count = filter_count($filteredmodules, $books);      
                   
                    if(empty($filteredmodules)){
                        //NO BOOK WAS SELECTED
                        render_page_without_chart($OUTPUT, $form);
                    }else{
                        //BOOKS WERE SELECTED
                        $moduleseries = new core\chart_series('Number of students', $count);
                        $barchart = generate_bar_chart($moduleseries, $filteredmodules);
                        
                        echo $OUTPUT->header();
                        
                        $templatecontext = (object)[];
                        
                        echo $OUTPUT->render_from_template('local_activitytracker/main_logger', $templatecontext);
                        
                        $form->display();
                        
                        echo $OUTPUT->render($barchart);
                        echo $OUTPUT->footer();
                    }
                }
            }else{
                if(empty($books)){
                    //FORM IS NOT YET SUBMITTED ------- NO BOOKS COMPLETED                    
                    \core\notification::info('No books have been completed');
                    render_page_without_chart($OUTPUT, $form);
                }else{
                    //FORM IS NOT YET SUBMITTED ------- SHOW CHART WITH ALL BOOKS                    
                    $book_series = array();
                    foreach($books as $book_count){
                        $book_series[] = $book_count;
                    }
                    $moduleseries = new core\chart_series('Number of students', $book_series);
                    $barchart = generate_bar_chart($moduleseries, $book_names);
                    
                    echo $OUTPUT->header();
                    
                    $templatecontext = (object)[];
                    
                    echo $OUTPUT->render_from_template('local_activitytracker/main_logger', $templatecontext);
                    
                    $form->display();
                    
                    echo $OUTPUT->render($barchart);
                    echo $OUTPUT->footer();

                    show_unfiltered_modules($form, $book_names);
                }                
            }           
            break;
        case 'page':
            $url = new moodle_url('/local/activitytracker/activity_modules_page.php',
                array('type' => $type, 'id' => $courseid));
            $page_names = get_module_names_by_course($courseid, $type);
            $form = new modules_form($url, $page_names);
            $pages = local_activitytracker_count_modules_completed($courseid, $type);
            
            if($fromform = $form->get_submitted_data()){
                //FORM WAS SUBMITTED
                
                if($fromform->checkbox_controller1 == 1){
                    //SHOW UNFILTERED PAGE ACTIVITIES                    
                    
                    show_unfiltered_modules($form, $page_names);

                }else{
                    //SHOW FILTERED PAGE ACTIVITIES                    
                    $filteredmodules = filter_modules($fromform, $page_names);
                    $count = filter_count($filteredmodules, $pages);  
                    
                    if(empty($filteredmodules)){
                        //NO PAGE ACTIVITIES WERE SELECTED
                        render_page_without_chart($OUTPUT, $form);
                    }else{
                        //PAGE ACTIVITIES WERE SELECTED
                        $moduleseries = new core\chart_series('Number of students', $count);                        
                        $page_bar_chart = generate_bar_chart($moduleseries, $filteredmodules);
                        
                        echo $OUTPUT->header();
                        
                        $templatecontext = (object)[];
                        
                        echo $OUTPUT->render_from_template('local_activitytracker/main_logger', $templatecontext);
                        
                        $form->display();
                        
                        echo $OUTPUT->render($page_bar_chart);
                        echo $OUTPUT->footer();
                    }
                }
            }else{
                //FORM IS NOT YET SUBMITTED ------- NO PAGE ACTIVITIES COMPLETED               
                if(empty($pages)){
                    \core\notification::info('No pages have been completed');
                    render_page_without_chart($OUTPUT, $form);
                }else{
                    //FORM IS NOT YET SUBMITTED ------- SHOW CHART WITH ALL PAGE ACTIVITIES                    
                    $page_series = array();
                    foreach($pages as $page_count){
                        $page_series[] = $page_count;
                    }
                    $moduleseries = new core\chart_series('Number of students', $page_series);
                    $page_bar_chart = generate_bar_chart($moduleseries, $page_names);
                    
                    echo $OUTPUT->header();
                    
                    $templatecontext = (object)[];
                    
                    echo $OUTPUT->render_from_template('local_activitytracker/main_logger', $templatecontext);
                    
                    $form->display();
                    
                    echo $OUTPUT->render($page_bar_chart);
                    echo $OUTPUT->footer();
                }                
            }
            break;
        case 'quiz':
            $url = new moodle_url('/local/activitytracker/activity_modules_page.php',
                array('type' => $type, 'id' => $courseid));
            $quiz_names = get_module_names_by_course($courseid, $type);
            $form = new modules_form($url, $quiz_names);
            $quizes = local_activitytracker_count_modules_completed($courseid, $type);
            
            if($fromform = $form->get_submitted_data()){
                //FORM WAS SUBMITTED
                
                if($fromform->checkbox_controller1 == 1){
                    //SHOW UNFILTERED QUIZ ACTIVITIES                    
                    
                    show_unfiltered_modules($quiz_names);

                }else{
                    //SHOW FILTERED QUIZ ACTIVITIES
                    
                    $filteredmodules = filter_modules($fromform, $quiz_names);
                    $count = filter_count($filteredmodules, $quizes);
                    
                    if(empty($filteredmodules)){
                        //NO QUIZ ACTIVITIES WERE SELECTED
                        render_page_without_chart($OUTPUT, $form);
                    }else{
                        //QUIZ ACTIVITIES WERE SELECTED
                        $moduleseries = new core\chart_series('Number of students', $count);                        
                        $quiz_bar_chart = generate_bar_chart($moduleseries, $filteredmodules);
                        
                        echo $OUTPUT->header();
                        
                        $templatecontext = (object)[];
                        
                        echo $OUTPUT->render_from_template('local_activitytracker/main_logger', $templatecontext);
                        
                        $form->display();
                        
                        echo $OUTPUT->render($quiz_bar_chart);
                        echo $OUTPUT->footer();
                    }
                }
            }else{
                //FORM IS NOT YET SUBMITTED ------- SHOW CHART WITH ALL QUIZ ACTIVITIES                
                if(empty($quizes)){
                    \core\notification::info('No quizes have been completed');
                    render_page_without_chart($OUTPUT, $form);
                }else{
                    $quiz_series = array();
                    foreach($quizes as $quiz_count){
                        $quiz_series[] = $quiz_count;
                    }
                    
                    $moduleseries = new core\chart_series('Number of students', $quiz_series);
                    
                    $quiz_bar_chart = generate_bar_chart($moduleseries, $quiz_names);
                    
                    echo $OUTPUT->header();
                    
                    $templatecontext = (object)[];
                    
                    echo $OUTPUT->render_from_template('local_activitytracker/main_logger', $templatecontext);
                    
                    $form->display();
                    
                    echo $OUTPUT->render($quiz_bar_chart);
                    echo $OUTPUT->footer();
                }               
            }          
            break;
    }
}

function assignments_chart(&$courseid, &$type){
    $url = new moodle_url('/local/activitytracker/activity_modules_page.php', 
    array('type' => $type, 'id' => $courseid));

    $assignment_names = get_module_names_by_course($courseid, $type);            
    $form = new modules_form($url, $assignment_names);

    $assignments = local_activitytracker_count_modules_completed($courseid, $type);

    handle_form($form, $assignments, $assignment_names);
}

function handle_form(&$form, &$assignments, &$assignment_names){
    if($fromform = $form->get_submitted_data()){   
        //FORM WAS SUBMITTED
        
        if($fromform->checkbox_controller1 != 1){    
            //SHOW FILTERED ASSIGNMENTS                    
            $filteredmodules = filter_modules($fromform, $assignment_names);
            $count = filter_count($filteredmodules, $assignments);                    
            
            if(!empty($filteredmodules)){
                //ASSIGNMENTS WERE SELECTED              
                render_page_with_chart($form, $filteredmodules, $count);
        
            }else{
                //NO ASSIGNMENT WAS SELECTED                                
                render_page_without_chart($OUTPUT, $form);
            }
        }else{ 
             //SHOW UNFILTERED ASSIGNMENTS                   
             show_unfiltered_modules($form, $assignment_names);
        }               
    }else{      
        if(empty($assignments)){
            //FORM IS NOT YET SUBMITTED ------- NO ASSIGNS COMPLETED
            \core\notification::info('No assignments have been completed');
        
            render_page_without_chart($OUTPUT, $form);
        }else{
            //FORM IS NOT YET SUBMITTED ------- SHOW CHART WITH ALL ASSIGNMENTS                    
            show_unfiltered_modules($form, $assignment_names);
        }               
    }
}

function show_unfiltered_modules(&$form, &$module_names){
    //Create and fill array with modules.
    $modules = array();                    
    foreach($module as $module_count){
        $modules[] = $module_count;
    }      

    render_page_with_chart($form, $module_names, $modules);
}

function render_page_without_chart(&$OUTPUT, &$form){
    echo $OUTPUT->header();  

    $templatecontext = (object)[]; 

    echo $OUTPUT->render_from_template('local_activitytracker/main_logger', $templatecontext);   

    $form->display();

    echo $OUTPUT->footer();
}

function render_page_with_chart(&$form, &$module_names, &$modules){
    $module_chart_series = new core\chart_series('Number of students', $modules);                        
    $barchart = generate_bar_chart($module_chart_series, $module_names);

    echo $OUTPUT->header();   

    $templatecontext = (object)[]; 

    echo $OUTPUT->render_from_template('local_activitytracker/main_logger', $templatecontext);    

    $form->display();

    echo $OUTPUT->render($barchart);
    echo $OUTPUT->footer();
}

/*
 * Filters the modules not checked by the user.
 * @param $form_data is the array containing the values of the checkboxes selected.
 * @param $module_names is an array containing the names of the modules in the form.
 */
function filter_modules($form_data, &$module_names){
    $filtered_modules = array();   
    
    $index = 0;
    foreach($form_data as $module){
        if($module == '1')     //element must be an integer with value of 1
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
function filter_count(&$filtered_modules, &$module_counts){
    
    $module_objects = array();
    $count = array();    
    $index = 0;
    
    $mod_name_keys = array_keys($module_counts);
    foreach($module_counts as $module){
        $module_objects[] = (object)[$mod_name_keys[$index] => $module];
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

function generate_bar_chart(&$bar_series, &$bar_labels){
    
    //Bar Chart - setup
    $bar_chart = new core\chart_bar();
    $bar_chart->set_title('Activity Completion by Amount of Students');
    $bar_chart->add_series($bar_series);
    $bar_chart->set_labels($bar_labels);
    $bar_chart->set_legend_options(['position' => 'top']);  // Change legend position to top side.
    
    $yaxis = $bar_chart->get_yaxis(0, true);
    $yaxis->set_stepsize(2);
    
    return $bar_chart;
}