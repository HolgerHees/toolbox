<?php
include dirname(__FILE__) . "/../../_lib/init.php";

Logger::init( "import" );

$arg_config = array(
    'items'   => array(true ,"all|<item1>,<item2>"),
    'dry'     => array(false,array("true","false"),function($val){ return empty($val) ? true : ( $val == "true" ? true : false ); }),
    'verbose' => array(false,array("true","false"),function($val){ return empty($val) ? false : ( $val == "true" ? true : false ); }),
);

$result = CmdArgs::processOptions($arg_config);
if( !is_array( $result ) )
{
    echo $result;
    exit(1);
}

$dryRun = $result['dry'];
$detailedLog = $result['verbose'];
$allowedItems = $result['items'] == 'all' ? array() : $result['items'];

$intervalConfigs = Environment::getInfluxDBIntervalConfigs();
$dbGroups = Environment::getInfluxPersistanceGroups();

$timezone = SystemConfig::getTimezone();

$mysql_db = SystemConfig::getOpenHabMysql();
$influx_db = SystemConfig::getOpenHabInfluxDB();
$rest = SystemConfig::getOpenHabRest();

$calculator = new IntervalCalculator( $mysql_db, $timezone, $detailedLog );

$job = new DataJob( $mysql_db, $influx_db, $rest, $calculator, $allowedItems, $dryRun );
$job->fillInfluxDBValues( $dbGroups, $intervalConfigs );
