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
* mylist: A table-like overview of my courses, with grades and other additional information.
* Most logical placement would be at the /my page, as a block in the middle column.
* Available languages: English and Dutch.
*
* @package    block
* @subpackage courses_overview
* @copyright  2012 Jeroen
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

class block_courses_overview extends block_base 
{
    // data to show: 
    private $showcolumngrades = false;
    private $showrowgradeableitems = false;
    private $showrowrequireditems = false;
    private $showcolumnrequirements = false;
    
    
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
        if (! empty($this->config->cfg_col_grade)) 
        {
            if ($this->config->cfg_col_grade != 'unchecked')
            {
                $this->showcolumngrades = true;
            }
        }
        if (! empty($this->config->cfg_row_gradeableitems)) 
        {
            if ($this->config->cfg_row_gradeableitems != 'unchecked')
            {
                $this->showrowgradeableitems = true;
            }
        }
        if (! empty($this->config->cfg_row_requireditems)) 
        {
            if ($this->config->cfg_row_requireditems != 'unchecked')
            {
                $this->showrowrequireditems = true;
            }
        }
        if (! empty($this->config->cfg_row_requirements)) 
        {
            if ($this->config->cfg_row_requirements != 'unchecked')
            {
                $this->showcolumnrequirements = true;
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
    
    
    
    /**
    * Helper function: 
    * first call is to a function which gathers all necessary data, 
    * second call is to a function that makes some of the data (such as courses' names) into clickable links
    * third call is to a function that puts all the data in a divtable.
    *
    * @return String $overview
    */
    private function block_courses_overview_make_overview()
    {
        $datanolinks = $this->block_courses_overview_make_rows(); //make_rows function will call make_column_xxx()-like functions
        $datalinks = $this->block_courses_overview_make_links($datanolinks);
        $overview = $this->block_courses_overview_add_html($datalinks);
        return $overview;
    }
    
    
    
    /**
    * Function that gets the information that is wanted for the overview.
    * The settings (suchs as $showcolumngrades) determine what information will be gathered.
    *
    * @return Array $data ('raw' data: some text still needs to be transformed to hyperlinks)
    */ 
    private function block_courses_overview_make_rows()
    {
        global $USER, $CFG;
        require_once($CFG->dirroot.'/lib/gradelib.php');
        require_once($CFG->dirroot.'/lib/grade/grade_item.php');
        require_once($CFG->dirroot.'/lib/grade/grade_grade.php');
        require_once($CFG->dirroot.'/grade/querylib.php'); 
        
        // get my courses -> also includes modinfo, but not the modname and grade. 
        // modinfo has to be unserialized, this will make the string into a usable array
        $mycourses = array();// QUERY
        $mycourses = enrol_get_my_courses('modinfo');// eventueel summary, enablecompletion missch completionstartonenrol velden van course ook nog ophalen
                
        // start the array which will hold all overview data
        $data = array();
        foreach($mycourses as $mc)
        {
            // ROW: course
            $data = $this->block_courses_overview_add_row_courses($mc, $data);                     
           
            
            // ROW: gradable course items
            if($this->showrowgradeableitems)
            {
                $data = $this->block_courses_overview_add_row_gradeable_items($mc, $data);                     
            } 

            
            // ROW: required course items
            if($this->showrowrequireditems)
            {
                $data = $this->block_courses_overview_add_row_required_items($mc, $data);
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
    private function block_courses_overview_add_column_grades($userid = null, $courseid = null, $gradeitem = null, $course = null)
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
                // make the grade into 2 decimals (or not 2, if configured otherwise elsewhere) // QUERY?
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
    private function block_courses_overview_add_column_requirements()
    {
        $requirement = 'v';
        return $requirement;
    }
    
    
    
    /**
    * Row function
    * Function that adds gradeable items as rows
    *
    * @param stdClass Object course
    * @param Array $data 
    * @return Array $data (with course info)
    */
    private function block_courses_overview_add_row_courses($mc, $data)
    {
        // row array, which will contain global course data
        global $USER;
        $mycourseoverview = array();// later wil ik array_unshift gebruiken dus liever een array dan een object, dus geen: $mycourseoverview = new stdClass;
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
        }
        if($this->showcolumngrades)
        {
            // get the grade i got for each course
            // add it to the global course data
            $mycourseoverview[] = $this->block_courses_overview_add_column_grades($USER->id, $mc->id);
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
    private function block_courses_overview_add_row_gradeable_items($mc, $data)
    {
        // get all gradable items (grade_item objects) for each course
        global $USER;
        $gradeitems = array();// QUERY
        $gradeitems = grade_item::fetch_all(array('courseid'=>$mc->id));
        // some hassle to get the coursemoduleid and mod's name
        // we need this later to know which coursemodule id matches which grade
        $coursemodinfoarray = unserialize($mc->modinfo);
        foreach($gradeitems as $gi)
        {
            if($gi->itemtype != 'course')
            {
                // some hassle to get the coursemoduleid and mod. modinfo has it, but it needs to be connected to the right grade_item
                foreach($coursemodinfoarray as $cmi)
                {
                    // todo: check if these checks are ALWAYS enough and correct
                    if($cmi->id == $gi->iteminstance && $cmi->mod == $gi->itemmodule)
                    {
                        // fast_mod_info has attribute uservisible
                        $fastmodinfo = get_fast_modinfo($mc);
                        // get the fastmodinfo for the module were currently working with
                        $coursemodule = $fastmodinfo->get_cm($cmi->cm);
                                    
                        // dont display hidden items. seems to be like the following:
                        // sometimes hidden: $coursemodule->completion on 0  
                        // except when $coursemodule-> showavailability on 1
                        // always hidden: $cmi->completionview on 1
                        $completion = -1;
                        $showavailability = -1;
                        $completionview = -1;
                        if(! empty($coursemodule->completion))
                        {
                            $completion = $coursemodule->completion;
                        }
                        if(! empty($coursemodule->showavailability))
                        {
                            $showavailability = $coursemodule->showavailability;
                        }
                        if(! empty($cmi->completionview))
                        {
                            $completionview = $cmi->completionview;
                        }
                        if(($completion != 0 || $showavailability == 1) && $completionview != 1)//((empty($cmi->completionview) || $cmi->completionview == 0) && $coursemodule->showavailability != 0)// && $coursemodule->showavailability != 0)// || $cmi->completionview != 1)
                        {
                            // row array, which will contain gradable course item data
                            $mycourseitemoverview = array();
                            // add gradable item name, mod (e.q. forum/scorm), and coursemoduleid to create gradableitem link,
                            // and later add that array to data array
                            $mycourseitemoverview['gradableitemmyname'] = $gi->itemname;
                            $mycourseitemoverview['gradableitemid'] = $cmi->cm;
                            $mycourseitemoverview['gradableitemmod'] = $cmi->mod;
                            // some items are greyed out / disabled
                            
                            //print_object($coursemodule);
                            //if(!empty($cmi->showavailability) && $cmi->showavailability == 1)
                            if(! $coursemodule->uservisible)
                            {
                                $mycourseitemoverview['greyedout'] = true;
                            }
                            if($this->showcolumngrades)
                            {
                                $grade = $this->block_courses_overview_add_column_grades($USER->id, null, $gi);
                                $mycourseitemoverview[] = $grade;
                            }
                            if($this->showcolumnrequirements)
                            {
                                // which of the items that are gradeable are also required?
                                $info = new completion_info($mc);
                                $completions = $info->get_completions($USER->id);
                                foreach($completions as $completion)
                                {
                                    $criteria = $completion->get_criteria();
                                    $details = $criteria->get_details($completion);
                                    // add requirement column info to each gradeable item that is also required 
                                    if($criteria instanceof completion_criteria_activity && $cmi->cm == $criteria->moduleinstance)
                                    {
                                        $requirement = $this->block_courses_overview_add_column_requirements();
                                        $mycourseitemoverview[] = $requirement;
                                    }
                                }
                            }
                            $data[] = $mycourseitemoverview;
                        }
                    }
                }
            } 
        }
        return $data;
    }
    
    
    
    private function block_courses_overview_add_row_required_items($mc, $data)
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
            foreach($completions as $completion)
            {
                $criteria = $completion->get_criteria();
                $details = $criteria->get_details($completion);
                
                // for the moment, add all required items except for the ones that are also gradeable 
                if(! $criteria instanceof completion_criteria_activity)// completion_criteria_activity Object
                {
                    // put item in array before we add it to $data 
                    // to prevent string offset problems with older versions of php when doing checks later
                    $requireditem = array();
                    $requireditem[] = $details['criteria'];
                    if($this->showcolumnrequirements)
                    {
                        $requirement = $this->block_courses_overview_add_column_requirements();
                        if($this->showcolumngrades)
                        {
                            $requireditem[] = '';
                        }
                        $requireditem[] = $requirement;
                    }
                    $data[] = $requireditem;
                }                            
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
                                    //if($cmi->completionview != 1)//$coursemodule->showavailability != 0)// || $cmi->completionview != 1)//empty($cmi->completionview) && 
                                    $completion = -1;
                                    $showavailability = -1;
                                    $completionview = -1;
                                    if(! empty($coursemodule->completion))
                                    {
                                        $completion = $coursemodule->completion;
                                    }
                                    if(! empty($coursemodule->showavailability))
                                    {
                                        $showavailability = $coursemodule->showavailability;
                                    }
                                    if(! empty($cmi->completionview))
                                    {
                                        $completionview = $cmi->completionview;
                                    }
                                    if(($completion != 0 || $showavailability == 1) && $completionview != 1)
                                    {
                                        $requireditem = array();
                                        $requireditem[] = $details['criteria'];
                                        // some items are greyed out / disabled
                                        if(! $coursemodule->uservisible)
                                        {
                                            $requireditem['greyedout'] = true;
                                        }
                                        if($this->showcolumngrades)
                                        {
                                            $grade = $this->block_courses_overview_add_column_grades($USER->id, null, $gi);
                                            $requireditem[] = $grade;
                                        }
                                        if($this->showcolumnrequirements)
                                        {
                                            $requirement = $this->block_courses_overview_add_column_requirements();
                                            $requireditem[] = $requirement;
                                        }
                                        $data[] = $requireditem;
                                    }
                                } 
                            }
                        }
                    }
                }
                
            }
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
                $requireditem[] = $details['criteria'];
                if($this->showcolumnrequirements)
                {
                    // no grade
                    $requireditem[] = '';
                    // requirement. TODO: use add column req function
                    $requirement = $this->block_courses_overview_add_column_requirements();
                    $requireditem[] = $requirement;
                }
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
                if(! $alreadycontains)
                {
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
    */
    private function block_courses_overview_add_html($data)
    {
        $divtable = html_writer::start_tag('div', array('class' => 'data overview table')); 
        $divtable .= html_writer::start_tag('div', array('class' => 'colwrapper'));
        if ($this->showcolumngrades && $this->showcolumnrequirements)
        {
            //language files gebruiken?
            //of uit $data halen, en dan dus eerst daadwerkelijk in $data zetten?
            //bij rows en columns class attributes first/last toevoegen aan firsts en lasts?
            $divtable .= html_writer::start_tag('div', array('class' => 'head entirerow row1'));
            $divtable .= html_writer::tag('div', get_string('column1', 'block_courses_overview'), array('class' => 'head col row1 col1')); 
            $divtable .= html_writer::tag('div', get_string('column2', 'block_courses_overview'), array('class' => 'head col row1 col2')); 
            $divtable .= html_writer::tag('div', get_string('column3', 'block_courses_overview'), array('class' => 'head col row1 col3')); 
            $divtable .= html_writer::end_tag('div');//head entirerow row1 
        }
        else if ($this->showcolumngrades)
        {
            //language files gebruiken?
            //of uit $data halen, en dan dus eerst daadwerkelijk in $data zetten?
            //bij rows en columns class attributes first/last toevoegen aan firsts en lasts?
            $divtable .= html_writer::start_tag('div', array('class' => 'head entirerow row1'));
            $divtable .= html_writer::tag('div', get_string('column1', 'block_courses_overview'), array('class' => 'head col row1 col1')); 
            $divtable .= html_writer::tag('div', get_string('column2', 'block_courses_overview'), array('class' => 'head col row1 col2')); 
            $divtable .= html_writer::end_tag('div');//head entirerow row1 
        }
        else if ($this->showcolumnrequirements)
        {
            //language files gebruiken?
            //of uit $data halen, en dan dus eerst daadwerkelijk in $data zetten?
            //bij rows en columns class attributes first/last toevoegen aan firsts en lasts?
            $divtable .= html_writer::start_tag('div', array('class' => 'head entirerow row1'));
            $divtable .= html_writer::tag('div', get_string('column1', 'block_courses_overview'), array('class' => 'head col row1 col1')); 
            $divtable .= html_writer::tag('div', get_string('column3', 'block_courses_overview'), array('class' => 'head col row1 col2')); 
            $divtable .= html_writer::end_tag('div');//head entirerow row1 
        }
        else
        {
            $divtable .= html_writer::start_tag('div', array('class' => 'head entirerow row1'));
            $divtable .= html_writer::tag('div', get_string('column1', 'block_courses_overview'), array('class' => 'head col row1 col1')); 
            $divtable .= html_writer::end_tag('div');//head entirerow row1 
        }
        // first, see with which rows we are dealing
        $l = count($data);
        for ($i = 0; $i < $l; $i++)
        {
            // make row
            // if its the course row:
            if(is_array($data[$i]) && isset($data[$i]['mainrow']))
            {
                // if its a hidden course, for which user has teacher role
                if(isset($data[$i]['ghost']))// && $data[$i]['ghost'] == true)
                {
                    $divtable .= html_writer::start_tag('div', array('class' => 'coursedata entirerow mainrow ghost row' . ($i + 2) ));
                    unset($data[$i]['ghost']);
                    unset($data[$i]['mainrow']);
                }
                else
                {
                    $divtable .= html_writer::start_tag('div', array('class' => 'coursedata entirerow mainrow row' . ($i + 2) ));
                    unset($data[$i]['mainrow']);
                }
            }
            // if its not a course row its a gradeable and/or required item
            else
            {
                if(is_array($data[$i]) && isset($data[$i]['greyedout']))
                {
                    $divtable .= html_writer::start_tag('div', array('class' => 'coursedata entirerow greyedout row' . ($i + 2) ));
                    unset($data[$i]['greyedout']);
                }
                else
                {
                    $divtable .= html_writer::start_tag('div', array('class' => 'coursedata entirerow row' . ($i + 2) ));
                }
            }
            $l2 = count($data[$i]);
            // make columns, or, in other words, 
            // display extra fields with information within each row 
            // enter the rows (which are arrays themselves) that are in the data array
            for ($i2 = 0; $i2 < $l2; $i2++)
            {
                $divtable .= html_writer::tag('div', $data[$i][$i2], array('class' => 'coursedata col row' . ($i + 2) . ' col' . ($i2 + 1) )); 
            }
            // close row
            $divtable .= html_writer::end_tag('div');//coursedata entirerow rowx
        }
        // this added hr element is just for layout purposes: 
        // we dont know how big the data overview table will be, so we cant simply give widths/heights
        // just floating makes for correct size, but incorrect placement
        // hr trick used to get correct size as well as placement. see also in CSS: styles.css
        $divtable .= html_writer::end_tag('div');//colwrapper
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
    private function block_courses_overview_make_links($data)
    {
        global $DB, $CFG, $USER, $OUTPUT;
        $l = count($data);
        for($i = 0; $i < $l; $i++)
        {
            // make link to course - courseid and course fullname are needed
            if(is_array($data[$i]) && isset($data[$i]['id']))/// && ! empty($data[$i]['fullname']))
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
                // add key=>value 'mainrow''true'
                // when we surround the data with html, we will use this to give extra class attribute to the row div tag
                $data[$i]['mainrow'] = 'true';
            }
            
            
            // make links to gradable course items - complete gradable item is needed
            if(is_array($data[$i]) && isset($data[$i]['gradableitemmyname']))/// $$ ! empty($data[$i]['gradableitemid'])$$ ! empty($data[$i]['gradableitemmod']))
            {
                $gradableitemlink =
                    html_writer::link   
                    (
                        new moodle_url('/mod/' . $data[$i]['gradableitemmod'] . '/view.php', array('id' => $data[$i]['gradableitemid'])),
                        $data[$i]['gradableitemmyname'],
                        array('title' => $data[$i]['gradableitemmyname'])
                    );
                unset($data[$i]['gradableitemmyname']);
                unset($data[$i]['gradableitemid']); 
                unset($data[$i]['gradableitemmod']); 
                array_unshift($data[$i], $gradableitemlink);
            }
        } 
        return $data;
    }
} 