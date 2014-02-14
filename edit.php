<?php // $Id: edit.php,v 1.25 2006/04/05 07:46:53 gustav_delius Exp $

    require_once("../../config.php");
    require_once("../../lib/formslib.php");


    $id                     = required_param('id', PARAM_INT);    // Course Module ID
    $act                    = optional_param('act', NULL, PARAM_CLEAN);
    $text                   = optional_param_array('text', NULL, PARAM_TEXT);
    $format                 = optional_param('format', NULL, PARAM_TEXT);

    if (! $cm = $DB->get_record("course_modules", array("id" => $id))) {
        error("Course Module ID was incorrect");
    }

    if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
        error("Course is misconfigured");
    }

    require_login($course->id, false, $cm);

    $context = context_module::instance($cm->id);

    if (!has_capability('mod/lecturefeedback:teacher', $context) && !has_capability('mod/lecturefeedback:student', $context)) {
        error("Guests are not allowed to edit lecturefeedbacks", $_SERVER["HTTP_REFERER"]);
    }

    if (! $lecturefeedback = $DB->get_record("lecturefeedback", array("id" => $cm->instance))) {
        error("Course module is incorrect");
    }

    $entry = $DB->get_record("lecturefeedback_entries", array("userid" => $USER->id, "lecturefeedback" => $lecturefeedback->id));


    if ($act == 'edit' && !empty($text)) {
        $timenow = time();
        $newentry = new StdClass();

        if ($entry) {
            $newentry->id = $entry->id;
            $newentry->text = $text['text'];
            $newentry->format = $format;
            $newentry->modified = $timenow;
            if (! $DB->update_record("lecturefeedback_entries", $newentry)) {
                error("Could not update your lecturefeedback");
            }
            add_to_log($course->id, "lecturefeedback", "update entry", "view.php?id=$cm->id", "$newentry->id", $cm->id);
        } else {
            $newentry->userid = $USER->id;
            $newentry->lecturefeedback = $lecturefeedback->id;
            $newentry->text = $text['text'];
            $newentry->format = $format;
            $newentry->modified = $timenow;
            if (! $newentry->id = $DB->insert_record("lecturefeedback_entries", $newentry)) {
                error("Could not insert a new lecturefeedback entry");
            }
            add_to_log($course->id, "lecturefeedback", "add entry", "view.php?id=$cm->id", "$newentry->id", $cm->id);
        }

        redirect("view.php?id=$cm->id");
        die();
    }


    $strlecturefeedback = get_string("modulename", "lecturefeedback");
    $strlecturefeedbacks = get_string("modulenameplural", "lecturefeedback");
    $stredit = get_string("edit");

    $defaultformat = FORMAT_HTML;

    if (empty($entry)) {
    	$entry = new \stdClass();
        $entry->text = "";
        $entry->format = $defaultformat;
    }

// Initialize $PAGE, compute blocks
    $PAGE->set_url('/mod/lecturefeedback/edit.php', array('id' => $id));

    $title = $course->shortname . ': ' . format_string($lecturefeedback->name);
    $PAGE->set_title($title);
    $PAGE->set_heading($course->fullname);

    echo $OUTPUT->header();

    echo "<center>\n";

    echo $OUTPUT->box_start('generalbox');

    echo format_text($lecturefeedback->intro,  $lecturefeedback->introformat);

    echo $OUTPUT->box_end();

    echo "<br />";

    class mod_lecturefeedback_edit_form extends moodleform {
        function definition() {
            global $CFG, $course, $DB, $entry;
            $mform    =& $this->_form;
            $mform->addElement('editor', 'text', get_string('lecturefeedbacktext', 'lecturefeedback'))->setValue( array('text' => $entry->text) );
            $mform->addElement('select', 'format', get_string('lecturefeedbackformat', 'lecturefeedback'), format_text_menu());
            $this->add_action_buttons(true, $submitlabel="Save changes");

            $mform->setDefault('format', $entry->format);
        }
    }
    $mform = new mod_lecturefeedback_edit_form("edit.php?id={$id}&act=edit");
    $mform->display();

    echo $OUTPUT->footer();


