<?php

class block_courses_overview extends block_base 
{
    // data to show: 
    private $showcolumngrades = false;
    private $showrowgradableitems = false;
    
    
    public function init() 
    {
        $this->title = get_string('coursesoverviewtitle', 'block_courses_overview');
    }
  
  
  
    public function get_content() 
    {
        // get_content() is called multiple times, so only continue to build the content if it hasnt been done already
        if ($this->content !== null) 
        {
          return $this->content;
        }
        
        // check custom configs, to see which data to show
        if (! empty($this->config->column1)) 
        {
            if ($this->config->column1 != 'unchecked')
            {
                $this->showcolumngrades = true;
            }
        }
        if (! empty($this->config->column2)) 
        {
            if ($this->config->column1 != 'unchecked')
            {
                $this->showrowgradableitems = true;
            }
        }
        
        // make overview according to custom configs
        $overview = $this->block_courses_overview_make_overview();
        
        // display content        
        $this->content          = new stdClass;
        $this->content->text    = $overview;
        ///$this->content->footer  = '';
        return $this->content;
    }
    
    
    
    private function block_courses_overview_make_overview()
    {
        $datanolinks = $this->block_courses_overview_make_rows();
        $datalinks = $this->block_courses_overview_make_links($datanolinks);
        $overview = $this->block_courses_overview_add_html($datalinks);
        return $overview;
    }
    
    
    
    // get the information wanted for the overview -> depends on settings
    private function block_courses_overview_make_rows()
    {
        global $USER, $CFG;
        require_once($CFG->dirroot.'/lib/gradelib.php');
        require_once($CFG->dirroot.'/lib/grade/grade_item.php');
        require_once($CFG->dirroot.'/lib/grade/grade_grade.php');
        require_once($CFG->dirroot.'/grade/querylib.php'); 
        
        // get my courses
        $mycourses = enrol_get_my_courses('id, shortname, modinfo', 'visible DESC,sortorder ASC');//, $courses_limit)
        print_object($mycourses);
        print_object(unserialize($mycourses[7]->modinfo));
        // start the array which will hold all overview data
        $data = array();
        foreach($mycourses as $mc)
        {
            try
            {
                // ROW: course
                // row array, which will contain global course data
                $mycourseoverview = array();// later wil ik array_unshift gebruiken dus liever een array dan een object, dus geen: $mycourseoverview = new stdClass;
                // get my courses' fullname and id
                // id and fullname values are given a key when added to the array
                // array items with keys contain values needed to make links
                // after another function call the fullname and id will be replaced with a link to the course
                $mycourseoverview['id'] = $mc->id;// id en fullname meegeven om links te kunnen maken van de course names. deze met key meegeven om handig op te kunnen halen
                $mycourseoverview['fullname'] = $mc->fullname;
                if($this->showcolumngrades)
                {
                    // get the grade i got for each course
                    // add it to the global course data
                    $mycourseoverview[] = $this->block_courses_overview_add_grades($USER->id, $mc->id);
                }
                // add the global course data to the complete collection of data
                $data[] = $mycourseoverview;
                
                // ROW: gradable course items
                if($this->showrowgradableitems)
                {
                    
                    /* $mods = array();
                    $modnames = array();
                    $modnamesplural = array();
                    $modnamesused = array();
                    // the next function passes these arrays by reference, so theyll get filled by this call
                    get_all_mods($mc->id, $mods, $modnames, $modnamesplural, $modnamesused);
                     *//* echo $mods[0]['modname'];
                    echo $mods[0]['url']->path;
                    echo $mods[0]['name'];
                    echo $mods[0]['id']; */
                    //print_object($mods[]['instances']['scorm'][4]);
                    require_once($CFG->dirroot.'/lib/modinfolib.php');
                    require_once($CFG->dirroot.'/lib/conditionlib.php');
                    ///$cmi = new course_modinfo($mc, $USER->id);
                    //[modinfo:cm_info:private] => course_modinfo Object
                    ///$blaid = $mc->id;
                    ///echo $blaid . ' - ';
                    //$bla = $cmi->get_cm($blaid);
                    ///print_object($cmi);//->get_cm($mc->id));//->get_modinfo()->get_url());
                    
                    $cmi = get_fast_modinfo($mc, $USER->id);
                    echo('<br /><br />');
                    /* $cms = $cmi->get_cms();
                    $cms41 = $cms[41]; */
                    $mis = $cmi->get_instances();
                    foreach($mis as $miss)
                    {
                        //$misss = $miss->get_instances();
                        foreach($miss as $misss)
                        {
                            $murl = $misss->get_url();
                            //echo $murl->out();
                            //echo $misss->obtain_view_data();
                            //print_object($misss);
                            if($misss->completion != 0)
                            {
                                echo(   
                                        html_writer::link   
                                        (
                                            $murl,
                                            $misss->name,
                                            array('title' => $misss->name)
                                        )
                                    );
                            }
                        }
                        //print_r($miss);
                    }
                    //print_object('course is object?:' . $cm);
                    
                    // get all gradable items for each course
                    $gradeitems = array();
                    $gradeitems = grade_item::fetch_all(array('courseid'=>$mc->id));
                    //print_object($gradeitems);
                    foreach($gradeitems as $gi)
                    {
                        ///print_object($gi);
                        // get my grade object for each gradable item
                        $gradeditem = $gi->get_grade($USER->id);
                        if($gi->itemtype != 'course')
                        {
                            // row array, which will contain gradable course item data
                            $mycourseitemoverview = array();
                            // get my grade for the gradable item out of the gradable object
                            $longgrade = $gradeditem->finalgrade;
                            if(! empty($longgrade))
                            {
                                // make the grade into 2 decimals (or not 2, if configured otherwise elsewhere)
                                $finalgrade = grade_format_gradevalue($longgrade, $gi, true);
                            }
                            else
                            {
                                $finalgrade = '-';
                            }
                            // add gradable item name and grade for it to array, and add that array to data array
                            ///$mycourseitemoverview[] = $gi->itemname;
                            // we need entire course to make links for each gradable item
                            //$mycourseitemoverview['modname'] = ;
                            //$mycourseitemoverview['modid'] = ;
                            $mycourseitemoverview[] = $finalgrade;
                            $data[] = $mycourseitemoverview;
                        } 
                    }                     
                }                 
            }
            catch(exception $e)
            {
                return "failed to get data!";
            }
        } 
        return $data;
    }
    
    
    
