<?php // $Id: lib.php,v 1.4 2008/05/04 15:07:18 okuda Exp okuda $

/*
if (!isset($CFG->lecturefeedback_showrecentactivity)) {
    set_config("lecturefeedback_showrecentactivity", true);
}
*/


// STANDARD MODULE FUNCTIONS /////////////////////////////////////////////////////////


function lecturefeedback_add_instance($lecturefeedback) {
    global $CFG, $USER, $DB;

    $lecturefeedback->timemodified = time();

    if (empty($lecturefeedback->notice))
      $lecturefeedback->notice = 0;

    if (empty($lecturefeedback->showfeedback))
      $lecturefeedback->showfeedback = 0;

    $lecturefeedback->id = $DB->insert_record("lecturefeedback", $lecturefeedback);

    lecturefeedback_grade_item_update($lecturefeedback);

    return $lecturefeedback->id;
}


function lecturefeedback_update_instance($lecturefeedback) {
    global $CFG, $USER, $DB;

    $lecturefeedback->timemodified = time();
    $lecturefeedback->id = $lecturefeedback->instance;

    if (empty($lecturefeedback->notice))
      $lecturefeedback->notice = 0;

    if (empty($lecturefeedback->showfeedback))
      $lecturefeedback->showfeedback = 0;

    lecturefeedback_grade_item_update($lecturefeedback);

    return $DB->update_record("lecturefeedback", $lecturefeedback);
}


function lecturefeedback_delete_instance($id) {
    global $CFG, $USER, $DB;

    if (! $lecturefeedback = $DB->get_record("lecturefeedback", array("id" => $id))) {
        return false;
    }

    $result = true;

    if (! $DB->delete_records("lecturefeedback_entries", array("lecturefeedback" => $lecturefeedback->id))) {
        $result = false;
    }

    if (! $DB->delete_records("lecturefeedback", array("id" => $lecturefeedback->id))) {
        $result = false;
    }

    return $result;
}


function lecturefeedback_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

//-------------------

function lecturefeedback_user_outline($course, $user, $mod, $lecturefeedback) {
    global $CFG, $USER, $DB;

    if ($entry = $DB->get_record("lecturefeedback_entries", array("userid" => $user->id, "lecturefeedback" => $lecturefeedback->id))) {
        $numwords = count(preg_split("/\w\b/", $entry->text)) - 1;
        $result->info = get_string("numwords", "", $numwords);
        $result->time = $entry->modified;
        return $result;
    }
    return NULL;
}


function lecturefeedback_user_complete($course, $user, $mod, $lecturefeedback) {
    global $CFG, $USER, $DB, $OUTPUT;

    if ($entry = $DB->get_record("lecturefeedback_entries", array("userid" => $user->id, "lecturefeedback" => $lecturefeedback->id))) {
        echo $OUTPUT->box_start('generalbox');
        if ($entry->modified) {
            echo "<p><font size=\"1\">".get_string("lastedited").": ".userdate($entry->modified)."</font></p>";
        }
        if ($entry->text) {
            echo format_text($entry->text, $entry->format);
        }
        if ($entry->teacher) {
            $grades = make_grades_menu($lecturefeedback->assessed);
            lecturefeedback_print_feedback($course, $entry, $grades);
        }
        echo $OUTPUT->box_end();
    } else {
        print_string("noentry", "lecturefeedback");
    }
}


