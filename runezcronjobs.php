<?php

include ('eZCronjob.class.php');

$options = getopt( "q::h::" );
$jobType = $argv[count( $argv ) - 1];

if( array_key_exists( 'h', $options )) {
	echo<<<EOF
Usage:
php runezcronjobs.php [OPTIONS] command

Options are:
  -q		Quiet mode, only outputs errors
  
Please configure settings/config.ini
And if needed add config files in settings/sites/

EOF;
	exit;
}

$quiet = array_key_exists( 'q', $options ) ? true : false;

$ezcron = new eZCronjob( $quiet );
$ezcron->start( $jobType );

?>