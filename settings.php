<?php
if (!defined("PHORUM_ADMIN")) return;

require_once("./mods/stopforumspam/defaults.php");

// Save settings.
if (count($_POST))
{

        $PHORUM["mod_stopforumspam"] = array
        (
            // Whether or not to enable event logging.
            'log_events' => isset($_POST['log_events']) ? 1 : 0,


            'block_action' => $_POST['block_action'],
            'check_ip' => (!empty($_POST['check_ip']))?1:0,
            'check_username' => (!empty($_POST['check_username']))?1:0,
            'check_email' => (!empty($_POST['check_email']))?1:0,
            'force_generic' => (!empty($_POST['force_generic']))?1:0,
            'freq_min' => (!empty($_POST['freq_min'])) ? (int)($_POST['freq_min']) : 1,

            'apikey' => $_POST['apikey'],

        );
        if($PHORUM["mod_stopforumspam"]["freq_min"] < 1)
            $PHORUM["mod_stopforumspam"]["freq_min"] = 1;

        phorum_db_update_settings(array(
            "mod_stopforumspam" => $PHORUM["mod_stopforumspam"]
        ));

        phorum_admin_okmsg('The settings were successfully saved');
}

?>
<div style="font-size: xx-large; font-weight: bold">Stopforumspam Module</div>
<div style="padding-bottom: 15px; font-size: large">
  Use data from <a href="http://stopforumspam.com">stopforumspam.com</a> to decide if a new user is a suspected spammer!
</div>


<br style="clear:both" />
<?php

include_once "./include/admin/PhorumInputForm.php";
$frm = new PhorumInputForm ("", "post", "Save");
$frm->hidden("module", "modsettings");
$frm->hidden("mod", "stopforumspam");

// ----------------------------------------------------------------------
// Configure general settings
// ----------------------------------------------------------------------

$frm->addrow(
    'API Key<small> (Get it from <a href="http://www.stopforumspam.com/signup">http://www.stopforumspam.com/signup</a>)</small>',
    $frm->text_box('apikey',$PHORUM["mod_stopforumspam"]['apikey'])
);
$row = $frm->addrow("Minimum Frequency Score:", $frm->text_box("freq_min", $PHORUM["mod_stopforumspam"]["freq_min"]));
$frm->addhelp(
    $row, "Spam score threshold",
    "If the total number of times the checked fields appear in the 
     stopforumspam database exceeds this number, the registration
     will be considered spammy.  Adjust according to your checks
     and watch the event log for fale positives.<br/>
     <br/>
     Set to 1 for maximum protection."
);


// ----------------------------------------------------------------------
// Configure log settings
// ----------------------------------------------------------------------

$frm->addbreak("Log settings");

if (!file_exists('./mods/event_logging')) {
      $check = '<span style="color:red">The Event Logging module ' .
               'is currently not installed; logging cannot ' .
               'be enabled</span>';
      $disabled = 'disabled="disabled"';
      $PHORUM["mod_stopforumspam"]["log_events"] = 0;
} elseif (empty($PHORUM['mods']['event_logging'])) {
      $check = '<span style="color:red">The Event Logging module ' .
               'is currently not activated; logging cannot ' .
               'be enabled</span>';
      $disabled = 'disabled="disabled"';
      $PHORUM["mod_stopforumspam"]["log_events"] = 0;
} else {
      $check = '<span style="color:darkgreen">The Event Logging module ' .
               'is activated; events can be logged by enabling the ' .
               'feature below</span>';
      $disabled = '';
}

$frm->addrow($check, '');

$row = $frm->addrow(
    'Log blocked form posts to the Event Logging module?',
    $frm->checkbox(
        "log_events", 1, "Yes",
        $PHORUM["mod_stopforumspam"]["log_events"],
        $disabled
    )
);

$url = phorum_admin_build_url(array(
   'module=modsettings',
   'mod=event_logging',
   'el_action=logviewer'
));

$frm->addhelp(
    $row, "Log blocked form posts to the Event Logging module?",
    "When both this feature and the Event Logging module are enabled,
     then the Stopforumspam module will log information about blocked
     registrations to the Phorum Event Log. To view this log, go to
     <a href=\"$url\">the Event Log viewer</a>"
);


// ----------------------------------------------------------------------
// Configure spam hurdles for posting messages
// ----------------------------------------------------------------------
$frm->addbreak("Action done");
$row = $frm->addrow(
    'What action has to be taken when a spammy registration is suspected?',
    $frm->select_tag(
        'block_action',
        array(
            'blockerror' => 'Fully block and show an error',
            'unapprove'  => 'Accept, but make unapproved'
        ),
        $PHORUM['mod_stopforumspam']['block_action']
    )
);
$frm->addhelp(
    $row, "Action when a spam message is suspected",
    "You can choose whether you want to fully block suspected spammy registrations
     or that you want to have them saved in a moderated state, so they
     will need approval by a moderator.<br/>
     <br/>
     A message is suspicious if it one of the selected checks fails."
);
$row = $frm->addrow('Return Generic Error',$frm->checkbox('force_generic',1,'Yes',$PHORUM['mod_stopforumspam']['force_generic']));
$frm->addhelp(
    $row, "Message type shown to blocked user",
    "Enabling this option will return a generic error message to the suspected spammer
     that does not contain any hints as to the source or reason for the block."
);

// ----------------------------------------------------------------------
// Configure spam hurdles for user registration 
// ----------------------------------------------------------------------

$frm->addbreak("Checks done");
$frm->addrow('Check IP address',$frm->checkbox('check_ip',1,'Yes',$PHORUM['mod_stopforumspam']['check_ip']));
$frm->addrow('Check Username',$frm->checkbox('check_username',1,'Yes',$PHORUM['mod_stopforumspam']['check_username']));
$frm->addrow('Check Email address',$frm->checkbox('check_email',1,'Yes',$PHORUM['mod_stopforumspam']['check_email']));

$frm->show();

#print "<pre>"; print_r($PHORUM['mod_stopforumspam']); print "</pre>";

