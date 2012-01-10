<?php

function phorum_mod_stopforumspam_before_register($userdata) {
    global $PHORUM;

    $apikey = $PHORUM['mod_stopforumspam']['apikey'];
    $last_key = false;
    if(!empty($apikey)) {
        $baseurl = "http://www.stopforumspam.com/api?";
        $args=array();
        if(!empty($PHORUM['mod_stopforumspam']['check_ip'])) {
            $args[]="ip=".$_SERVER['REMOTE_ADDR'];
        }
        if(!empty($PHORUM['mod_stopforumspam']['check_username'])) {
            $args[]="username=".$userdata['username'];
        }
        if(!empty($PHORUM['mod_stopforumspam']['check_email'])) {
            $args[]="email=".$userdata['email'];
        }
        if(count($args)) {
            include_once("./include/api/http_get.php");
            $error = array();
            // request serialized data
            $args[]="f=serial";
            $url = $baseurl.implode("&",$args);
            #_phorum_mod_stopforumspam_log("SFS query is $url", "", EVENTLOG_LVL_DEBUG);
            $res = phorum_api_http_get($url);
            if($res !== null) {
                $resdata = unserialize($res);
                $score = 0;
                foreach($resdata as $key => $var) {
                    if($key == 'success') {
                        if(empty($var)) {
                            // check failed, get out of here
                            _phorum_mod_stopforumspam_log('Check againt sfs api failed','The check against the stopforumspam.com API failed. We got no success message.\nData was:\n'.print_r($resdata,true));
                            break;
                        }
                    } elseif(!empty($var['appears'])) {
                        $last_key = $key;
                    }
                    if(!empty($var['frequency'])) {
                        $score += $var['frequency'];
                    }
                }
                #_phorum_mod_stopforumspam_log("score $score >= {$PHORUM["mod_stopforumspam"]["freq_min"]} ?", "", EVENTLOG_LVL_DEBUG);
                if(empty($PHORUM["mod_stopforumspam"]["freq_min"]) || $PHORUM["mod_stopforumspam"]["freq_min"] < 1)
                    $PHORUM["mod_stopforumspam"]["freq_min"] = 1; # hard min
                if($score >= $PHORUM["mod_stopforumspam"]["freq_min"]) {
                    if($PHORUM['mod_stopforumspam']['block_action'] == 'blockerror') {
                        // block user
                        if($PHORUM['mod_stopforumspam']['force_generic'] || !$last_key)
                            $error[]=$PHORUM['DATA']['LANG']['mod_stopforumspam']['error_generic'];
                        else
                            $error[]=$PHORUM['DATA']['LANG']['mod_stopforumspam']['error_'.$last_key];
                    } else {
                        if($userdata["active"] == PHORUM_USER_ACTIVE) {
                            $userdata['active'] = PHORUM_USER_PENDING_MOD;
                        } elseif($userdata["active"] == PHORUM_USER_PENDING_EMAIL) {
                            $userdata['active'] = PHORUM_USER_PENDING_BOTH;
                        }
                    }
                }
                if(count($error)) {
                    if($PHORUM['mod_stopforumspam']['block_action'] == 'blockerror') {
                        _phorum_mod_stopforumspam_log("User {$userdata['username']} registration blocked! (score $score)","A user registration based on data from stopforumspam.com was blocked!\nUserdata was:\nUsername: {$userdata['username']}\nIP: {$_SERVER['REMOTE_ADDR']}\nEmail: {$userdata['email']}\n\nAnd data returned from Stopforumspam was:\n".print_r($resdata,true));
                    } else {
                        _phorum_mod_stopforumspam_log("User registration made unapproved! (score $score)","A user registration based on data from stopforumspam.com was made unapproved!\nUserdata was:\nUsername: {$userdata['username']}\nIP: {$_SERVER['REMOTE_ADDR']}\nEmail: {$userdata['email']}\n\nAnd data returned from Stopforumspam was:\n".print_r($resdata,true));
                    }
                    $userdata['error']=implode("<br />",$error);
                    $error = array();
                } else {
                    _phorum_mod_stopforumspam_log("User registration was safe (score $score)","A user registration was safe based on data from stopforumspam.com.\nUserdata was:\nUsername: {$userdata['username']}\nIP: {$_SERVER['REMOTE_ADDR']}\nEmail: {$userdata['email']}\n\nAnd data returned from Stopforumspam was:\n".print_r($resdata,true));

                }
            } else {
                _phorum_mod_stopforumspam_log('ERROR Check failed',"Data returned from Stopforumspam was:\n".print_r($res,true));

            }
        }
    }
    return $userdata;
}
function _phorum_mod_stopforumspam_log($shortmsg,$message,$level=EVENTLOG_LVL_INFO) {
    global $PHORUM;
    if (!empty($PHORUM["mod_stopforumspam"]["log_events"]) &&
        function_exists('event_logging_writelog')) {
        event_logging_writelog(array(
            'message'   => $shortmsg,
            'details'   => $message,
            'loglevel'  => $level
        ));
    }
}