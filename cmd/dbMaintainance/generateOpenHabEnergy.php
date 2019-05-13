<?php
include dirname(__FILE__) . "/../../_lib/init.php";

Logger::init( "import" );

exit;

$dryRun = false;
$detailedLog = false;
$allowedItems = array(
    "Electricity_Current_Daily_Consumption",  // 2019-05-06 08:06:35.514    174
    "Electricity_Current_Consumption",        // 2019-05-06 08:06:41.924    175
    "Electricity_Current_Daily_Demand",       // 2019-05-08 08:03:59.228      179
);
$specialItems = array(
    "Electricity_Current_Daily_Consumption" => new IntervalConfig("Electricity_Meter_Demand", "daily", "15 */5 * * * ?", 50 ),
    "Electricity_Current_Consumption" => new IntervalConfig("Electricity_Meter_Demand", "interval", "15 */5 * * * ?", 20000, 300, 360, 900, 2 ),
    "Electricity_Current_Daily_Demand" => new IntervalConfig("Electricity_Meter_Demand", "daily", "15 */5 * * * ?", 50 )
);
$timezone = "Europe/Berlin";

$mysql_db = Setup::getOpenHabMysql();
$influx_db = Setup::getOpenHabInfluxDB();
$rest = Setup::getOpenHabRest();

$calculator = new IntervalCalculator( $mysql_db, $timezone, $detailedLog );

$job = new DataJob( $mysql_db, $influx_db, $rest, $calculator, $allowedItems, $dryRun );
$job->generateValues( array( 'PersistentHistory' ), $specialItems );
