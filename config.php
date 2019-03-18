
<?php

//  Install as a Cron Job and run this as fast as possible
//  There are internal braking methods to prevent a CPU overload
//  To acheive "real time" performance, we try to run this file as fast as possible, at all times, forever!
//
//  # Moodle Real Time Scanner, must run every minute
//  */1 * * * * wget -O - https://yourwebsite.com/local/RealTimeMonitoringService/?key=YOUR_WEB_KEY > /dev/null 2>&1
//


$KEY = ""; //Basic security measure, a key must be provided to run the file

function debugMessage($type, $message){

	if($_GET['debugMessages'] == "true"){

		if( is_string($message) ){
			echo "<$type>$message</$type>";
		}

		if( is_object($message) ){
			echo "<pre>";
			var_dump($message);
			echo "</pre>";
		}


	}

	return false;

}