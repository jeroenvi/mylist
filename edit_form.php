<?php
 
class block_my_courses_edit_form extends block_edit_form 
{
 
    protected function specific_definition($mform) 
    {
        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));
 
        
        // Label show/hide ROWS
        $mform->addElement('static', 'chooserows', get_string('chooserows', 'block_my_courses'));
        
        // checkbox for row gradeableitems
        $mform->addElement('advcheckbox', 'config_cfg_row_gradeableitems', get_string('cfg_row_gradeableitems', 'block_my_courses'));
        $mform->setDefault('config_cfg_row_gradeableitems', 'checked');
        $mform->setDefault('config_cfg_row_gradeableitems', 1);
        //$mform->disabledIf('config_cfg_row_gradeableitems', 'config_cfg_col_grade', 'eq', 0);//eq = equals, 0 = arraykey = not checked, or in this case 'unchecked'
        $mform->setType('config_cfg_row_gradeableitems', PARAM_MULTILANG);
      
        // checkbox for row requireditems
        $mform->addElement('advcheckbox', 'config_cfg_row_requireditems', get_string('cfg_row_requireditems', 'block_my_courses'));
        $mform->setDefault('config_cfg_row_requireditems', 'checked');
        $mform->setDefault('config_cfg_row_requireditems', 1);
        //$mform->disabledIf('config_cfg_row_requireditems', 'config_cfg_col_grade', 'eq', 0);//eq = equals, 0 = arraykey = not checked, or in this case 'unchecked'
        $mform->setType('config_cfg_row_requireditems', PARAM_MULTILANG); 
        
        
       
        // Label show/hide COLUMNS
        $mform->addElement('static', 'choosecolumns', get_string('choosecolumns', 'block_my_courses'));
                
        // checkbox for column grades
        $mform->addElement('advcheckbox', 'config_cfg_col_grade', get_string('cfg_col_grade', 'block_my_courses'));
        $mform->setDefault('config_cfg_col_grade', 'checked');
        $mform->setDefault('config_cfg_col_grade', 1);
        $mform->setType('config_cfg_col_grade', PARAM_MULTILANG);

        // checkbox for column requirementsitems
        $mform->addElement('advcheckbox', 'config_cfg_col_requirements', get_string('cfg_col_requirements', 'block_my_courses'));
        $mform->setDefault('config_cfg_col_requirements', 'checked');
        $mform->setDefault('config_cfg_col_requirements', 1);
        $mform->setType('config_cfg_col_requirements', PARAM_MULTILANG);
        
        // checkbox for column progressbar
        $mform->addElement('advcheckbox', 'config_cfg_col_progress', get_string('config_cfg_col_progress', 'block_my_courses'));
        $mform->setDefault('config_cfg_col_requirements', 'checked');
        $mform->setDefault('config_cfg_col_requirements', 1);
        $mform->setType('config_cfg_col_requirements', PARAM_MULTILANG);
    }
}