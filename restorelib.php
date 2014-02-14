<?php //$Id: restorelib.php,v 1.14 2006/03/28 23:31:11 stronk7 Exp $
    //This php script contains all the stuff to backup/restore
    //lecturefeedback mods

    //This is the "graphical" structure of the lecturefeedback mod:
    //
    //                      lecturefeedback                                      
    //                    (CL,pk->id)
    //                        |
    //                        |
    //                        |
    //                   lecturefeedback_entries 
    //               (UL,pk->id, fk->lecturefeedback)     
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          nt->nested field (recursive data)
    //          CL->course level info
    //          UL->user level info
    //          files->table may have files)
    //
    //-----------------------------------------------------------

    //This function executes all the restore procedure about this mod
    function lecturefeedback_restore_mods($mod,$restore) {

        global $CFG;

        $status = true;

        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

        if ($data) {
            //Now get completed xmlized object
            $info = $data->info;
            //traverse_xmlize($info);                                                                     //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //Now, build the lecturefeedback record structure
            $lecturefeedback->course = $restore->course_id;
            $lecturefeedback->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $lecturefeedback->intro = backup_todb($info['MOD']['#']['INTRO']['0']['#']);
            $lecturefeedback->introformat = backup_todb($info['MOD']['#']['INTROFORMAT']['0']['#']);
            $lecturefeedback->days = backup_todb($info['MOD']['#']['DAYS']['0']['#']);
            $lecturefeedback->assessed = backup_todb($info['MOD']['#']['ASSESSED']['0']['#']);
            $lecturefeedback->timemodified = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);

            //We have to recode the assessed field if it is <0 (scale)
            if ($lecturefeedback->assessed < 0) {
                $scale = backup_getid($restore->backup_unique_code,"scale",abs($lecturefeedback->assessed));
                if ($scale) {
                    $lecturefeedback->assessed = -($scale->new_id);
                }
            }

            //The structure is equal to the db, so insert the lecturefeedback
            $newid = insert_record ("lecturefeedback",$lecturefeedback);

            //Do some output
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("modulename","lecturefeedback")." \"".format_string(stripslashes($lecturefeedback->name),true)."\"</li>";
            }
            backup_flush(300);

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype,
                             $mod->id, $newid);
                //Now check if want to restore user data and do it.
                if (restore_userdata_selected($restore,'lecturefeedback',$mod->id)) {
                    //Restore lecturefeedback_entries
                    $status = lecturefeedback_entries_restore_mods ($mod->id, $newid,$info,$restore);
                }
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        return $status;
    }


    //This function restores the lecturefeedback_entries
    function lecturefeedback_entries_restore_mods($old_lecturefeedback_id, $new_lecturefeedback_id,$info,$restore) {

        global $CFG;

        $status = true;

        //Get the entries array
        $entries = $info['MOD']['#']['ENTRIES']['0']['#']['ENTRY'];

        //Iterate over entries
        for($i = 0; $i < sizeof($entries); $i++) {
            $entry_info = $entries[$i];
            //traverse_xmlize($entry_info);                                                               //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($sub_info['#']['ID']['0']['#']);
            $olduserid = backup_todb($sub_info['#']['USERID']['0']['#']);

            //Now, build the lecturefeedback_ENTRIES record structure
            $entry->lecturefeedback = $new_lecturefeedback_id;
            $entry->userid = backup_todb($entry_info['#']['USERID']['0']['#']);
            $entry->modified = backup_todb($entry_info['#']['MODIFIED']['0']['#']);
            $entry->text = backup_todb($entry_info['#']['TEXT']['0']['#']);
            $entry->format = backup_todb($entry_info['#']['FORMAT']['0']['#']);
            $entry->rating = backup_todb($entry_info['#']['RATING']['0']['#']);
            $entry->comment = backup_todb($entry_info['#']['COMMENT']['0']['#']);
            $entry->teacher = backup_todb($entry_info['#']['TEACHER']['0']['#']);
            $entry->timemarked = backup_todb($entry_info['#']['TIMEMARKED']['0']['#']);
            $entry->mailed = backup_todb($entry_info['#']['MAILED']['0']['#']);

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$entry->userid);
            if ($user) {
                $entry->userid = $user->new_id;
            }

            //We have to recode the teacher field
            $user = backup_getid($restore->backup_unique_code,"user",$entry->teacher);
            if ($user) {
                $entry->teacher = $user->new_id;
            }

            //The structure is equal to the db, so insert the lecturefeedback_entry
            $newid = insert_record ("lecturefeedback_entries",$entry);

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"lecturefeedback_entry",$oldid,
                             $newid);
            } else {
                $status = false;
            }
        }

        return $status;
    }

    //This function converts texts in FORMAT_WIKI to FORMAT_MARKDOWN for
    //some texts in the module
    function lecturefeedback_restore_wiki2markdown ($restore) {

        global $CFG;

        $status = true;

        //Convert lecturefeedback_entries->text
        if ($records = get_records_sql ("SELECT e.id, e.text, e.format
                                         FROM {$CFG->prefix}lecturefeedback_entries e,
                                              {$CFG->prefix}lecturefeedback j,
                                              {$CFG->prefix}backup_ids b
                                         WHERE j.id = e.lecturefeedback AND
                                               j.course = $restore->course_id AND
                                               e.format = ".FORMAT_WIKI. " AND
                                               b.backup_code = $restore->backup_unique_code AND
                                               b.table_name = 'lecturefeedback_entries' AND
                                               b.new_id = e.id")) {
            foreach ($records as $record) {
                //Rebuild wiki links
                $record->text = restore_decode_wiki_content($record->text, $restore);
                //Convert to Markdown
                $wtm = new WikiToMarkdown();
                $record->text = $wtm->convert($record->text, $restore->course_id);
                $record->format = FORMAT_MARKDOWN;
                $status = update_record('lecturefeedback_entries', addslashes_object($record));
                //Do some output
                $i++;
                if (($i+1) % 1 == 0) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo ".";
                        if (($i+1) % 20 == 0) {
                            echo "<br />";
                        }
                    }
                    backup_flush(300);
                }
            }

        }

        //Convert lecturefeedback->intro
        if ($records = get_records_sql ("SELECT j.id, j.intro, j.introformat
                                         FROM {$CFG->prefix}lecturefeedback j,
                                              {$CFG->prefix}backup_ids b
                                         WHERE j.course = $restore->course_id AND
                                               j.introformat = ".FORMAT_WIKI. " AND
                                               b.backup_code = $restore->backup_unique_code AND
                                               b.table_name = 'lecturefeedback' AND
                                               b.new_id = j.id")) {
            foreach ($records as $record) {
                //Rebuild wiki links
                $record->intro = restore_decode_wiki_content($record->intro, $restore);
                //Convert to Markdown
                $wtm = new WikiToMarkdown();
                $record->intro = $wtm->convert($record->intro, $restore->course_id);
                $record->introformat = FORMAT_MARKDOWN;
                $status = update_record('lecturefeedback', addslashes_object($record));
                //Do some output
                $i++;
                if (($i+1) % 1 == 0) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo ".";
                        if (($i+1) % 20 == 0) {
                            echo "<br />";
                        }
                    }
                    backup_flush(300);
                }
            }

        }

        return $status;
    }

    //This function returns a log record with all the necessay transformations
    //done. It's used by restore_log_module() to restore modules log.
    function lecturefeedback_restore_logs($restore,$log) {
    
        $status = false;

        //Depending of the action, we recode different things
        switch ($log->action) {
        case "add":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "update":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "add entry":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "update entry":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view responses":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "report.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "update feedback":
            if ($log->cmid) {
                $log->url = "report.php?id=".$log->cmid;
                $status = true;
            }
            break;
        case "view all":
            $log->url = "index.php?id=".$log->course;
            $status = true;
            break;
        default:
            if (!defined('RESTORE_SILENTLY')) {
                echo "action (".$log->module."-".$log->action.") unknown. Not restored<br />";                 //Debug
            }
            break;
        }

        if ($status) {
            $status = $log;
        }
        return $status;
    }
?>