function lecturefeedback_user_complete_index($course, $user, $lecturefeedback, $lecturefeedbackopen, $heading) {
    global $CFG, $USER, $DB, $OUTPUT;

    if (! $cm = $DB->get_record("course_modules", array("id" => $lecturefeedback->coursemodule))) {
        error("Course Module ID was incorrect");
    }

    $context = context_module::instance($cm->id);

    if (has_capability('mod/lecturefeedback:teacher', $context)) {
        $entrycount = lecturefeedback_count_entries($lecturefeedback, get_current_group($course->id));
        $entryinfo  = "&nbsp;(<a href=\"report.php?id=$lecturefeedback->coursemodule\">".get_string("viewallentries","lecturefeedback", $entrycount)."</a>)";
    } else {
        $entryinfo = "";
    }

    $lecturefeedback->name = "<a href=\"view.php?id=$lecturefeedback->coursemodule\">".format_string($lecturefeedback->name,true)."</a>";

    if ($heading) {
        echo "<h3>$heading - $lecturefeedback->name$entryinfo</h3>";
    } else {
        echo "<h3>$lecturefeedback->name$entryinfo</h3>";
    }

    echo $OUTPUT->box_start('generalbox');
    echo format_text($lecturefeedback->intro,  $lecturefeedback->introformat);
    echo $OUTPUT->box_end();
    echo "<br clear=\"all\" />";
    echo "<br />";

    if (has_capability('mod/lecturefeedback:student', $context) or has_capability('mod/lecturefeedback:teacher', $context)) {
        echo $OUTPUT->box_start('generalbox');

        if ($lecturefeedbackopen) {
            echo "<p align=\"right\"><a href=\"edit.php?id=$lecturefeedback->coursemodule\">";
            echo get_string("edit")."</a></p>";
        } else {
            echo "<p align=\"right\"><a href=\"view.php?id=$lecturefeedback->coursemodule\">";
            echo get_string("view")."</a></p>";
        }

        if ($entry = $DB->get_record("lecturefeedback_entries", array("userid" => $user->id, "lecturefeedback" => $lecturefeedback->id))) {
            if ($entry->modified) {
                echo "<p align=\"center\"><font size=\"1\">".get_string("lastedited").": ".userdate($entry->modified)."</font></p>";
            }
            if ($entry->text) {
                echo format_text($entry->text, $entry->format);
            }
            if ($entry->teacher) {
                $grades = make_grades_menu($lecturefeedback->assessed);
                if ( $lecturefeedback->showfeedback == 1 ) { //sekiya2007
                    lecturefeedback_print_feedback($course, $entry, $grades);
                }          //sekiya2007
            }
        } else {
            print_string("noentry", "lecturefeedback");
        }

        echo $OUTPUT->box_end();
        echo "<br clear=\"all\" />";
        echo "<br />";
    }

}


