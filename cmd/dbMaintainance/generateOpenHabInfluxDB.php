<?php
include dirname(__FILE__) . "/../../_lib/init.php";

Logger::init( "import" );

$dryRun = false;
$detailedLog = false;
$allowedItems = array(
    //"Rain_Garden_Current_Daily",
    "Electricity_Current_Daily_Consumption"
    //"Electricity_Current_Consumption"
);
$specialItems = array(
    "Gas_Current_Daily_Consumption" => new IntervalConfig( "Gas_Current_Count", "daily", "0 */5 * * * ?", 20 ),
    "Gas_Current_Consumption" => new IntervalConfig("Gas_Current_Count", "interval", "0 */5 * * * ?", 0.2, 300, 615, 900, 2 ),

    "Electricity_Current_Daily_Consumption" => new IntervalConfig("Electricity_Meter", "daily", "15 */5 * * * ?", 50 ),
    "Electricity_Current_Consumption" => new IntervalConfig("Electricity_Meter", "interval", "15 */5 * * * ?", 20000, 300, 360, 900, 2 ),

    "Wind_Garden_Current" => new IntervalConfig("Wind_Garden_Converted", "interval", "0 */15 * * * ?", 150, "MAX", 900, 900, 99 ),
    "Rain_Garden_Current" => new IntervalConfig("Rain_Garden_Counter", "interval", "0 0 * * * ?", 30, "DIFF", 3600, 3600, 99 ),
    "Rain_Garden_Current_Daily" => new IntervalConfig("Rain_Garden_Counter", "daily", "0 */5 * * * ?", 100 ),

    // Maybe improve interval. Sometimes we messure every minute
    "Heating_Burner_Hours_Current_Daily" => new IntervalConfig("Heating_Burner_Hours", "daily", "15 0 0 * * ?", 15 ),
    "Heating_Burner_Starts_Current_Daily" => new IntervalConfig("Heating_Burner_Starts", "daily", "15 0 0 * * ?", 100 ),

    "Heating_Solar_Power_Current_Daily" => new IntervalConfig("Heating_Solar_Power", "daily", "15 0 0 * * ?", 50 ),
    "Heating_Solar_Power_Current5Min" => new IntervalConfig("Heating_Solar_Power", "interval", "45 */30 * * * ?", 30, 300, 3600, 5600, 2 ),
);
$timezone = "Europe/Berlin";

$mysql_db = Setup::getOpenHabMysql();
$influx_db = Setup::getOpenHabInfluxDB();
$rest = Setup::getOpenHabRest();

$calculator = new IntervalCalculator( $mysql_db, $timezone, $detailedLog );

$job = new DataJob( $mysql_db, $influx_db, $rest, $calculator, $allowedItems, $dryRun );
$job->copyValues( array( 'PersistentChart' ), $specialItems );
