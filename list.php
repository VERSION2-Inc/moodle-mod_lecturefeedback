<?PHP // $Id: list.php,v 1.00 2004/07/04 18:07:04 moodler Exp $

    require_once("../../config.php");
    require_once("lib.php");

    $id                     = required_param('id', PARAM_INT);    // Course Module ID
    $act                    = optional_param('act', NULL, PARAM_TEXT); 

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
    
    if ($act != "getcsv") {
      $PAGE->set_url('/mod/lecturefeedback/list.php', array('id' => $id));
      
      $title = $course->shortname . ': ' . format_string($lecturefeedback->name);
      $PAGE->set_title($title);
      $PAGE->set_heading($course->fullname);
      $PAGE->set_cm($cm);
      
      echo $OUTPUT->header();
      
      echo '<input type="button" name="getcsv" value="'.get_string('downloadcsv','lecturefeedback').'" onclick="location.href=\'list.php?id='.$id.'&act=getcsv\'" />';

      echo "-----------------------------------------------------------------------------------------------------------<br>\n";
      echo "<b>Please select following text and put it into text editor and save as ***.csv.<br>\n";
      echo "Then you can open from Spread sheet program like MS-Excel.</b><br>\n";
      echo "1)userid<br>\n";
      echo "2)firstname<br>\n";
      echo "3)lastname<br>\n";
      echo "4)text<br>\n";
      echo "5)comment<br>\n";
      echo "6)rating<br>\n";
      echo "7)kind<br>\n";
      echo "8)modified<br>\n";
      echo "-----------------------------------------------------------------------------------------------------------<br><br>\n";
    }
/// Print out the lecturefeedback entries
    
    $kinds = split(",",$lecturefeedback->kinds);
    $eee = $DB->get_records_sql("SELECT * FROM {lecturefeedback_entries} WHERE lecturefeedback = ?", array($lecturefeedback->id));
    $t = "";
    if ( $eee ) {
        foreach ($eee as $ee) {
            $user = $DB->get_record("user", array("id" => $ee->userid));
            $t .= "\"".$user->username."\",";
            $t .= "\"".$user->firstname."\",";
            $t .= "\"".$user->lastname."\",";
            $st = $ee->text;
            $st = str_replace("\"", "'", $st);
            $t .= "\"".htmlspecialchars($st)."\",";
            $t .= "\"".$ee->comment."\",";
            $t .= "\"".$ee->rating."\",";
            $t .= "\"".$kinds[$ee->kind-1]."\",";
            $t .= "\"".date("Y/m/d H:i:s",$ee->modified)."\"{sep}\n";
        }
    }

    if ($act != "getcsv") {
      $t = str_replace("{sep}", "<br />", $t);
      echo $t;
      echo $OUTPUT->footer();
    } else {
      $t = str_replace("{sep}", "", $t);
      header("Content-type: application/csv");
      header("Content-Disposition: attachment; filename=userslist.csv");
      header("Pragma: no-cache");
      header("Expires: 0");
      
      echo $t;
    }
 


