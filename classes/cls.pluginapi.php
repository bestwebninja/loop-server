<?php

//This API class should be included by any Loop-server plugins 
//  The plugin is located in the /plugins/pluginname directory

if(isset($define_classes_path)) {
    //Optionally set $define_classes_path with the root of the loop-server
    require_once($define_classes_path . "classes/cls.basic_geosearch.php");
    require_once($define_classes_path . "classes/cls.layer.php");
    require_once($define_classes_path . "classes/cls.ssshout.php"); 
}




 
 
class cls_plugin_api {
    /*
        Example basic plugin
        <?php
            require_once($start_path . "classes/cls.pluginapi.php");
            
            class plugin_help_is_coming
            {
                function on_message($message_forum_name, $message, $message_id, $sender_id, $recipient_id, $sender_name, $sender_email, $sender_phone)
                {
                    //Do your thing in here
                    
                    return true;
                    
                }
            }
        ?>

    */

    


	  
	/*
	   A manual database insertion function for plugins 
    */
	
	
	public function db_insert(  $table,                       //AtomJump Loop Server (ssshout by default) database table name eg. "tbl_email"
	                            $insert_field_names_str,    //Insert string e.g. "(var_layers, var_title, var_body, date_when_received, var_whisper)"
	                            $insert_field_data_str)     //Insert values e.g. ('" . clean_data($feed['aj']) . "','". clean_data($subject) . "','" . mysql_real_escape_string($raw_text) .  "', NOW(), '" . $feed['whisper'] . "') " )    
	{
	    //Returns true for successful, or breaks the server request if unsuccessful, with an error 
	    
	    //TODO: ensure this uses a more modern type of mysql insertion.
	    $sql = "INSERT INTO " . $table . " " . $insert_field_names_str . " VALUES " . $insert_field_data_str;  
	    mysql_query($sql) or die("Unable to execute query $sql " . mysql_error());
	    return;
	}
	
	/*
	   A manual database update for plugins 
    */
	
	public function db_update($table,                       //AtomJump Loop Server (ssshout by default) database table name eg. "tbl_email"
	                            $update_set)    //Update set e.g. "var_title = 'test' WHERE var_title = 'test2'"  - can have multiple fields	                          
	{
	    //Returns true for successful, or breaks the server request if unsuccessful, with an error 
	    
	    //TODO: ensure this uses a more modern type of mysql insertion.
	    $sql = "UPDATE " . $table . " SET " . $update_set;  
	    mysql_query($sql) or die("Unable to execute query $sql " . mysql_error());
	    return;
	}
	
	
	/*
	   A manual database selection for plugins 
    */
	
	
	public function db_select($sql)     
	{
	    //Returns result array when successful, or breaks the server request if unsuccessful, with an error 
	    
	    //TODO: ensure this uses a more modern type of mysql connection.
	    $result = mysql_query($sql)  or die("Unable to execute query $sql " . mysql_error());
	    
	    return $result;
	}
	                            
	
	/*
	    Get a forum id from a forum name for plugins
	*/	
	
	public function get_forum_id($message_forum_name)
	{
	    //Return the forum id object, or false if unsuccessful
	    /*
	    
	        Output:
	        
	        [ forum_id, access_type, forum_group_user_id ]
	    
	        Where 'forum_id' e.g. 34
	              'access_type' eg. "readwrite", "read"
	              'forum_owner_user_id' is the user id to send a message to, to become visible to all the private forum owners.
	    
	    
	        Internally:
	            [
	                "myaccess",
	                "int_group_id",
	                layer-group-user,
	                enm_access
	            ]
	            
	              `int_layer_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                  `enm_access` enum('public','public-admin-write-only','private') CHARACTER SET latin1 DEFAULT NULL,
                  `passcode` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
                  `int_group_id` int(10) unsigned DEFAULT NULL,
                  `var_public_code` varchar(255) COLLATE utf8_bin DEFAULT NULL,
                  `var_owner_string` varchar(255) COLLATE utf8_bin DEFAULT NULL,
                  `date_owner_start` datetime DEFAULT NULL,
	    
	    
	    */
	
	    $ly = new cls_layer();
	    
	    $returns = $ly->get_layer_id($message_forum_name, null);
	    
	    if($returns != false) {
	        $output = array();
	        $output['forum_id'] = $returns['int_layer_id'];
	        $output['access_type'] = $returns['myaccess'];
	        $output['forum_owner_user_id'] = $returns['layer-group-user'];
	        return $output;
	        
	    } else {
	
    	    return false;
        }
	}

	/*
	    Get the current user's ip address
	*/		
		
