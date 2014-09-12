<?php  // $Id: view.php,v 1.43 2006/04/05 07:46:53 gustav_delius Exp $

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);    // Course Module ID

    if (! $cm = $DB->get_record("course_modules", array("id" => $id))) {
        error("Course Module ID was incorrect");
    }

    if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
        error("Course is misconfigured");
    }

    require_login($course->id, true, $cm);

    $context = context_module::instance($cm->id);

    if (! $lecturefeedback = $DB->get_record("lecturefeedback", array("id" => $cm->instance))) {
        error("Course module is incorrect");
    }

    $event = \mod_lecturefeedback\event\course_module_viewed::create(array(
        'objectid' => $lecturefeedback->id,
        'context' => $context
    ));
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('lecturefeedback', $lecturefeedback);
    $event->trigger();

    if (! $cw = $DB->get_record("course_sections", array("id" => $cm->section))) {
        error("Course module is incorrect");
    }

    $strlecturefeedback = get_string("modulename", "lecturefeedback");
    $strlecturefeedbacks = get_string("modulenameplural", "lecturefeedback");

// Initialize $PAGE, compute blocks
    $PAGE->set_url('/mod/lecturefeedback/view.php', array('id' => $id));

    $title = $course->shortname . ': ' . format_string($lecturefeedback->name);
    $PAGE->set_title($title);
    $PAGE->set_heading($course->fullname);

    echo $OUTPUT->header();

    echo '<style>
    .lborder {
      border: 1px solid black;
    }
    </style>';

    if (has_capability('mod/lecturefeedback:teacher', $context)) {

//     	var_dump($SESSION);
//     	$grps=groups_get_all_groups($course->id);
//     	var_dump($grps);
//         $currentgroup = get_current_group($course->id);
    	$currentgroup = groups_get_activity_group($cm);
//         var_dump($currentgroup);
        if ($currentgroup and has_capability('mod/lecturefeedback:teacher', $context)) {
            $group = $DB->get_record("groups", ["id" => $currentgroup]);
            $groupname = " ($group->name)";
        } else {
            $groupname = "";
        }
///        if (has_capability('mod/lecturefeedback:debugger', $context)) echo "DEBUG: teacher header entries loaded<br />";
        $entrycount = lecturefeedback_count_entries($lecturefeedback, $currentgroup);

        echo '<div class="reportlink"><a href="report.php?id='.$cm->id.'">'.
              get_string('viewallentries','lecturefeedback', $entrycount)."</a>$groupname</div>";

//sekiya2004 start
        if ( $lecturefeedback->kinds ) {
            echo "<p align=right><a href=\"extract.php?id=$cm->id\">".get_string("viewextract","lecturefeedback")."</a></p>";
        }
        echo "<p align=right><a href=\"list.php?id=$cm->id\">".get_string("viewlist","lecturefeedback")."</a></p>";
///        if (has_capability('mod/lecturefeedback:debugger', $context)) echo "DEBUG: teacher header complyte<br />";
//sekiya2004 end
    } else if (!$cm->visible) {
        notice(get_string('activityiscurrentlyhidden'));
    }

    $lecturefeedback->intro = trim($lecturefeedback->intro);

///    if (has_capability('mod/lecturefeedback:debugger', $context)) {
///      echo "<pre>";
///      print_r ($lecturefeedback);
///    }

    if (!empty($lecturefeedback->intro)) {
        //print_simple_box( strip_tags(format_text($lecturefeedback->intro,  $lecturefeedback->introformat), '<br><a><b><p>'), 'center', '70%', '', 5, 'generalbox', 'intro');
        echo $OUTPUT->box_start('generalbox');

        echo format_text($lecturefeedback->intro,  $lecturefeedback->introformat);

        echo $OUTPUT->box_end();
    }

    echo '<br />';

    $timenow = time();

    if ($course->format == 'weeks' and $lecturefeedback->days) {
        $timestart = $course->startdate + (($cw->section - 1) * 604800);
        if ($lecturefeedback->days) {
            $timefinish = $timestart + (3600 * 24 * $lecturefeedback->days);
        } else {
            $timefinish = $course->enddate;
        }
    } else {  // Have no time limits on the lecturefeedbacks

        $timestart = $timenow - 1;
        $timefinish = $timenow + 1;
        $lecturefeedback->days = 0;
    }

    if ($timenow > $timestart) {

        echo $OUTPUT->box_start('generalbox');

        if ($timenow < $timefinish) {
            $options = array ('id' => "$cm->id");
            echo '<center>';
            if (has_capability('mod/lecturefeedback:teacher', $context) || has_capability('mod/lecturefeedback:student', $context)) {
                //print_single_button('edit.php', $options, get_string('startoredit','lecturefeedback'));
                echo $OUTPUT->single_button(new moodle_url("edit.php", $options), get_string('startoredit','lecturefeedback'), 'post', $options);
            }
            echo '</center>';
        }

        $entry = $DB->get_record('lecturefeedback_entries', array('userid' => $USER->id, 'lecturefeedback' => $lecturefeedback->id));

///        if (has_capability('mod/lecturefeedback:debugger', $context)) print_r ($entry);

        if ($entry) {
            if (empty($entry->text)) {
                echo '<p align="center"><b>'.get_string('blankentry','lecturefeedback').'</b></p>';
            } else {
                echo strip_tags(format_text($entry->text, $entry->format), '<br><a><b><p>');
            }
        } else {
            echo '<span class="warning">'.get_string('notstarted','lecturefeedback').'</span>';
        }

        echo $OUTPUT->box_end();

        if ($timenow < $timefinish) {
          if (is_object($entry)){
            if ($entry->modified) {
                echo '<div class="lastedit"><strong>'.get_string('lastedited').':</strong> ';
                echo userdate($entry->modified);
                echo ' ('.get_string('numwords', '', count_words($entry->text)).')';
                echo "</div>";
            }
            if ($lecturefeedback->days) {
                echo '<div class="editend"><strong>'.get_string('editingends', 'lecturefeedback').':</strong> ';
                echo userdate($timefinish).'</div>';
            }
          }
        } else {
            echo '<div class="editend"><strong>'.get_string('editingended', 'lecturefeedback').':</strong> ';
            echo userdate($timefinish).'</div>';
        }
      if (is_object($entry)){
        if ($entry->comment or $entry->rating) {
            if ( $lecturefeedback->showfeedback == 1 ) {    //sekiya2004
                $grades = make_grades_menu($lecturefeedback->assessed);
                echo $OUTPUT->heading(get_string('feedback'));
                lecturefeedback_print_feedback($course, $entry, $grades);
            }                                                //sekiya2004
        }
      }
    } else {
        echo '<div class="warning">'.get_string('notopenuntil', 'lecturefeedback').': ';
        echo userdate($timestart).'</div>';
    }

    echo $OUTPUT->footer();

