<?PHP // $Id: report.php,v 1.18 2003/08/18 05:47:04 moodler Exp $

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);   // course

    if (! $cm = $DB->get_record("course_modules", array("id" => $id))) {
        error("Course Module ID was incorrect");
    }

    if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
        error("Course module is misconfigured");
    }
    
    $context       = get_context_instance(CONTEXT_MODULE, $cm->id);

    require_login($course->id);

    if (!has_capability('mod/lecturefeedback:teacher', $context)) {
        error("Only teachers can look at this page");
    }

    if (! $lecturefeedback = $DB->get_record("lecturefeedback", array("id" => $cm->instance))) {
        error("Course module is incorrect");
    }

    $PAGE->set_url('/mod/lecturefeedback/extract.php', array('id' => $id));
    
    $title = $course->shortname . ': ' . format_string($lecturefeedback->name);
    $PAGE->set_title($title);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_cm($cm);
    
    echo $OUTPUT->header();

/// Print out the lecturefeedback entries
    
    $kinds = $lecturefeedback->kinds;
    $kindsArray = make_menu_from_list($kinds);
    for( $ii=1; $ii<=sizeof($kindsArray); $ii++ ) {
        echo "------------------------------------------<br>\n";
        echo $kindsArray[$ii]."<br>\n";
        echo "------------------------------------------<br>\n";
        if ( $eee = $DB->get_records_sql("SELECT * FROM {lecturefeedback_entries} WHERE lecturefeedback = ? AND kind = ?", array($lecturefeedback->id, $ii))) {
            foreach ($eee as $ee) {
                echo format_text($ee->text, $ee->format)."<br>\n";
            }
        } else {
            echo "*<br>\n";
        }
    }
    
    echo $OUTPUT->footer();
 