	public function get_current_user_ip()
    {	
	    $ly = new cls_layer();
	    return $ly->getRealIpAddr();
	}	


	/*
	    Get the current user's user id
	*/		
		
	public function get_current_user_id()
    {	
	    
	    return $_SESSION['logged-user'];
	}
	
	
	
	/*
	    Run a parallel system process on the server machine
	*/		
		
	public function parallel_system_call($command, $platform = "linux", $logfile = "")
    {	
        switch($platform) {
            case "linux":
                if($logfile != "") {
                    $logfile = ">" . $logfile;
                }
            
	            $cmd = "nohup nice -n 10 " . $command . " " . $logfile . " 2>&1 &";
		        //Seems to help: session_write_close();          //This allows for session data to be stored, and accessed again before a long
		                                        //running process finishes
		        shell_exec($cmd);
		        
		    break;
		    
		    case "windows":
		        //Not yet supported
		    break;
		}
	    return;
	}
	
		
	
	/*
	    Create a new message function for plugins
	*/	
	
	public function new_message($sender_name_str,                           //e.g. 'Fred'
	                            $message,                                   //Message being sent e.g "Hello world!"
	                            $recipient_id,                              //User id of recipient e.g. 436 
	                            $sender_email,                              //Sender's email address e.g. "fred@company.com"
	                            $sender_ip,                                 //Sender's ip address eg. "123.123.123.123"
	                            $message_forum_name,                        //Forum name e.g. 'aj_interesting'
	                            $options = null
	                            )
	 {
	     //Returns the message id if successful, or false if not successful.
	     
	     $bg = new clsBasicGeosearch();
	     $ly = new cls_layer();
	     $sh = new cls_ssshout();
	 
        $sender_still_typing = false;               //Set to true if this is a partially completed message
        $known_message_id = null;                   //If received an id from this function in the past
        $sender_phone = null;                       //Include the phone number for configuration purposes
        $javascript_client_msg_id = null;           //Browser id for this message. Important for 
        $forum_owner_id = null;                     //User id of forum owner
        $social_post_short_code = null;             //eg 'twt' for twitter, 'fcb' for facebook
        $social_recipient_handle_str = null;        //eg. 'atomjump' for '@atomjump' on Twitter
        $date_override = null;                      //optional string for a custom date (as opposed to now) 
        $latitude = 0.0;                            //for potential future location expansion
        $longitude = 0.0;                            //for potential future location expansion
	    $login_as = false;
	 
	    if(isset($options)) {
	     if(isset($options['sender_still_typing'])) $sender_still_typing = $options['sender_still_typing'];
	     if(isset($options['known_message_id']))  $known_message_id = $options['known_message_id'];
	     if(isset($options['sender_phone']))  $known_message_id = $options['sender_phone'];
	     if(isset($options['javascript_client_msg_id']))  $javascript_client_msg_id = $options['javascript_client_msg_id'];
	     if(isset($options['forum_owner_id']))  $forum_owner_id = $options['forum_owner_id'];
	     if(isset($options['social_post_short_code']))  $social_post_short_code = $options['social_post_short_code'];
	     if(isset($options['social_recipient_handle_str']))  $social_recipient_handle_str = $options['social_recipient_handle_str'];
	     if(isset($options['date_override']))  $date_override = $options['date_override'];
	     if(isset($options['latitude'])) $latitude = $options['latitude'];
	     if(isset($options['longitude']))  $longitude = $options['longitude'];
	     if(isset($options['login_as']))  $login_as = $options['login_as'];
	    }
	 
	    
	 
	     return $sh->insert_shout($latitude,
	                        $longitude,
	                        $sender_name_str,
	                        $message,
	                        $recipient_id,
	                        $sender_email,
	                        $sender_ip, 
	                        $bg,
	                        $message_forum_name,
	                        $sender_still_typing,
	                        $known_message_id,
	                        $sender_phone,
	                        $javascript_client_msg_id,
	                        $forum_owner_id,
	                        $social_post_short_code,
	                        $social_recipient_handle_str,
	                        $date_override,
	                        $login_as);
	 
	 }	
	 
	 
	 /*

	    Hide a message, and optionally warn an admin user.
	 */	
	 
	 public function hide_message($message_id, $warn_admin = false)
	 {
	    
	     $ly = new cls_layer();
	     $sh = new cls_ssshout();
	     
	     if($warn_admin = true) {
	        $just_typing = false;
	     } else {
	        $just_typing = true;
	     }	        
	     
	     return $sh->deactivate_shout($message_id, $just_typing);
		 
     }
   

}


?>