function lecturefeedback_cron() {
    global $CFG, $USER, $DB, $OUTPUT, $context;

    $cutofftime = time() - $CFG->maxeditingtime;

    if ($entries = lecturefeedback_get_unmailed_graded($cutofftime)) {
        $timenow = time();

        foreach ($entries as $entry) {
            echo "Processing lecturefeedback entry $entry->id\n";
            if (! $user = $DB->get_record("user", array("id" => $entry->userid))) {
                echo "Could not find user $entry->userid\n";
                continue;
            }

            $USER->lang = $user->lang;

            if (! $course = $DB->get_record("course", array("id" => $entry->course))) {
                echo "Could not find course $entry->course\n";
                continue;
            }

            if (!has_capability('mod/lecturefeedback:student', $context) and !has_capability('mod/lecturefeedback:teacher', $context)) {
                continue;  // Not an active participant
            }

            if (! $teacher = $DB->get_record("user", array("id" => $entry->teacher))) {
                echo "Could not find teacher $entry->teacher\n";
                continue;
            }

            if (! $mod = get_coursemodule_from_instance("lecturefeedback", $entry->lecturefeedback, $course->id)) {
                echo "Could not find course module for lecturefeedback id $entry->lecturefeedback\n";
                continue;
            }

            unset($lecturefeedbackinfo);
            $lecturefeedbackinfo->teacher = fullname($teacher);
            $lecturefeedbackinfo->lecturefeedback = format_string($entry->name,true);
            $lecturefeedbackinfo->url = "$CFG->wwwroot/mod/lecturefeedback/view.php?id=$mod->id";
            $modnamepl = get_string( 'modulenameplural','lecturefeedback' );
            $msubject = get_string( 'mailsubject','lecturefeedback' );

            $postsubject = "$course->shortname: $msubject: ".format_string($entry->name,true);
            $posttext  = "$course->shortname -> $modnamepl -> ".format_string($entry->name,true)."\n";
            $posttext .= "---------------------------------------------------------------------\n";
            $posttext .= get_string("lecturefeedbackmail", "lecturefeedback", $lecturefeedbackinfo)."\n";
            $posttext .= "---------------------------------------------------------------------\n";
            if ($user->mailformat == 1) {  // HTML
                $posthtml = "<p><font face=\"sans-serif\">".
                "<a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->shortname</a> ->".
                "<a href=\"$CFG->wwwroot/mod/lecturefeedback/index.php?id=$course->id\">lecturefeedbacks</a> ->".
                "<a href=\"$CFG->wwwroot/mod/lecturefeedback/view.php?id=$mod->id\">".format_string($entry->name,true)."</a></font></p>";
                $posthtml .= "<hr /><font face=\"sans-serif\">";
                $posthtml .= "<p>".get_string("lecturefeedbackmailhtml", "lecturefeedback", $lecturefeedbackinfo)."</p>";
                $posthtml .= "</font><hr />";
            } else {
              $posthtml = "";
            }

            if (! email_to_user($user, $teacher, $postsubject, $posttext, $posthtml)) {
                echo "Error: lecturefeedback cron: Could not send out mail for id $entry->id to user $user->id ($user->email)\n";
            }
            if (! $DB->set_field("lecturefeedback_entries", "mailed", "1", array("id" => $entry->id))) {
                echo "Could not update the mailed field for id $entry->id\n";
            }
        }
    }

    return true;
}

function lecturefeedback_print_recent_activity($course, $isteacher, $timestart) {   //!!!!!!!!!NEEDTOFIX!!!!!!!!!!!!!!1
    global $CFG, $USER, $DB, $OUTPUT;

    if (!empty($CFG->lecturefeedback_showrecentactivity)) {    // Don't even bother
        return false;
    }

    $content = false;
    $lecturefeedbacks = NULL;

    if (!$logs = $DB->get_records_sql("SELECT * FROM {log} WHERE `time` > ? AND ".
                                           "`course` = ? AND ".
                                           "`module` = 'lecturefeedback' AND ".
                                           "(`action` = 'add entry' OR `action` = 'update entry') ORDER BY time ASC", array($timestart, $course->id))){
        return false;
    }

    foreach ($logs as $log) {
        $j_log_info = lecturefeedback_log_info($log);

        //Create a temp valid module structure (course,id)
        $tempmod = new \stdClass();
        $tempmod->course = $log->course;
        $tempmod->id = $j_log_info->id;
        //Obtain the visible property from the instance
        $modvisible = instance_is_visible($log->module,$tempmod);

        //Only if the mod is visible
        if ($modvisible) {
            if (!isset($lecturefeedbacks[$log->info])) {
                $lecturefeedbacks[$log->info] = $j_log_info;
                $lecturefeedbacks[$log->info]->time = $log->time;
                $lecturefeedbacks[$log->info]->url = str_replace('&', '&amp;', $log->url);
            }
        }
    }

    if ($lecturefeedbacks) {
        $content = true;
        echo $OUTPUT->heading(get_string('newlecturefeedbackentries', 'lecturefeedback').':');
        foreach ($lecturefeedbacks as $lecturefeedback) {
            print_recent_activity_note($lecturefeedback->time, $lecturefeedback, $isteacher, $lecturefeedback->name,
                                       $CFG->wwwroot.'/mod/lecturefeedback/'.$lecturefeedback->url);
        }
    }

    return $content;
}

