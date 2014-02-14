<?php // $Id: index.php,v 1.21 2006/04/05 07:46:53 gustav_delius Exp $

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = $DB->get_record("course", array("id" => $id))) {
        error("Course ID is incorrect");
    }

    require_login($course->id);
    
    add_to_log($course->id, "lecturefeedback", "view all", "index.php?id=$course->id", "");

    $strlecturefeedback = get_string("modulename", "lecturefeedback");
    $strlecturefeedbacks = get_string("modulenameplural", "lecturefeedback");
    $strweek = get_string("week");
    $strtopic = get_string("topic");


// Initialize $PAGE, compute blocks
    $PAGE->set_url('/mod/lecturefeedback/index.php', array('id' => $id));
    
    $title = $course->shortname . ': LectureFeedback';
    $PAGE->set_title($title);
    $PAGE->set_heading($course->fullname);

    echo $OUTPUT->header();

    echo '<style>
    .lborder {
      border: 1px solid black;
    }
    </style>';

    if (! $lecturefeedbacks = get_all_instances_in_course("lecturefeedback", $course)) {
        notice("There are no lecturefeedbacks", "../../course/view.php?id=$course->id");
        die;
    }

    $timenow = time();

    if ($course->format == "weeks") {
        $strsection = $strweek;
    } else if ($course->format == "topics") {
        $strsection = $strtopic;
    } else {
        $strsection = "";
    }

    foreach ($lecturefeedbacks as $lecturefeedback) {
        $lecturefeedback->timestart  = $course->startdate + (($lecturefeedback->section - 1) * 608400);
        if (!empty($lecturefeedback->daysopen)) {
            $lecturefeedback->timefinish = $lecturefeedback->timestart + (3600 * 24 * $lecturefeedback->daysopen);
        } else {
            $lecturefeedback->timefinish = 9999999999;
        }
        $lecturefeedbackopen = ($lecturefeedback->timestart < $timenow && $timenow < $lecturefeedback->timefinish);
        lecturefeedback_user_complete_index($course, $USER, $lecturefeedback, $lecturefeedbackopen, "$strsection $lecturefeedback->section");
    }


    echo $OUTPUT->footer();
 


