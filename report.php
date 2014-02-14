<?php // $Id: report.php,v 1.1 2008/05/04 15:33:59 okuda Exp $

    require_once("../../config.php");
    require_once("lib.php");
    require_once($CFG->libdir.'/gradelib.php');

    $id                     = required_param('id', PARAM_INT);   // course module
    $act                    = optional_param('act', NULL, PARAM_TEXT); 
    $text                   = optional_param('text', NULL, PARAM_TEXT); 
    $format                 = optional_param('format', NULL, PARAM_TEXT); 

    if (! $cm = $DB->get_record("course_modules", array("id" => $id))) {
        error("Course Module ID was incorrect");
    }

    if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
        error("Course module is misconfigured");
    }

    require_login($course->id, false);
    
    
    $context       = get_context_instance(CONTEXT_MODULE, $cm->id);
    $contextcourse = get_context_instance(CONTEXT_COURSE, $course->id);

    if (!has_capability('mod/lecturefeedback:teacher', $context)) {
        error("Only teachers can look at this page");
    }

    if (! $lecturefeedback = $DB->get_record("lecturefeedback", array("id" => $cm->instance))) {
        error("Course module is incorrect");
    }

    // make some easy ways to access the entries.
    if ( $eee = $DB->get_records("lecturefeedback_entries", array("lecturefeedback" => $lecturefeedback->id))) {
        foreach ($eee as $ee) {
            $entrybyuser[$ee->userid] = $ee;
            $entrybyentry[$ee->id]  = $ee;
        }

    } else {
        $entrybyuser  = array () ;
        $entrybyentry = array () ;
    }

    $strentries = get_string("entries", "lecturefeedback");
    $strlecturefeedbacks = get_string("modulenameplural", "lecturefeedback");

// Initialize $PAGE, compute blocks

    $PAGE->set_url('/mod/lecturefeedback/report.php', array('id' => $id));
    
    $title = $course->shortname . ': ' . format_string($lecturefeedback->name);
    $PAGE->set_title($title);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_cm($cm);
    
    echo $OUTPUT->header();
    
    echo '<style>
    .lborder {
      border: 1px solid black;
    }
    </style>';

/// Check to see if groups are being used in this lecturefeedback
    if ($groupmode = groupmode($course, $cm)) {   // Groups are being used
        $currentgroup = setup_and_print_groups($course, $groupmode, "report.php?id=$cm->id");
    } else {
        $currentgroup = false;
    }
    