function lecturefeedback_grade_item_update($lecturefeedback, $grades=NULL) {
    global $CFG, $USER, $DB;

    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    if (!isset($lecturefeedback->courseid)) {
        $lecturefeedback->courseid = $lecturefeedback->course;
    }

    $params = array('itemname'=>$lecturefeedback->name, 'idnumber'=>$lecturefeedback->id);

    if ($lecturefeedback->assessed > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $lecturefeedback->assessed;
        $params['grademin']  = 0;

    } else if ($lecturefeedback->assessed < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$lecturefeedback->assessed;

    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = NULL;
    }

    if ($lecturefeedback->name) {
        grade_update('mod/lecturefeedback', $lecturefeedback->courseid, 'mod', 'lecturefeedback', $lecturefeedback->id, 0, $grades, $params);
    }

    return true;
}


function lecturefeedback_get_participants($lecturefeedbackid) {
    global $CFG, $USER, $DB;

    //Get students
    $students = $DB->get_records_sql("SELECT DISTINCT u.id, u.id
                                 FROM {user} u,
                                      {lecturefeedback_entries} j
                                 WHERE j.lecturefeedback = ? and
                                       u.id = j.userid", array($lecturefeedbackid));
    //Get teachers
    $teachers = $DB->get_records_sql("SELECT DISTINCT u.id, u.id
                                 FROM {user} u,
                                      {lecturefeedback_entries} j
                                 WHERE j.lecturefeedback = ? and
                                       u.id = j.teacher", array($lecturefeedbackid));

    //Add teachers to students
    if ($teachers) {
        foreach ($teachers as $teacher) {
            $students[$teacher->id] = $teacher;
        }
    }
    //Return students array (it contains an array of unique users)
    return ($students);
}

function lecturefeedback_scale_used ($lecturefeedbackid,$scaleid) {
    global $CFG, $USER, $DB;

    $return = false;

    $rec = $DB->get_record("lecturefeedback",array("id" => $lecturefeedbackid, "assessed" => "-$scaleid"));

    if (!empty($rec) && !empty($scaleid)) {
        $return = true;
    }

    return $return;
}

/**
 *
 * @param int $scaleid
 * @return boolean
 */
function lecturefeedback_scale_used_anywhere($scaleid) {
	return false;
}

// SQL FUNCTIONS ///////////////////////////////////////////////////////////////////

function lecturefeedback_get_users_done($lecturefeedback) {
    global $CFG, $USER, $DB;

    $userslist = $DB->get_records("lecturefeedback_entries", array("lecturefeedback" => $lecturefeedback->id), "modified DESC");

    foreach ($userslist as $userslist_) {
        $newarray[$userslist_->userid] = $DB->get_record("user", array("id" => $userslist_->userid));
    }

    return $newarray;
}

function lecturefeedback_count_entries($lecturefeedback, $groupid=0) {
    global $CFG, $USER, $DB;

    return $DB->count_records("lecturefeedback_entries", array("lecturefeedback" => $lecturefeedback->id));
}


function lecturefeedback_get_unmailed_graded($cutofftime) {
    global $CFG, $USER, $DB;

    return $DB->get_records_sql("SELECT e.*, j.course, j.name
                              FROM {lecturefeedback_entries} e,
                                   {lecturefeedback} j
                             WHERE e.mailed = '0'
                               AND e.timemarked < ?
                               AND e.timemarked > 0
                               AND e.lecturefeedback = j.id
                               AND j.notice = 1", array($cutofftime));	//sekiya2004
}

function lecturefeedback_log_info($log) {
    global $CFG, $USER, $DB;

    return $DB->get_record_sql("SELECT j.*, u.firstname, u.lastname
                             FROM {lecturefeedback} j,
                                  {lecturefeedback_entries} e,
                                  {user} u
                            WHERE e.id = ?
                              AND e.lecturefeedback = j.id
                              AND e.userid = u.id", array($log->info));
}



function lecturefeedback_print_user_entry($course, $user, $entry, $teachers, $grades, $kinds) {  //sekiya2006
    global $CFG, $USER, $DB, $OUTPUT;

    echo "\n<table border=\"1\" cellspacing=\"0\" valign=\"top\" cellpadding=\"10\">";

    echo "\n<tr>";
    echo "\n<td rowspan=\"2\" width=\"35\" valign=\"top\" class=\"lborder\">";
    echo $OUTPUT->user_picture($user);
    echo "</td>";
    echo "<td nowrap=\"nowrap\" width=\"100%\" class=\"lborder\">".fullname($user);
    if ($entry) {
        echo "&nbsp;&nbsp;<font size=\"1\">".get_string("lastedited").": ".userdate($entry->modified)."</font>";
    }
    echo "</tr>";

    echo "\n<tr><td width=\"100%\" class=\"lborder\">";
    if ($entry) {
        echo format_text(str_replace("<p></p>", "", strip_tags($entry->text, '<p>')), $entry->format);
    } else {
        print_string("noentry", "lecturefeedback");
    }
    echo "</td></tr>";

    if ($entry) {
        echo "\n<tr>";
        echo "<td width=\"35\" valign=\"top\" class=\"lborder\">";
        if (!$entry->teacher) {
            $entry->teacher = $USER->id;
        }
        $tuser = $DB->get_record("user", array("id" => $entry->teacher));
        echo $OUTPUT->user_picture($tuser);
        echo "<td class=\"lborder\">".get_string("feedback").":";
        echo html_writer::select($grades, "r$entry->id", $entry->rating, get_string("nograde")."...");
        if ($entry->timemarked) {
            echo "&nbsp;&nbsp;<font size=\"1\">".userdate($entry->timemarked)."</font>";
        }
        echo "<br /><textarea name=\"c$entry->id\" rows=\"12\" cols=\"60\" wrap=\"virtual\">";
        p($entry->comment);
        echo "</textarea><br />";

        //sekiya2006 start
        if ( $kinds ) {
            echo get_string("category","lecturefeedback").": ";
            $kindsArray = make_menu_from_list($kinds);
            for( $ii=1; $ii<=sizeof($kindsArray); $ii++ ) {
                $chk = "";
                if ( $entry->kind == $ii ) $chk = "checked";
                echo "<input type=\"radio\" name=\"k$entry->id\" value=\"$ii\" $chk>$kindsArray[$ii]\n";
            }
        }
        //sekiya2006 end

        echo "</td></tr>";
    }
    echo "</table><br clear=\"all\" />\n";
}



function lecturefeedback_print_feedback($course, $entry, $grades) {
    global $CFG, $USER, $DB, $OUTPUT;

    if (! $teacher = $DB->get_record('user', array('id' => $entry->teacher))) {
        error('Weird lecturefeedback error');
    }

    echo '<table cellspacing="0" align="center" class="feedbackbox">';

    echo '<tr>';
    echo '<td class="left picture lborder">';
    echo $OUTPUT->user_picture($teacher);
    echo '</td>';
    echo '<td class="entryheader lborder">';
    echo '<span class="author">'.fullname($teacher).'</span>';
    echo '&nbsp;&nbsp;<span class="time">'.userdate($entry->timemarked).'</span>';
    echo '</tr>';

    echo '<tr>';
    echo '<td class="left side lborder">&nbsp;</td>';
    echo '<td class="entrycontent lborder">';

    echo '<div class="grade">';

    if (!empty($entry->rating) and !empty($grades[$entry->rating])) {
        echo get_string('grade').': ';
        echo $grades[$entry->rating];
    } else {
        print_string('nograde');
    }
    echo '</div>';

    echo strip_tags(format_text($entry->comment), '<br><a><b><p>');
    echo '</td></tr></table>';
}

function lecturefeedback_get_view_actions() {
    return array('view','view all','view responses');
}

function lecturefeedback_get_post_actions() {
    return array('add entry','update entry','update feedback');
}