    private function block_courses_overview_add_grades($userid, $courseid)
    {
        return grade_get_course_grade($userid, $courseid)->str_grade;
    }
    
    
    
    private function block_courses_overview_add_gradeable_items()
    {
    
    }
    
    
    
    // surround data and links with html -> put in divtable
    private function block_courses_overview_add_html($data)
    {
        $divtable = html_writer::start_tag('div', array('class' => 'data overview table')); 
        if ($this->showcolumngrades)
        {
            //language files gebruiken?
            //of uit $data halen, en dan dus eerst daadwerkelijk in $data zetten?
            //bij rows en columns first/last toevoegen aan firsts en lasts?
            $divtable .= html_writer::start_tag('div', array('class' => 'head entirerow row1'));
            $divtable .= html_writer::tag('div', 'course', array('class' => 'head col row1 col1')); 
            $divtable .= html_writer::tag('div', 'grade', array('class' => 'head col row1 col2')); 
            $divtable .= html_writer::end_tag('div');//head entirerow row1 
        }
        else
        {
            $divtable .= html_writer::start_tag('div', array('class' => 'head entirerow row1'));
            $divtable .= html_writer::tag('div', 'course', array('class' => 'head col row1 col1')); 
            $divtable .= html_writer::end_tag('div');//head entirerow row1 
        }
        $l = count($data);
        for ($i = 0; $i < $l; $i++)
        {
            $divtable .= html_writer::start_tag('div', array('class' => 'coursedata entirerow row' . ($i + 2) ));
            $l2 = count($data[$i]);
            //print_object($data);
            for ($i2 = 0; $i2 < $l2; $i2++)
            {
                $divtable .= html_writer::tag('div', $data[$i][$i2], array('class' => 'coursedata col row' . ($i + 2) . ' col' . ($i2 + 1) )); 
            }
            $divtable .= html_writer::end_tag('div');//coursedata entirerow rowx
        }        
        $divtable .= html_writer::empty_tag('hr');
        $divtable .= html_writer::end_tag('div');//data overview table
        
        return $divtable;
    }
    
    
    
    // switch id + fullname in $data to fullnamelink, get back the adjusted $data
    // make some other text into links as well
    private function block_courses_overview_make_links($data)
    {
        global $DB, $CFG, $USER, $OUTPUT;
        $l = count($data);
        for($i = 0; $i < $l; $i++)
        {
            // make link to course - courseid and course fullname are needed
            if(! empty($data[$i]['id']) && ! empty($data[$i]['fullname']))
            {
                try
                {
                    $fullnamelink =
                        html_writer::link   
                        (
                            new moodle_url('/course/view.php', array('id' => $data[$i]['id'])),
                            $data[$i]['fullname'],
                            array('title' => $data[$i]['fullname'])
                        );
                    // remove the items in the array that we dont need anymore
                    unset($data[$i]['id']);
                    unset($data[$i]['fullname']);
                    // make the link the first item in the array
                    array_unshift($data[$i], $fullnamelink); // zet de link als eerste in $data - array_unshift doet een prepend ipv append
                }
                catch(exception $e)
                {
                    return "failed to make links!";
                }
            }
            // make links to gradable course items - complete gradable item is needed
            if(! empty($data[$i]['gradableitem']))
            {
                try
                {
                    unset($data[$i]['gradableitem']);
                }
                catch(exception $e)
                {
                    return "failed to make links!";
                }
            }
        } 
        return $data;
    }
} 