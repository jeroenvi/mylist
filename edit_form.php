<?php
 
class block_courses_overview_edit_form extends block_edit_form {
 
    protected function specific_definition($mform) 
    {
        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));
 
 
        // Label show/hide columns
        $mform->addElement('static', 'choosecolumns', get_string('choosecolumns', 'block_courses_overview'));
        
        
        // checkbox for column grades
        $mform->addElement('advcheckbox', 'config_cfg_col_grade', get_string('cfg_col_grade', 'block_courses_overview'));
        $mform->setDefault('config_cfg_col_grade', 'unchecked');
        $mform->setType('config_cfg_col_grade', PARAM_MULTILANG);
        
        
        // Label show/hide rows
        $mform->addElement('static', 'chooserows', get_string('chooserows', 'block_courses_overview'));
        
        
        // checkbox for row gradeableitems
        $mform->addElement('advcheckbox', 'config_cfg_row_gradeableitems', get_string('cfg_row_gradeableitems', 'block_courses_overview'));
        $mform->setDefault('config_cfg_row_gradeableitems', 'unchecked');
        /* 
        // this checkbox for the gradable items column is only enableable if the course grades column is enabled
        $mform->disabledIf('config_cfg_row_gradeableitems', 'config_cfg_col_grade', 'eq', 0);//eq = equals, 0 = arraykey = not checked, or in this case 'unchecked'
         */$mform->setType('config_cfg_row_gradeableitems', PARAM_MULTILANG);
    }
}