<?php
 
class block_courses_overview_edit_form extends block_edit_form {
 
    protected function specific_definition($mform) {
 
        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));
 
        // A sample string variable with a default value.
        /* $mform->addElement('text', 'config_text', get_string('blockstring', 'block_courses_overview'));
        $mform->setDefault('config_text', 'default value');
        $mform->setType('config_text', PARAM_MULTILANG); */  
        
        $mform->addElement('static', 'choosecolumns', get_string('choosecolumns', 'block_courses_overview'));
                
        $mform->addElement('advcheckbox', 'config_column1', get_string('column1', 'block_courses_overview'));
        $mform->setDefault('config_column1', 'unchecked');
        $mform->setType('config_column1', PARAM_MULTILANG);
 
        /* if (! empty($this->config->column1)) 
        {
            if ($this->config->column1 != 'unchecked')
            {
                $showgrades = true;
            }
        } */
        $mform->addElement('advcheckbox', 'config_column2', get_string('column2', 'block_courses_overview'));
        $mform->setDefault('config_column2', 'unchecked');
        $mform->disabledIf('config_column2', 'config_column1', 'eq', 0);//eq = equals, 0 = arraykey = not checked, or in this case 'unchecked'
        $mform->setType('config_column2', PARAM_MULTILANG);
    }
}