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
                $mycourseoverview = new stdClass;
                $mycourseoverview->fullname = $mc->fullname;///$mycourses[$mc->id]->fullname;
                
                if($showgrades)
                {
                    $mycourseoverview->coursegrade = grade_get_course_grade($USER->id, $mc->id)->str_grade;
                }
                
                $data[] = $mycourseoverview;
            }
            catch(exception $e)
            {
                return "fail!";
            }
        } 
        return $data;
    }
    
    
    
    private function block_courses_overview_html($data, $showgrades)
    {
        $table = new html_table();
        if($showgrades)
        {
            $table->head  = array('course','grade'); ///array($data[7]->id, $data[7]->coursegrade);
        }
        else
        {
            $table->head  = array('course');
        }
        ///$table->size  = array('70%', '20%', '10%');
        ///$table->align = array('left', 'center', 'center');
        $table->attributes['class'] = 'data overview table'; ///'scaletable localscales generaltable';
        $table->data  = $data;///array($data[]->id, $data[]->coursegrade); ///$data;///array($data->name, $data->coursegrade);
        ///print_object($data);
        return html_writer::table($table);
    }
} 