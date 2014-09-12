<?php

namespace mod_lecturefeedback\event;
defined('MOODLE_INTERNAL') || die();

class entry_created extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'lecturefeedback_entries';
    }

    public function get_url() {
        return new \moodle_url('/mod/lecturefeedback/view.php', array('id' => $this->contextinstanceid));
    }

    protected function get_legacy_logdata() {
        return array($this->courseid, 'lecturefeedback', 'add entry',
            "view.php?id={$this->contextinstanceid}",
            $this->objectid, $this->contextinstanceid);
    }

    protected function validate_data() {
        parent::validate_data();
        if ($this->contextlevel !== CONTEXT_MODULE)
            throw new \coding_exception('Context level must be CONTEXT_MODULE.');
    }
}
