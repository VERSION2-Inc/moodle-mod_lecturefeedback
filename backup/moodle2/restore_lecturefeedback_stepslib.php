<?php

/**
 * Structure step to restore one lecturefeedback activity
 */
class restore_lecturefeedback_activity_structure_step extends restore_activity_structure_step {
 
    protected function define_structure() {
 
        $paths = array();

        $paths[] = new restore_path_element('lecturefeedback', '/activity/lecturefeedback');
        $paths[] = new restore_path_element('lecturefeedback_entries', '/activity/lecturefeedback/entries');
 
        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }
 
    protected function process_lecturefeedback($data) {
        global $DB;
  
        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
 
        //$data->name = $this->apply_date_offset($data->name);
        //$data->intro = $this->apply_date_offset($data->intro);
        $data->introformat = $this->apply_date_offset($data->introformat);
        $data->days = $this->apply_date_offset($data->days);
        $data->assessed = $this->apply_date_offset($data->assessed);
        //$data->kinds = $this->apply_date_offset($data->kinds);
        $data->notice = $this->apply_date_offset($data->notice);
        $data->showfeedback = $this->apply_date_offset($data->showfeedback);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
 
        // insert the lecturefeedback record
        $newitemid = $DB->insert_record('lecturefeedback', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }
 
    protected function process_lecturefeedback_entries($data) {
        global $DB;
 
        $data = (object)$data;
        $oldid = $data->id;
 
        $data->lecturefeedback = $this->get_new_parentid('lecturefeedback');
        
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->modified = $this->apply_date_offset($data->modified);
        //$data->text = $this->apply_date_offset($data->text);
        $data->format = $this->apply_date_offset($data->format);
        $data->rating = $this->apply_date_offset($data->rating);
        //$data->comment = $this->apply_date_offset($data->comment);
        $data->kind = $this->apply_date_offset($data->kind);
        $data->teacher = $this->get_mappingid('user', $data->teacher);
        $data->timemarked = $this->apply_date_offset($data->timemarked);
        $data->mailed = $this->apply_date_offset($data->mailed);
 
        $newitemid = $DB->insert_record('lecturefeedback_entries', $data);
        $this->set_mapping('lecturefeedback_entries', $oldid, $newitemid);
    }
    

    protected function after_execute() {
        // Add lecturefeedback related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_lecturefeedback', 'intro', null);
    }
}