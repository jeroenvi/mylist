<?php
 
class block_courses_overview_edit_form extends block_edit_form 
{
 
    protected function specific_definition($mform) 
    {
        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));
 
        
        // Label show/hide ROWS
        $mform->addElement('static', 'chooserows', get_string('chooserows', 'block_courses_overview'));
        
        // checkbox for row gradeableitems
        $mform->addElement('advcheckbox', 'config_cfg_row_gradeableitems', get_string('cfg_row_gradeableitems', 'block_courses_overview'));
        $mform->setDefault('config_cfg_row_gradeableitems', 'unchecked');
        //$mform->disabledIf('config_cfg_row_gradeableitems', 'config_cfg_col_grade', 'eq', 0);//eq = equals, 0 = arraykey = not checked, or in this case 'unchecked'
        $mform->setType('config_cfg_row_gradeableitems', PARAM_MULTILANG);
      
        // checkbox for row requireditems
        $mform->addElement('advcheckbox', 'config_cfg_row_requireditems', get_string('cfg_row_requireditems', 'block_courses_overview'));
        $mform->setDefault('config_cfg_row_requireditems', 'unchecked');
        //$mform->disabledIf('config_cfg_row_requireditems', 'config_cfg_col_grade', 'eq', 0);//eq = equals, 0 = arraykey = not checked, or in this case 'unchecked'
        $mform->setType('config_cfg_row_requireditems', PARAM_MULTILANG); 
        
        
       
        // Label show/hide COLUMNS
        $mform->addElement('static', 'choosecolumns', get_string('choosecolumns', 'block_courses_overview'));
                
        // checkbox for column grades
        $mform->addElement('advcheckbox', 'config_cfg_col_grade', get_string('cfg_col_grade', 'block_courses_overview'));
        $mform->setDefault('config_cfg_col_grade', 'unchecked');
        $mform->setType('config_cfg_col_grade', PARAM_MULTILANG);

        // checkbox for column requirementsitems
        $mform->addElement('advcheckbox', 'config_cfg_col_requirements', get_string('cfg_col_requirements', 'block_courses_overview'));
        $mform->setDefault('config_cfg_col_requirements', 'unchecked');
        $mform->setType('config_cfg_col_requirements', PARAM_MULTILANG);
        
        // checkbox for column progressbar
        $mform->addElement('advcheckbox', 'config_cfg_col_progress', get_string('config_cfg_col_progress', 'block_courses_overview'));
        $mform->setDefault('config_cfg_col_requirements', 'unchecked');
        $mform->setType('config_cfg_col_requirements', PARAM_MULTILANG);
    }
}