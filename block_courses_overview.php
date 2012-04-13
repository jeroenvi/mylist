<?php

class block_courses_overview extends block_base 
{
    
    
    
    public function init() 
    {
        $this->title = get_string('coursesoverviewtitle', 'block_courses_overview');
    }
  
  
  
    public function get_content() 
    {
        // get_content() is called multiple times, so only continue to build the content if its not been done already
        if ($this->content !== null) 
        {
          return $this->content;
        }
        
        // check custom configs
        $showgrades = false;
        $showgradableitems = false;
        if (! empty($this->config->column1)) 
        {
            if ($this->config->column1 != 'unchecked')
            {
                $showgrades = true;
            }
        }
        if (! empty($this->config->column2)) 
        {
            if ($this->config->column1 != 'unchecked')
            {
                $showgradableitems = true;
            }
        }
        
        // make overview according to custom configs
        $overview = $this->block_courses_overview_make($showgrades, $showgradableitems);
        
        // display content        
        $this->content          = new stdClass;
        $this->content->text    = $overview;///'The content of our courses overview block!';
        ///$this->content->footer  = 'Footer here...';
        return $this->content;
    }
    
    
    
    private function block_courses_overview_make($showgrades, $showgradableitems)
    {
        $data = $this->block_courses_overview_get_data($showgrades, $showgradableitems);
        return $this->block_courses_overview_html($data, $showgrades, $showgradableitems);
    }
    
    
    
    private function block_courses_overview_get_data($showgrades, $showgradableitems)
    {
        global $USER, $CFG;
        require_once($CFG->dirroot.'/lib/gradelib.php');
        require_once($CFG->dirroot.'/lib/grade/grade_item.php');
        require_once($CFG->dirroot.'/lib/grade/grade_grade.php');
        require_once($CFG->dirroot.'/grade/querylib.php'); 
        
        // get my courses' fullname and id
        $mycourses = enrol_get_my_courses();
        $data = array();
        foreach($mycourses as $mc)
        {
            try
            {
                $mycourseoverview = array();// later wil ik array_unshift gebruiken dus liever een array dan een object, dus geen: $mycourseoverview = new stdClass;
                $mycourseoverview['id'] = $mc->id;// id en fullname meegeven om links te kunnen maken van de course names. deze met key meegeven om handig op te kunnen halen
                $mycourseoverview['fullname'] = $mc->fullname;
                if($showgrades)
                {
                    // get the grade i got for each course
                    $mycourseoverview[] = grade_get_course_grade($USER->id, $mc->id)->str_grade;
                }
                $data[] = $mycourseoverview;
                if($showgradableitems)
                {
                    // get all gradable items for each course
                    $gradeitems = grade_item::fetch_all(array('courseid'=>$mc->id));
                    foreach($gradeitems as $gi)
                    {
                        // get my grade object for each gradable item
                        $gradeditem = $gi->get_grade($USER->id);
                        if($gi->itemtype != 'course')
                        {
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
                            $mycourseitemoverview = array();
                            $mycourseitemoverview[] = $gi->itemname;
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
    
    
    
    private function block_courses_overview_html($data, $showgrades, $showgradableitems)
    {
        // switch id + fullname in $data to fullnamelink, get back the adjusted $data
        $data = $this->block_courses_overview_link($data);
        $divtable = html_writer::start_tag('div', array('class' => 'data overview table')); 
        if ($showgrades)
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
    
    
    
    private function block_courses_overview_link($data)
    {
        $l = count($data);
        for($i = 0; $i < $l; $i++)
        {
            if(! empty($data[$i]['id']))
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
                    unset($data[$i]['id']);
                    unset($data[$i]['fullname']);
                    // zet de link als eerste in $data - array_unshift doet een prepend ipv append
                    array_unshift($data[$i], $fullnamelink);
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