<?php
include dirname(__FILE__) . "/config.php";

date_default_timezone_set('Europe/Berlin');

spl_autoload_register(function ($class_name) {

	//class directories
	$directorys = array(
		'db/',
		'helper/',
		'openhab/',
        'openhab/timeseries/',
		'openhab/rest/'
	);
	
	$base = dirname(__FILE__) . "/";
	
	//for each directory
	foreach($directorys as $directory)
	{
		//echo "Search " . $base . $directory.$class_name . ".php\n";
		//see if the file exsists
		if(file_exists( $base.$directory.$class_name . '.php'))
		{
			require_once( $base.$directory.$class_name . '.php');
			//only require the class once, so quit after to save effort (if you got more, then name them something else
			return;
		}           
		//see if the file exsists
		else if(file_exists( $base.$directory.'_'.$class_name . '.php'))
		{
			require_once( $base.$directory.'_'.$class_name . '.php');
			//only require the class once, so quit after to save effort (if you got more, then name them something else
			return;
		}           
	}
        
	echo "Möchte $class_name laden.\n";
    throw new Exception("Kann $class_name nicht laden.");
});
