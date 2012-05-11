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

// credit for the checkbox icon to Contributors to the Ubuntu documentation wiki
// https://help.ubuntu.com/community/License
// The material on this wiki is released under the Creative Commons Attribution-ShareAlike 3.0 License.
// You are therefore free to share and adapt the material, provided that you do so under the same or similar license, and that you give credit to the original authors.
// The full text of the license can be found on the Creative Commons website.
// When attributing, it is sufficient to refer to the authors of the wiki as a whole rather than individually, so "Contributors to the Ubuntu documentation wiki", although you should check the relevant page in case any specific attributions are required.
// For information on why we have chosen this license, please see WikiLicensing.

/**
* mylist: A table-like overview of my courses, with grades and other additional information.
* Most logical placement would be at the /my page, as a block in the middle column.
* Available languages: English and Dutch.
*
* @package    block
* @subpackage my_courses
* @copyright  2012 Jeroen
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

class block_my_courses extends block_base 
{
    // data to show: 
    private $showrowgradeableitems = true;
    private $showrowrequireditems = true;
    private $showcolumngrades = true;
    private $showcolumnrequirements = true;
    private $showcolumnprogress = true;
    
    public function init() 
    {
        $this->title = html_writer::tag('span', get_string('coursesoverviewtitle', 'block_my_courses'), array('class' => 'mycoursesoverview'));
    }
  
  
  
    public function get_content() 
    {
        // get_content() is called multiple times, so only continue to build the content if it hasnt been done already
        if ($this->content !== null) 
        {
            return $this->content;
        }
                
        // check custom configs, to see which data to show
        if ($this->config->cfg_row_gradeableitems != 1)//'checked')
        {
            $this->showrowgradeableitems = false;
        }
        if ($this->config->cfg_row_requireditems != 1)
        {
            $this->showrowrequireditems = false;
        }
        if ($this->config->cfg_col_grade != 1)//'checked')
        {
            $this->showcolumngrades = false;
        }
        if ($this->config->cfg_col_requirements != 1)
        {
            $this->showcolumnrequirements = false;
        }
        if ($this->config->cfg_col_progress != 1)
        {
            $this->showcolumnprogress = false;
        }
        
        
        // make overview according to custom configs
        $overview = $this->block_my_courses_make_overview();
        
        // display content        
        $this->content          = new stdClass;
        $this->content->text    = $overview;
        ///$this->content->footer  = '';
        return $this->content;
    }
    
    
    
    /**
    * Helper function: 
    * first call is to a function which gathers all necessary data, 
    * second call is to a function that makes some of the data (such as courses' names) into clickable links
    * third call is to a function that puts all the data in a divtable.
    *
    * @return String $overview
    */
    private function block_my_courses_make_overview()
    {
        $datanolinks = $this->block_my_courses_make_rows(); //make_rows function will call make_column_xxx()-like functions
        $datalinks = $this->block_my_courses_make_links($datanolinks);
        $overview = $this->block_my_courses_add_html($datalinks);
        return $overview;
    }
    
    
    
    /**
    * Function that gets the information that is wanted for the overview.
    * The settings (suchs as $showcolumngrades) determine what information will be gathered.
    *
    * @return Array $data ('raw' data: some text still needs to be transformed to hyperlinks)
    */ 
    private function block_my_courses_make_rows()
    {
        global $USER, $CFG;
        require_once($CFG->dirroot.'/lib/gradelib.php');
        require_once($CFG->dirroot.'/lib/grade/grade_item.php');
        require_once($CFG->dirroot.'/lib/grade/grade_grade.php');
        require_once($CFG->dirroot.'/grade/querylib.php'); 
        
        // get my courses -> also includes modinfo, but not the modname and grade. 
        // modinfo has to be unserialized, this will make the string into a usable array
        $mycourses = array();
        $mycourses = enrol_get_my_courses('modinfo');
                
        // start the array which will hold all overview data
        $data = array();
        foreach($mycourses as $mc)
        {
            // ROW: course
            $data = $this->block_my_courses_add_row_courses($mc, $data);                     
           
            
            // ROW: gradable course items
            if($this->showrowgradeableitems)
            {
                $data = $this->block_my_courses_add_row_gradeable_items($mc, $data);                     
            } 

            
            // ROW: required course items
            if($this->showrowrequireditems)
            {
                $data = $this->block_my_courses_add_row_required_items($mc, $data);
            }
        } 
        return $data;
    }
    
    
    
    /**
    * Column function
    * Function that adds grade information to each row.
    * Grade information is a field in a row.
    * All grade information together can also be considered to be a column 
    *
    * @param int $userid
    * @param int $courseid
    * @return float $coursegrade or $itemgrade
    */
    private function block_my_courses_add_column_grades($userid = null, $courseid = null, $gradeitem = null, $course = null)
    {
        // check for which rows we are getting grades, 
        // by checking which parameters were given when calling add_column_grades
        
        // we want the grades for the courses:
        if($userid != null && $courseid != null)
        {
            // normally the function below could return an array.
            // but because $courseid is not an array and not empty,
            // a reset on the array is returned, returning the first object and of that, the first variable,
            // which is $grade_grade->finalgrade. could also return false.
            // grade_grade's finalgrade attribute is a float
            $coursegrade = grade_get_course_grade($userid, $courseid)->str_grade;// QUERY
            return $coursegrade;
        }
        
        // we want the grades for the gradeableitems, or
        // we want the grade for the requireditems: 
        // note: required items with grades are gradeable items
        // when gradeable items are shown already then gradeable items will contain the gradeable required items too
        // in the last case, add_row_required_items will not call add_column_grades
        // but when gradebale items are not shown, add_row_required_items will call this function,
        // but just for items that are both gradeable and required
        if($userid != null && $gradeitem != null)
        {
            // get my grade object (grade_grade) for each gradable item // QUERY
            $gradeditem = $gradeitem->get_grade($userid);
            // add grade for gradeableitem to array,
            // get my grade for the gradable item out of the gradable object
            $longgrade = $gradeditem->finalgrade;
            if(! empty($longgrade))
            {
                // make the grade into 2 decimals (or not 2, if configured otherwise elsewhere) 
                $itemgrade = grade_format_gradevalue($longgrade, $gradeitem, true);
            }
            else
            {
                $itemgrade = '-';
            }
            return $itemgrade;
        }
    }
    
    
    
    /**
    * Column function
    * Function that adds requirement information to each row.
    * Requirement information is a field in a row.
    * All requirement information together can also be considered to be a column 
    *
    * @return String $requirement
    */
    private function block_my_courses_add_column_requirements($requirement, $completion, $coursegrade = false)
    {
        global $CFG; 
        if($coursegrade)
        {
            $requirement = get_string('coursegrade', 'block_my_courses') . $requirement;
        }
        if($completion->is_complete() == 1)
        {
            $url = $CFG->wwwroot . '/blocks/my_courses/checkboxxs.png';
            
            $requirement = /* html_writer::tag('div', 'v', array(
                'class' => 'requirement requireditem achieved yes', 
                'title' => $requirement)); */
                /* html_writer::checkbox   (
                                            'requirement requireditem achieved yes',
                                            1,
                                            true,
                                            '',
                                            array   (
                                                        'class' => 'requirement requireditem achieved yes',
                                                        'onclick' => 'return false;', //JAVASCRIPT!
                                                        'title' => $requirement
                                                    )
                                        ); */
                                       
                            
                            html_writer::empty_tag('img', array('src' => $url, 'alt' => 'checked box', 'title' => $requirement, 'class' => 'requirement requireditem achieved yes'));
        }
        else
        {
            $url = $CFG->wwwroot . '/blocks/my_courses/checkboxuncheckedxs.png';
            $requirement = html_writer::empty_tag('img', array('src' => $url, 'alt' => 'unchecked box', 'title' => $requirement, 'class' => 'requirement requireditem achieved no'));
        }
        return $requirement;
    }
    
    
    
    
    /**
    * Column function
    * Function that adds progress bar to course row.
    *
    * @return String $progress
    */
    private function block_my_courses_add_column_progress($mc, $userid)
    {
        $info = new completion_info($mc);
        $completions = $info->get_completions($userid);
        $completed = array();
        foreach($completions as $completion)
        {
            $iscomplete = $completion->is_complete();
            if($iscomplete == 1)
            {
                $completed[] = $iscomplete;
            }
        }
        $total = count($completions);
        
        if($total == 0)
        {
            $progress = html_writer::tag('span', get_string('noprogressset', 'block_my_courses'), array('class' => 'noinfo', 'title' => get_string('noprogress', 'block_my_courses')));
            /* $progress = html_writer::start_tag('div', array(
                'class' => 'progressbar noprogress', 
                'title' => get_string('noprogress', 'block_my_courses')));
            $progress .= html_writer::tag('div', '', array(
                'class' => 'progress  noprogress', 
                'style' => 'width: 100%; 
                            height: 100%'));  
            //$progress .= html_writer::end_tag('div');//progressbar */
        }
        else
        {
            $numbercompleted = count($completed);
            $percentage = 0;
            if($numbercompleted > 0)
            {
                $percentage = ($numbercompleted/$total) * 100;
            }
            $title = get_string('progress', 'block_my_courses') . $numbercompleted . ' / ' . $total;
            ///$percentageinverse = 100 - $percentage;
            ///$bgcolor = 'rgb(' . round(2.55 * $percentageinverse) . ', ' . round(2.55 * $percentage) . ', 0)';
            // add class attribute to give progress right colors with css
            $widthprogress = '';
            $full = '';
            switch (true)//$percentage)
            {
                case ($percentage == 100):
                    $widthprogress = 'eq100';
                    $full = 'full';
                    break;
                case ($percentage < 25):
                    $widthprogress = 'st25';
                    break;
                case ($percentage < 50):
                    $widthprogress = 'st50';
                    break;
                case ($percentage < 75):
                    $widthprogress = 'st75';
                    break;
                case ($percentage > 75):
                    $widthprogress = 'gt75';
                    break;
            }
            
            $progress = html_writer::start_tag('div', array(
                'class' => 'progressbar '. $full, 
                'title' => $title));
            $progress .= html_writer::tag('div', '', array(
                'class' => 'progress ' . $widthprogress, 
                'style' => 'width: ' . $percentage . '%; 
                            height: 100%'));   
            $progress .= html_writer::end_tag('div');//progressbar                            
        }
        return $progress;
    }
    
    
    
    /**
    * Row function
    * Function that adds gradeable items as rows
    *
    * @param stdClass Object course
    * @param Array $data 
    * @return Array $data (with course info)
    */
    private function block_my_courses_add_row_courses($mc, $data)
    {
        // row array, which will contain global course data
        global $USER;
        $mycourseoverview = array();
        // get my courses' fullname and id
        // id and fullname values are given a key when added to the array
        // array items with keys contain values needed to make links
        // after another function call the fullname and id will be replaced with a link to the course. 
        // this link will be added to the array as a value without a key, so i can iterate over the array later
        $mycourseoverview['id'] = $mc->id;// id en fullname meegeven om links te kunnen maken van de course names. deze met key meegeven om handig op te kunnen halen
        $mycourseoverview['fullname'] = $mc->fullname;
        // courses with visible attribute set to 0 are only returned by enrol_get_my_courses if role is teacher
        // the hidden course needs to be displayed differently (e.g. see through / greyed out a little)
        if($mc->visible == 0)
        {
            $mycourseoverview['ghost'] = true;
            $mycourseoverview['courseishidden'] = get_string('courseishidden', 'block_my_courses');
        }
        if($this->showcolumngrades)
        {
            // get the grade i got for each course
            // add it to the global course data
            $mycourseoverview[] = $this->block_my_courses_add_column_grades($USER->id, $mc->id);
        }
        if($this->showcolumnrequirements)
        {
            $notrequired = true;
            $info = new completion_info($mc);
            $completions = $info->get_completions($USER->id);
            foreach($completions as $completion)
            {
                $criteria = $completion->get_criteria();
                // check to see if a minimum course grade is required (id = 6)
                if($criteria->criteriatype == 6)
                {
                    $details = $criteria->get_details($completion);
                    $requirement = $this->block_my_courses_add_column_requirements($details['requirement'], $completion, true);
                    $mycourseoverview[] = $requirement;
                    $notrequired = false;
                }
            }
            if($notrequired)
            {
                $mycourseoverview[] = html_writer::tag('span', get_string('notrequired', 'block_my_courses'), array('class' => 'noinfo'));//'&nbsp;';
            }
        }
        if($this->showcolumnprogress)
        {
            $mycourseoverview[] = $this->block_my_courses_add_column_progress($mc, $USER->id);
        }
        // add the global course data to the complete collection of data
        $data[] = $mycourseoverview;
        return $data;
    }
    
    
    
    /**
    * Row function
    * Function that adds gradeable items as rows
    *
    * @param stdClass Object course
    * @param Array $data 
    * @return Array $data (with gradable item info)
    */
    private function block_my_courses_add_row_gradeable_items($mc, $data)
    {
        // get all gradable items (grade_item objects) for each course
        global $USER;
        $gradeitems = array();
        $gradeitems = grade_item::fetch_all(array('courseid'=>$mc->id));
        // some hassle to get the coursemoduleid and mod's name
        // we need this later to know which coursemodule id matches which grade
        $coursemodinfoarray = unserialize($mc->modinfo);
        foreach($gradeitems as $gi)
        {
            if($gi->itemtype != 'course')
            {
                // get the coursemoduleid and mod. modinfo has it, but it needs to be connected to the right grade_item
                foreach($coursemodinfoarray as $cmi)
                {
                    // todo: check if these checks are ALWAYS enough and correct
                    if($cmi->id == $gi->iteminstance && $cmi->mod == $gi->itemmodule)
                    {
                        // fast_mod_info has attribute uservisible
                        $fastmodinfo = get_fast_modinfo($mc);
                        // get the fastmodinfo for the module were currently working with
                        $coursemodule = $fastmodinfo->get_cm($cmi->cm);
                                                
                        // dont display hidden items. 
                        // normaal: uservisible == 1
                        // greyed out: showavailability == 1 && uservisible != 1 (give them availability info)
                        // hidden: the rest (showavailability == 0 && uservisible != 1)
                        
                        // not hidden:
                        if(! ($coursemodule->showavailability == 0 && $coursemodule->uservisible != 1))
                        {
                            // row array, which will contain gradable course item data
                            $mycourseitemoverview = array();
                            // add gradable item name, mod (e.q. forum/scorm), and coursemoduleid to create gradableitem link,
                            // and later add that array to data array
                            $mycourseitemoverview['gradableitemmyname'] = $gi->itemname;
                            $mycourseitemoverview['gradableitemid'] = $cmi->cm;
                            $mycourseitemoverview['gradableitemmod'] = $cmi->mod;
                            
                            // greyed out / disabled:
                            if($coursemodule->showavailability == 1 && $coursemodule->uservisible != 1)
                            {
                                $mycourseitemoverview['greyedout'] = true;
                                $mycourseitemoverview['availabilityinfo'] = $coursemodule->availableinfo; 
                            }
                            if($this->showcolumngrades)
                            {
                                $grade = $this->block_my_courses_add_column_grades($USER->id, null, $gi);
                                $mycourseitemoverview[] = $grade;
                            }
                            if($this->showcolumnrequirements)
                            {
                                // which of the items that are gradeable are also required?
                                $info = new completion_info($mc);
                                $completions = $info->get_completions($USER->id);
                                $hasrequirement = false;
                                foreach($completions as $completion)
                                {
                                    $criteria = $completion->get_criteria();
                                    $details = $criteria->get_details($completion);
                                    // add requirement column info to each gradeable item that is also required 
                                    if($criteria instanceof completion_criteria_activity && $cmi->cm == $criteria->moduleinstance)
                                    {
                                        $hasrequirement = true;
                                        $requirement = $this->block_my_courses_add_column_requirements($details['requirement'], $completion);
                                        $mycourseitemoverview[] = $requirement;
                                    }
                                }
                                if(! $hasrequirement)
                                {
                                    $mycourseitemoverview[] = html_writer::tag('span', get_string('notrequired', 'block_my_courses'), array('class' => 'noinfo'));//'&nbsp;';// or min-width
                                }
                            }
                            if($this->showcolumnprogress)
                            {
                                $mycourseitemoverview[] = '';//'&nbsp;';
                            }
                            $data[] = $mycourseitemoverview;
                        }
                    }
                }
            } 
        }
        return $data;
    }
    
    
    
    private function block_my_courses_add_row_required_items($mc, $data)
    {
        // gradable items and required items can overlap. 
        // thats why its not just possible to add al required items
        // because then some items could appear twice in the list with course items
        global $CFG, $USER;
        require_once($CFG->dirroot.'/lib/gradelib.php');
        
        
        // IF GRADEABLE ITEMS ARE NOT SHOWN, items would not appear twice, so the required items can simply be added
        if(! $this->showrowgradeableitems)
        {
            $info = new completion_info($mc);
            $completions = $info->get_completions($USER->id);
            // make 2 arrays to help change the order of occurence of required items in predata
            $requiredandgradeables = array();
            $requireds = array();
            foreach($completions as $completion)
            {
                $criteria = $completion->get_criteria();
                $details = $criteria->get_details($completion);
                                            
                // we want to add grades for items that are required (and gradeable)
                
                // we have required items by their id, through $criteria->moduleinstance
                // we need to get the grades for these required items through grade_items
                // unfortunately, we dont know which required item is which grade_item directly
                // the course contains information that can connect the two, in modinfo
                $coursemodinfoarray = unserialize($mc->modinfo);
                $gradeitems = array();// QUERY
                $gradeitems = grade_item::fetch_all(array('courseid'=>$mc->id));
                foreach($gradeitems as $gi)
                {
                    if($gi->itemtype != 'course')
                    {
                        foreach($coursemodinfoarray as $cmi)
                        {
                            // here we connect the moduleinstance id with the right grade_item (with help of course's modinfo)
                            // its done in a for loop, so we get all moduleinstances here
                            // we get all moduleinstances for every 1 required item (this all is surrounded by another for loop to cycle through every required item)
                            if($cmi->id == $gi->iteminstance && $cmi->mod == $gi->itemmodule)
                            {
                                // now we check for each moduleinstance id (which we now know the right grade_item for), 
                                // if its the current required items moduleinstance id
                                if($criteria->moduleinstance == $cmi->cm)
                                {
                                    $fastmodinfo = get_fast_modinfo($mc);
                                    $coursemodule = $fastmodinfo->get_cm($cmi->cm);
                                    
                                    // dont display hidden items
                                    // normal: uservisible == 1
                                    // greyed out: showavailability == 1 && uservisible != 1 (give them availability info)
                                    // hidden: the rest (showavailability == 0 && uservisible != 1)
                        
                                    // not hidden:
                                    if(! ($coursemodule->showavailability == 0 && $coursemodule->uservisible != 1))
                                    {
                                        $requireditem = array();
                                        $requireditem['requiredgradableitem'] = $details['criteria'];
                                        $requireditem['requiredgradableitemid'] = $cmi->cm; 
                                        $requireditem['requiredgradableitemmod'] = $cmi->mod;
                                        // greyed out / disabled:
                                        if($coursemodule->showavailability == 1 && $coursemodule->uservisible != 1)
                                        {
                                            $requireditem['greyedout'] = true;
                                            // use availabilityinfo as title
                                            $requireditem['availabilityinfo'] = $coursemodule->availableinfo; 
                                        }
                                        if($this->showcolumngrades)
                                        {
                                            $grade = $this->block_my_courses_add_column_grades($USER->id, null, $gi);
                                            $requireditem[] = $grade;
                                        }
                                        if($this->showcolumnrequirements)
                                        {
                                            $requirement = $this->block_my_courses_add_column_requirements($details['requirement'], $completion);
                                            $requireditem[] = $requirement;
                                        }
                                        if($this->showcolumnprogress)
                                        {
                                            $requireditem[] = html_writer::tag('span', get_string('notrequired', 'block_my_courses'), array('class' => 'noinfo'));//'&nbsp;';
                                        }
                                        $requiredandgradeables[] = $requireditem;
                                    }
                                } 
                            }
                        }
                    }
                }
                // add the rest of the required items (the ones that arent also gradeable)
                // except passing grade (which has id nr 6)
                if(! $criteria instanceof completion_criteria_activity && $criteria->criteriatype != 6)// completion_criteria_activity Object
                {
                    // put item in array before we add it to $data 
                    // to prevent string offset problems with older versions of php when doing checks later
                    $requireditem = array();
                    if($criteria->criteriatype == 1)
                    {
                        $requireditem['markyourselfcomplete'] = $details['criteria'];
                        $requireditem['courseid'] = $mc->id;
                    }
                    else
                    {
                        $requireditem[] = $details['criteria'];
                    }
                    
                    if($this->showcolumngrades)
                    {
                        $requireditem[] = html_writer::tag('span', get_string('notgradeable', 'block_my_courses'), array('class' => 'noinfo'));//'&nbsp;';
                    }
                    if($this->showcolumnrequirements)
                    {
                        $requirement = $this->block_my_courses_add_column_requirements($details['requirement'], $completion);
                        $requireditem[] = $requirement;
                    }
                    if($this->showcolumnprogress)
                    {
                        $requireditem[] = '';//'&nbsp;';
                    }
                    $requireds[] = $requireditem;
                }
            }
            $data = array_merge($data, $requiredandgradeables);
            $data = array_merge($data, $requireds);
        }
        
        
        // IF GRADEABLE ITEMS ARE SHOWN, items would appear twice, so get rid of doubles
        if($this->showrowgradeableitems)
        {
            $info = new completion_info($mc);
            $completions = $info->get_completions($USER->id);
            foreach($completions as $completion)
            {
                $criteria = $completion->get_criteria();
                $details = $criteria->get_details($completion);
                $requireditem = array();
                
                // all gradeable items can be made into links and have an id
                // so if there is an id for the required item,
                // first check if we do not have it already
                
                // below is the way we checked which gradeable items are added
                // we make a variable cm that contains the id for each added gradeableitem
                $cm = null;
                $alreadycontains = false;
                $gradeitems = array();// QUERY
                $gradeitems = grade_item::fetch_all(array('courseid'=>$mc->id));
                $coursemodinfoarray = unserialize($mc->modinfo);
                foreach($gradeitems as $gi)
                {
                    if($gi->itemtype != 'course')
                    {
                        foreach($coursemodinfoarray as $cmi)
                        {
                            if($cmi->id == $gi->iteminstance && $cmi->mod == $gi->itemmodule)
                            {
                                // all gradeable items with the $cm id below are added already
                                $cm = $cmi->cm;
                                // now we make sure we dont add the gradeable items again
                                // that means if requireditem with $criteria->moduleinstance is added already dont add it again
                                if($criteria->moduleinstance == $cm)
                                {
                                    $alreadycontains = true;
                                }
                            }
                        }
                    }
                }
                if(! $alreadycontains && $criteria->criteriatype != 6)
                {
                    if($criteria->criteriatype == 1)
                    {
                        $requireditem['markyourselfcomplete'] = $details['criteria'];
                        $requireditem['courseid'] = $mc->id; 
                    }
                    else
                    {
                        $requireditem[] = $details['criteria'];
                    }
                    if($this->showcolumngrades)
                    {
                        // no grade
                        $requireditem[] = html_writer::tag('span', get_string('notgradeable', 'block_my_courses'), array('class' => 'noinfo'));//'&nbsp;';
                    }
                    if($this->showcolumnrequirements)
                    {
                        // requirement. 
                        $requirement = $this->block_my_courses_add_column_requirements($details['requirement'], $completion);
                        $requireditem[] = $requirement;
                    }
                    if($this->showcolumnprogress)
                    {
                        $requireditem[] = '';//'&nbsp;';
                    }
                    
                    $data[] = $requireditem;
                }
                // completion criteria:
                // self, criteriatype 1? 
                // activity, criteriatype 4?
                // grade, criteriatype 6?
            }
        }
        return $data;
    }
    
    
    
    /**
    * Function that surrounds the gathered data (incl. links) with html.
    * Data gets surrounded by divs,
    * with class attributes so that CSS can make it look like a table.
    *
    * @param Array $data
    * @return String $divtable (html divtable containing the info from $data)
    * @retun String Nothing to display!
    */
    private function block_my_courses_add_html($data)
    {
        if(empty($data))
        {
            return get_string('nooverview', 'block_my_courses');
        }
        // find out in which column this block is placed
        $region = $this->instance->region;
        $divtable = html_writer::start_tag('div', array('class' => 'data overview table'));
        
        //bij rows en columns class attributes first/last toevoegen aan firsts en lasts?
        // start by adding header row and first column, that contains coursenames or itemnames
        $divtable .= html_writer::start_tag('div', array('class' => 'head row row1'));
        $divtable .= html_writer::tag('div', get_string('column1', 'block_my_courses'), array('class' => 'col col1'));        
        // we add columns. for now, each column has own column number
        // that means a table could consist of rows with col1 and col3
        // if we need to name col3 col2 instead in that instance, we need to perform more checks
        if ($this->showcolumngrades)
        {
            $divtable .= html_writer::tag('div', get_string('column2', 'block_my_courses'), array('class' => 'col col2')); 
        }
        if ($this->showcolumnrequirements)
        {
            $divtable .= html_writer::tag('div', get_string('column3', 'block_my_courses'), array('class' => 'col col3')); 
        }
        if($this->showcolumnprogress)
        {
            $divtable .= html_writer::tag('div', get_string('column4', 'block_my_courses'), array('class' => 'col col4')); 
        }
        $divtable .= html_writer::end_tag('div');//head row row1
        
        // first, see with which rows we are dealing
        $l = count($data);
        $endcoursewrap = false;
        $iscourserow = false;
        for ($i = 0; $i < $l; $i++)
        {
            // make row
            // if its the course row:
            if(is_array($data[$i]) && isset($data[$i]['courserow']))
            {
                $iscourserow = true;
                if($endcoursewrap)
                {
                    $divtable .= html_writer::end_tag('div');//course rows //end wrap entire course and all its items
                }
                // wrap entire course and all its items
                if($region == 'content' && isset($data[$i]['ghost']))
                {
                    $divtable .= html_writer::start_tag('div', array('class' => 'entirecourse expanded ghost')   );
                    unset($data[$i]['ghost']);
                }
                else if($region == 'content')
                {
                    $divtable .= html_writer::start_tag('div', array('class' => 'entirecourse expanded')   );
                }
                else if(isset($data[$i]['ghost']))
                {
                    $divtable .= html_writer::start_tag('div', array('class' => 'entirecourse collapsed ghost')   );
                    unset($data[$i]['ghost']);
                }
                else
                {
                    $divtable .= html_writer::start_tag('div', array('class' => 'entirecourse collapsed')   );
                }
                $endcoursewrap = true;
                $divtable .= html_writer::start_tag('div', array('class' => 'coursenamerow row row' . ($i + 2) ));
                unset($data[$i]['courserow']);
            }
            // if its not a course row its a gradeable and/or required item
            else
            {
                $iscourserow = false;
                if(is_array($data[$i]) && isset($data[$i]['greyedout']))
                {
                    $divtable .= html_writer::start_tag('div', array('class' => 'itemrow greyedout row row' . ($i + 2) ));
                    unset($data[$i]['greyedout']);
                }
                else
                {
                    $divtable .= html_writer::start_tag('div', array('class' => 'itemrow row row' . ($i + 2) ));
                }
            }
            $l2 = count($data[$i]);
            // make columns, or, in other words, 
            // display extra fields with information within each row 
            // enter the rows (which are arrays themselves) that are in the data array
            
            // add columns
            for ($i2 = 0; $i2 < $l2; $i2++)
            {
                // add collapse/expand ability. JAVASCRIPT!
                if($i2 == 0 && $iscourserow)
                {
                    if($region == 'content')
                    {
                        $expander = '<div 
                                        class="expanderexpanded" 
                                        onclick="
                                        
                                            if(parentNode.parentNode.parentNode.className == \'entirecourse collapsed\')
                                            {
                                                parentNode.parentNode.parentNode.className=\'entirecourse expanded\'; 
                                                this.innerHTML=\'\';
                                                this.className = \'expanderexpanded\';
                                            }
                                            else 
                                            {
                                                parentNode.parentNode.parentNode.className=\'entirecourse collapsed\'; 
                                                this.className = \'expandercollapsed\';
                                            }
                                        
                                        "
                                        style="width: 11px; height: 11px; padding-right: 2px;"
                                    ></div>';
                        $divtable .= html_writer::tag('div', $expander . $data[$i][$i2], array('class' => 'col col' . ($i2 + 1) ));
                    }
                    else
                    {
                        $expander = '<div 
                                        class="expandercollapsed" 
                                        onclick="
                                        
                                            if(parentNode.parentNode.parentNode.className == \'entirecourse collapsed\')
                                            {
                                                parentNode.parentNode.parentNode.className=\'entirecourse expanded\'; 
                                                this.innerHTML=\'\';
                                                this.className = \'expanderexpanded\';
                                            }
                                            else 
                                            {
                                                parentNode.parentNode.parentNode.className=\'entirecourse collapsed\'; 
                                                this.className = \'expandercollapsed\';
                                            }
                                        
                                        "
                                        style="width: 11px; height: 11px; padding-right: 2px;"
                                    ></div>';
                        $divtable .= html_writer::tag('div', $expander . $data[$i][$i2], array('class' => 'col col' . ($i2 + 1) ));
                    }
                }
                else
                {
                    $divtable .= html_writer::tag('div', $data[$i][$i2], array('class' => 'col col' . ($i2 + 1) )); 
                }
            }
            // close row
            $divtable .= html_writer::end_tag('div');//courserow/itemrow row rowx
        }
        $divtable .= html_writer::end_tag('div');//course rows //end wrap entire course and all its items one last time
        // this added hr element is just for layout purposes: 
        // we dont know how big the data overview table will be, so we cant simply give widths/heights
        // just floating makes for correct size, but incorrect placement
        // hr trick used to get correct size as well as placement. see also in CSS: styles.css
        $divtable .= html_writer::empty_tag('hr');
        $divtable .= html_writer::end_tag('div');//data overview table
        
        return $divtable;
    }
    
    
    
    /**
    * Function that makes links out of some data-items.
    * For example: switch courses' id + fullname from $data to fullnamelink, and return the adjusted $data.
    * Makes some other text into links as well.
    *
    * @param Array $data (with raw text)
    * @return Array $data (with links)
    */
    private function block_my_courses_make_links($data)
    {
        global $DB, $CFG, $USER, $OUTPUT;
        $l = count($data);
        for($i = 0; $i < $l; $i++)
        {
            // make link to course - courseid and course fullname are needed
            if(is_array($data[$i]) && isset($data[$i]['id']))/// && ! empty($data[$i]['fullname']))
            {
                if(isset($data[$i]['courseishidden']))
                {
                    $title = $data[$i]['courseishidden'];
                    unset($data[$i]['courseishidden']);
                }
                else
                {
                    $title = get_string('goto', 'block_my_courses') . $data[$i]['fullname'];
                }
                $fullnamelink =
                    html_writer::link   
                    (
                        new moodle_url('/course/view.php', array('id' => $data[$i]['id'])),
                        $data[$i]['fullname'],
                        array('title' => $title)
                    );
                // remove the items in the array that we dont need anymore
                unset($data[$i]['id']);
                unset($data[$i]['fullname']);
                // make the link the first item in the array
                array_unshift($data[$i], $fullnamelink); // array_unshift does a prepend instead of append
                // add key=>value 'courserow''true'
                // when we surround the data with html, we will use this to give extra class attribute to the row div tag
                $data[$i]['courserow'] = 'true';
            }
            
            
            // make links to gradable course items - complete gradable item is needed
            if(is_array($data[$i]) && isset($data[$i]['gradableitemmyname']))/// $$ ! empty($data[$i]['gradableitemid'])$$ ! empty($data[$i]['gradableitemmod']))
            {
                // if its an activity with availabilityinfo we want this as the tooltiptext
                // remember to strip tags because title attribute cannot deal with them
                if( isset($data[$i]['availabilityinfo'])  )
                {
                    $gradableitemlink =
                        html_writer::link   
                        (
                            new moodle_url('/mod/' . $data[$i]['gradableitemmod'] . '/view.php', array('id' => $data[$i]['gradableitemid'])),
                            $data[$i]['gradableitemmyname'],
                            array('title' => strip_tags($data[$i]['availabilityinfo']))
                        );
                    unset($data[$i]['availabilityinfo']);
                }
                else
                {
                    $gradableitemlink =
                        html_writer::link   
                        (
                            new moodle_url('/mod/' . $data[$i]['gradableitemmod'] . '/view.php', array('id' => $data[$i]['gradableitemid'])),
                            $data[$i]['gradableitemmyname'],
                            array('title' => get_string('goto', 'block_my_courses') . $data[$i]['gradableitemmyname'])
                        );
                }
                unset($data[$i]['gradableitemmyname']);
                unset($data[$i]['gradableitemid']); 
                unset($data[$i]['gradableitemmod']); 
                array_unshift($data[$i], $gradableitemlink);
            }
            
            // make links to required items that happen to be gradeable
            if(is_array($data[$i]) && isset($data[$i]['requiredgradableitem']))/// $$ ! empty($data[$i]['gradableitemid'])$$ ! empty($data[$i]['gradableitemmod']))
            {
                // if its an activity with availabilityinfo we want this as the tooltiptext
                // remember to strip tags because title attribute cannot deal with them
                // to properly make the link in the first place, we need to strip tags as well
                if( isset($data[$i]['availabilityinfo'])  )
                {
                    $requiredgradableitemlink =
                        html_writer::link   
                        (
                            new moodle_url('/mod/' . $data[$i]['requiredgradableitemmod'] . '/view.php', array('id' => $data[$i]['requiredgradableitemid'])),
                            strip_tags($data[$i]['requiredgradableitem']),
                            array('title' => strip_tags($data[$i]['availabilityinfo']))
                        );
                    unset($data[$i]['availabilityinfo']);
                }
                else
                {
                    $requiredgradableitemlink =
                        html_writer::link   
                        (
                            new moodle_url('/mod/' . $data[$i]['requiredgradableitemmod'] . '/view.php', array('id' => $data[$i]['requiredgradableitemid'])),
                            strip_tags($data[$i]['requiredgradableitem']),
                            array('title' => get_string('goto', 'block_my_courses') . strip_tags($data[$i]['requiredgradableitem']))
                        );
                }
                unset($data[$i]['requiredgradableitem']);
                unset($data[$i]['requiredgradableitemid']); 
                unset($data[$i]['requiredgradableitemmod']);
                array_unshift($data[$i], $requiredgradableitemlink);
            }
            
            // make link to the required item where one marks him-/herself complete in the course
            // itll link to the course, which is where the mark yourself complete block is
            if(is_array($data[$i]) && isset($data[$i]['markyourselfcomplete']) && isset($data[$i]['courseid']))
            {
                $markyourselfcompletelink =
                        html_writer::link   
                        (
                            new moodle_url('/course/view.php', array('id' => $data[$i]['courseid'])  ),
                            $data[$i]['markyourselfcomplete'],
                            array('title' => get_string('markyourselfcomplete', 'block_my_courses'))
                        );
                
                unset($data[$i]['markyourselfcomplete']);
                unset($data[$i]['courseid']);
                array_unshift($data[$i], $markyourselfcompletelink);
            }
        }
        return $data;
    } 
} 