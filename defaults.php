<?php
// These are the default settings for the Stopforumspam module

global $PHORUM;

if (!isset($PHORUM['mod_stopforumspam']))
    $PHORUM['mod_stopforumspam'] = array();

// The default settings for the Stopforumspam  module.
$mod_stopforumspam_defaults = array(
   'block_action' => 'unapprove',
   'check_ip' => 1,
    'check_username' => 0,
    'check_email' => 1,
    'log_events' => 1,
    'apikey' => '0000',
    'force_generic' => 0,
    'freq_min' => 1,
);

foreach ($mod_stopforumspam_defaults as $key => $val)
{
    if (!isset($PHORUM['mod_stopforumspam'][$key])) {
        $PHORUM['mod_stopforumspam'][$key] = $val;
    }
}