/// Process incoming data if there is any
    if ($act == 'savedata') {
        $feedback = array();
        $data = (array)$_POST;

        // Peel out all the data from variable names.
        foreach ($data as $key => $val) {
            if ($key != "id" && $key != "act") {
                $type = substr($key,0,1);
                $num  = substr($key,1);
                $feedback[$num][$type] = $val;
            }
        }
        

        $timenow = time();
        $count = 0;
        foreach ($feedback as $num => $vals) {
            $entry = $entrybyentry[$num];
            // Only update entries where feedback has actually changed.
            if (($vals['r'] <> $entry->rating) || ($vals['c'] <> addslashes($entry->comment)) || ($vals['k'] <> $entry->kind) ) {  //sekiya2006
              $newentry->rating     = $vals['r'];
              $newentry->comment    = $vals['c'];
              $newentry->kind       = $vals['k'];	//sekiya2006
              $newentry->teacher    = $USER->id;
              $newentry->timemarked = $timenow;
              $newentry->mailed     = 0;           // Make sure mail goes out (again, even)
              $newentry->id         = $num;
              if (! $DB->update_record("lecturefeedback_entries", $newentry)) {
                  notify("Failed to update the lecturefeedback feedback for user $entry->userid");
              } else {
                  $count++;
              }
              $entrybyuser[$entry->userid]->rating     = $vals['r'];
              $entrybyuser[$entry->userid]->comment    = $vals['c'];
              $entrybyuser[$entry->userid]->kind       = $vals['k'];	//sekiya2006
              $entrybyuser[$entry->userid]->teacher    = $USER->id;
              $entrybyuser[$entry->userid]->timemarked = $timenow;
                
                //Set Grades------------------------------------------//
              if ($newentry->rating > 0) {
                $catdata = $DB->get_record("grade_items", array("courseid" => $course->id, "iteminstance" => $lecturefeedback->id, "itemmodule" => 'lecturefeedback'));
                $studentdata = $DB->get_record("lecturefeedback_entries", array("id" => $newentry->id));
                
                $gradesdata = new object;
                $gradesdata->itemid = $catdata->id;
                $gradesdata->userid = $studentdata->userid;
                $gradesdata->rawgrade = $newentry->rating;
                $gradesdata->finalgrade = $newentry->rating;
                $gradesdata->usermodified = $studentdata->userid;
                $gradesdata->timecreated = time();
                $gradesdata->timemodified = time();
                
                if (!$grid = $DB->get_record("grade_grades", array("itemid" => $gradesdata->itemid, "userid" => $gradesdata->userid))) {
                    $grid = $DB->insert_record("grade_grades", $gradesdata);
                } else {
                    $gradesdata->id = $grid->id;
                    $DB->update_record("grade_grades", $gradesdata);
                }
                
                //Count all grades
                $coursedata = $DB->get_record("grade_items", array("courseid" => $course->id, "itemtype" => 'course'));
                $total1 = 0;
                $totalcount = 0;
                
                $allcoursegrades = $DB->get_records("grade_items", array("courseid" => $course->id));
                foreach ($allcoursegrades as $allcoursegrade) {
                    $usercoursegrade = $DB->get_record("grade_grades", array("itemid" => $allcoursegrade->id, "userid" => $gradesdata->userid));
                    if ($usercoursegrade->rawgrademax != $lecturefeedback->assessed) {
                        $DB->set_field ("grade_grades", "rawgrademax", $lecturefeedback->assessed, array("id" => $usercoursegrade->id));
                        $usercoursegrade->rawgrademax = $lecturefeedback->assessed;
                    }
                    if ($usercoursegrade->rawgrade) {
                        $totalcount++;
                        $total1 += round(($usercoursegrade->finalgrade / $usercoursegrade->rawgrademax) * 100);
                    }
                }
                
                $total = round(($total1/$totalcount), 2);
                
                if ($grid = $DB->get_record("grade_grades", array("itemid" => $coursedata->id, "userid" => $gradesdata->userid))) {
                    $DB->set_field("grade_grades", "finalgrade", $total, array("id" => $grid->id));
                } else {
                    $totalgrade = new object;
                    $totalgrade->itemid = $coursedata->id;
                    $totalgrade->userid = $gradesdata->userid;
                    $totalgrade->usermodified = $gradesdata->userid;
                    $totalgrade->rawgrademax = 100;
                    $totalgrade->finalgrade = $total;
                    $totalgrade->timecreated = time();
                    $totalgrade->timemodified = time();
                    $DB->insert_record("grade_grades", $totalgrade);
                }
              }
                //lecturefeedback_grade_item_update($lecturefeedback);
            }
        }
        add_to_log($course->id, "lecturefeedback", "update feedback", "report.php?id=$cm->id", "$count users", $cm->id);
        notify(get_string("feedbackupdated", "lecturefeedback", "$count"), "notifysuccess");
    } else {
        add_to_log($course->id, "lecturefeedback", "view responses", "report.php?id=$cm->id", "$lecturefeedback->id", $cm->id);
    }
    

/// Print out the lecturefeedback entries

    if ($currentgroup) {
        $users = get_enrolled_users($context, 'mod/lecturefeedback:student', $currentgroup);
    } else {
        $users = get_enrolled_users($context, 'mod/lecturefeedback:student', NULL);
    }
    

    if (!$users) {
        echo $OUTPUT->heading(get_string("nousersyet"));
    } else {
        $grades = make_grades_menu($lecturefeedback->assessed);
        $teachers = get_enrolled_users($context, 'mod/lecturefeedback:teacher', NULL);
        $kinds = $lecturefeedback->kinds;   //sekiya2006
        
        $allowedtograde = ($groupmode != VISIBLEGROUPS or has_capability('mod/lecturefeedback:teacher', $context) or groups_is_member($currentgroup, $USER->id));

        if ($allowedtograde) {
            echo '<form action="report.php?id='.$id.'" method="post">';
        }

        if ($usersdone = lecturefeedback_get_users_done($lecturefeedback)) {
            foreach ($usersdone as $user) {
                if ($currentgroup) {
                    if (!groups_is_member($currentgroup, $user->id)) {    /// Yes, it's inefficient, but this module will die
                        continue;
                    }
                }
                lecturefeedback_print_user_entry($course, $user, $entrybyuser[$user->id], $teachers, $grades, $kinds);  //sekiya2006
                unset($users[$user->id]);
            }
        }

        if ($allowedtograde) {
            echo "<center>";
            echo "<input type=\"hidden\" name=\"id\" value=\"$cm->id\" />";
            echo "<input type=\"hidden\" name=\"act\" value=\"savedata\" />";
            echo "<input type=\"submit\" value=\"".get_string("saveallfeedback", "lecturefeedback")."\" />";
            echo "</center>";
            echo "</form>";
        }
    }

    echo $OUTPUT->footer();


