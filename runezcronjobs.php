<?php

include ('eZCronjob.class.php');

$jobType = $argv[1];

$ezcron = new eZCronjob( );
$ezcron->start( $jobType );

?>