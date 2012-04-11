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
        if (! empty($this->config->column1)) 
        {
            if ($this->config->column1 != 'unchecked')
            {
                $showgrades = true;
            }
        }
        
        // make overview according to custom configs
        $overview = $this->block_courses_overview_make($showgrades);
        
        // display content        
        $this->content          = new stdClass;
        $this->content->text    = '<br/>' . $overview;///'The content of our courses overview block!';
        ///$this->content->footer  = 'Footer here...';
        return $this->content;
    }
    
    
    
    private function block_courses_overview_make($showgrades)
    {
        $data = $this->block_courses_overview_get_data($showgrades);
        return $this->block_courses_overview_html($data, $showgrades);
    }
    
    
    
    private function block_courses_overview_get_data($showgrades)
    {
        global $USER, $CFG;
        require_once($CFG->dirroot.'/lib/gradelib.php');
        require_once($CFG->dirroot.'/lib/grade/grade_item.php');
        require_once($CFG->dirroot.'/lib/grade/grade_grade.php');
        require_once($CFG->dirroot.'/grade/querylib.php'); 
        
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
                    $mycourseoverview[] = grade_get_course_grade($USER->id, $mc->id)->str_grade;
                }
                
                $data[] = $mycourseoverview;
            }
            catch(exception $e)
            {
                return "failed to get data!";
            }
        } 
        return $data;
    }
    
    
    
    private function block_courses_overview_html($data, $showgrades)
    {
        // switch id + fullname in $data to fullnamelink, get back the adjusted $data
        $data = $this->block_courses_overview_link($data);
        $divtable = '<div class="data overview table">';
        if ($showgrades)
        {
            //language files gebruiken?
            //of uit $data halen, en dan dus eerst daadwerkelijk in $data zetten?
            //bij rows en columns first/last toevoegen aan firsts en lasts?
            $divtable .= '<div class="head entirerow row1">';
            $divtable .= '<div class="head row1 col1">course</div>'; 
            $divtable .= '<div class="head row1 col2">grade</div>'; 
            $divtable .= '</div>';//head entirerow row1 
        }
        else
        {
            $divtable .= '<div class="head entirerow row1">';
            $divtable .= '<div class="head row1 col1">course</div>'; 
            $divtable .= '</div>';//head entirerow row1 
        }
        $l = count($data);
        for ($i = 0; $i < $l; $i++)
        {
            $divtable .= '<div class="coursedata entirerow row' . ($i + 2) . '">';
            $l2 = count($data[$i]);
            for ($i2 = 0; $i2 < $l2; $i2++)
            {
                $divtable .= '<div class="coursedata row' . ($i + 2) . ' col' . ($i2 + 1) . '">' . $data[$i][$i2] . '</div>'; 
            }
            $divtable .= '</div>';//coursedata entirerow rowx
        }        
        $divtable .= '</div>';//data overview table
        return $divtable;
    }
    
    
    
    private function block_courses_overview_link($data)
    {
        $l = count($data);
        for($i = 0; $i < $l; $i++)
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
        return $data;
    }
} 