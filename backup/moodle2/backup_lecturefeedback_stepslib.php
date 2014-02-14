<?php

/**
 * Define the complete lecturefeedback structure for backup, with file and id annotations
 */     
class backup_lecturefeedback_activity_structure_step extends backup_activity_structure_step {
 
    protected function define_structure() {
 
        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');
 
        // Define each element separated
        $lecturefeedback = new backup_nested_element('lecturefeedback', array('id'), array(
            'course', 'name', 'intro', 'introformat',
            'days', 'assessed', 'kinds', 'notice',
            'showfeedback', 'timemodified'));
 
        $entries = new backup_nested_element('entries', array('id'), array(
            'lecturefeedback', 'userid', 'modified', 'text', 'format', 'rating', 
            'comment', 'kind', 'teacher', 'timemarked', 'mailed'));
 
        // Build the tree
        $lecturefeedback->add_child($entries);
        
        
        // Define sources
        
        $lecturefeedback->set_source_table('lecturefeedback', array('id' => backup::VAR_ACTIVITYID));
 
        if ($userinfo) {
            $entries->set_source_table('lecturefeedback_entries', array('lecturefeedback' => backup::VAR_PARENTID));
        }
        // Define id annotations
        $entries->annotate_ids('user', 'userid');
        $entries->annotate_ids('user', 'teacher');

        // Define file annotations
        $entries->annotate_files('mod_lecturefeedback', 'intro', null);
        
        // Return the root element (lecturefeedback), wrapped into standard activity structure
        
        return $this->prepare_activity_structure($lecturefeedback);
 
    }
}