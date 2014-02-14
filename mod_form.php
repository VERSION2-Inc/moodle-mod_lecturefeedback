<?php //$Id,v 1.0 2012/03/07 12:00:00 Serafim Panov Exp $

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_lecturefeedback_mod_form extends moodleform_mod {

    function definition() {

        global $COURSE, $CFG, $DB, $PAGE;

        //$lecturefeedbackcfg = get_config('lecturefeedback');

        $data = null;
        if ($update = optional_param('update', 0, PARAM_INT)) {
          $cm = $DB->get_record("course_modules", array("id" => $update));
          $data = $DB->get_record("lecturefeedback", array("id" => $cm->instance));
        }

        $mform    =& $this->_form;

        $context = context_course::instance($COURSE->id);

//-------------------------------------------------------------------------------
    /// Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));
    /// Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('lecturefeedbackname', 'lecturefeedback'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
    /// Adding the optional "intro" and "introformat" pair of fields
        $this->add_intro_editor(true, get_string('lecturefeedbackquestion', 'lecturefeedback'));

        $mform->addElement('select', 'format', get_string('lecturefeedbackformat', 'lecturefeedback'), format_text_menu());
        $mform->addHelpButton('format', 'textformat', 'lecturefeedback');

        $scales = get_scales_menu($COURSE->id);
        $strscale = get_string('scale');
        foreach ($scales as $i => $scalename) {
            $grades[-$i] = $strscale .': '. $scalename;
        }
        $grades[0] = get_string('nograde');
        for ($i=100; $i>=1; $i--) {
            $grades[$i] = $i;
        }

        $mform->addElement('select', 'assessed', get_string('grade', 'lecturefeedback'), $grades);

        $options = array();
        $options[0] = get_string('alwaysopen', 'lecturefeedback');
        for ($i=1;$i<=13;$i++) {
            $options[$i] = get_string('numdays', '', $i);
        }
        for ($i=2;$i<=16;$i++) {
            $days = $i * 7;
            $options[$days] = get_string('numweeks', '', $i);
        }
        $options[365] = get_string('numweeks', '', 52);

        $mform->addElement('select', 'days', get_string('daysavailable', 'lecturefeedback'), $options);


        $mform->addElement('textarea', 'kinds', get_string('category', 'lecturefeedback'), 'rows="5" cols="50"');

        $mform->addElement('checkbox', 'notice', get_string('noticemail', 'lecturefeedback') ,'&nbsp;');
        $mform->addElement('checkbox', 'showfeedback', get_string('showfeedback', 'lecturefeedback') ,'&nbsp;');

        if (is_object($data)) {
          $mform->addElement('html', '<script>var ai = document.getElementById(\'id_assessed\');for (var i=0; i < ai.length; i++) {if (ai.options[i].value == \''.$data->assessed.'\') {ai.options[i].selected = true;}}</script>');
        }

        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
//-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }
}


