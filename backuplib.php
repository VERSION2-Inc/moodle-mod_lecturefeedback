<?php //$Id: backuplib.php,v 1.4 2006/01/13 03:45:30 mjollnir_ Exp $
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

    function lecturefeedback_backup_mods($bf,$preferences) {

        global $CFG;

        $status = true;

        //Iterate over lecturefeedback table
        $lecturefeedbacks = get_records ("lecturefeedback","course",$preferences->backup_course,"id");
        if ($lecturefeedbacks) {
            foreach ($lecturefeedbacks as $lecturefeedback) {
                if (backup_mod_selected($preferences,'lecturefeedback',$lecturefeedback->id)) {
                    $status = lecturefeedback_backup_one_mod($bf,$preferences,$lecturefeedback);
                }
            }
        }
        return $status;
    }

    function lecturefeedback_backup_one_mod($bf,$preferences,$lecturefeedback) {

        global $CFG;
    
        if (is_numeric($lecturefeedback)) {
            $lecturefeedback = get_record('lecturefeedback','id',$lecturefeedback);
        }
    
        $status = true;

        //Start mod
        fwrite ($bf,start_tag("MOD",3,true));
        //Print lecturefeedback data
        fwrite ($bf,full_tag("ID",4,false,$lecturefeedback->id));
        fwrite ($bf,full_tag("MODTYPE",4,false,"lecturefeedback"));
        fwrite ($bf,full_tag("NAME",4,false,$lecturefeedback->name));
        fwrite ($bf,full_tag("INTRO",4,false,$lecturefeedback->intro));
        fwrite ($bf,full_tag("INTROFORMAT",4,false,$lecturefeedback->introformat));
        fwrite ($bf,full_tag("DAYS",4,false,$lecturefeedback->days));
        fwrite ($bf,full_tag("ASSESSED",4,false,$lecturefeedback->assessed));
        fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$lecturefeedback->timemodified));

        //if we've selected to backup users info, then execute backup_lecturefeedback_entries
        if (backup_userdata_selected($preferences,'lecturefeedback',$lecturefeedback->id)) {
            $status = backup_lecturefeedback_entries($bf,$preferences,$lecturefeedback->id);
        }
        //End mod
        $status =fwrite ($bf,end_tag("MOD",3,true));

        return $status;
    }

    //Backup lecturefeedback_entries contents (executed from lecturefeedback_backup_mods)
    function backup_lecturefeedback_entries ($bf,$preferences,$lecturefeedback) {

        global $CFG;

        $status = true;

        $lecturefeedback_entries = get_records("lecturefeedback_entries","lecturefeedback",$lecturefeedback,"id");
        //If there is entries
        if ($lecturefeedback_entries) {
            //Write start tag
            $status =fwrite ($bf,start_tag("ENTRIES",4,true));
            //Iterate over each entry
            foreach ($lecturefeedback_entries as $jou_ent) {
                //Start entry
                $status =fwrite ($bf,start_tag("ENTRY",5,true));
                //Print lecturefeedback_entries contents
                fwrite ($bf,full_tag("ID",6,false,$jou_ent->id));
                fwrite ($bf,full_tag("USERID",6,false,$jou_ent->userid));
                fwrite ($bf,full_tag("MODIFIED",6,false,$jou_ent->modified));
                fwrite ($bf,full_tag("TEXT",6,false,$jou_ent->text));
                fwrite ($bf,full_tag("FORMAT",6,false,$jou_ent->format));
                fwrite ($bf,full_tag("RATING",6,false,$jou_ent->rating));
                fwrite ($bf,full_tag("COMMENT",6,false,$jou_ent->comment));
                fwrite ($bf,full_tag("TEACHER",6,false,$jou_ent->teacher));
                fwrite ($bf,full_tag("TIMEMARKED",6,false,$jou_ent->timemarked));
                fwrite ($bf,full_tag("MAILED",6,false,$jou_ent->mailed));
                //End entry
                $status =fwrite ($bf,end_tag("ENTRY",5,true));
            }
            //Write end tag
            $status =fwrite ($bf,end_tag("ENTRIES",4,true));
        }
        return $status;
    }
 
   ////Return an array of info (name,value)
   function lecturefeedback_check_backup_mods($course,$user_data=false,$backup_unique_code, $instances=null) {
       if (!empty($instances) && is_array($instances) && count($instances)) {
           $info = array();
           foreach ($instances as $id => $instance) {
               $info += lecturefeedback_check_backup_mods_instances($instance,$backup_unique_code);
           }
           return $info;
       }
        //First the course data
        $info[0][0] = get_string("modulenameplural","lecturefeedback");
        if ($ids = lecturefeedback_ids ($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }

        //Now, if requested, the user_data
        if ($user_data) {
            $info[1][0] = get_string("entries","lecturefeedback");
            if ($ids = lecturefeedback_entry_ids_by_course ($course)) {
                $info[1][1] = count($ids);
            } else {
                $info[1][1] = 0;
            }
        }
        return $info;
    }

   ////Return an array of info (name,value)
   function lecturefeedback_check_backup_mods_instances($instance,$backup_unique_code) {
        //First the course data
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';

        //Now, if requested, the user_data
        if (!empty($instance->userdata)) {
            $info[$instance->id.'1'][0] = get_string("entries","lecturefeedback");
            if ($ids = lecturefeedback_entry_ids_by_instance ($instance->id)) {
                $info[$instance->id.'1'][1] = count($ids);
            } else {
                $info[$instance->id.'1'][1] = 0;
            }
        }
        return $info;
    }





    // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    //Returns an array of lecturefeedbacks id
    function lecturefeedback_ids ($course) {

        global $CFG;

        return get_records_sql ("SELECT a.id, a.course
                                 FROM {$CFG->prefix}lecturefeedback a
                                 WHERE a.course = '$course'");
    }
   
    //Returns an array of lecturefeedback entries id
    function lecturefeedback_entry_ids_by_course ($course) {

        global $CFG;

        return get_records_sql ("SELECT s.id , s.lecturefeedback
                                 FROM {$CFG->prefix}lecturefeedback_entries s,
                                      {$CFG->prefix}lecturefeedback a
                                 WHERE a.course = '$course' AND
                                       s.lecturefeedback = a.id");
    }

    //Returns an array of lecturefeedback entries id
    function lecturefeedback_entry_ids_by_instance ($instanceid) {

        global $CFG;

        return get_records_sql ("SELECT s.id , s.lecturefeedback
                                 FROM {$CFG->prefix}lecturefeedback_entries s
                                 WHERE s.lecturefeedback = $instanceid");
    }
?>
