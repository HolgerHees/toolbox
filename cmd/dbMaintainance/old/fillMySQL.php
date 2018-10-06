<?php
include "../src/helper/Logger.php";
include "../src/helper/Request.php";
include "../src/helper/IntervalCalculator.php";
include "../src/db/_DBConnector.php";
include "../src/db/DBConnectorOpenhab.php";

Logger::init( "import" );

$dryRun = true;
$detailedLog = true;
$allowedItems = array(
    "Gas_Current_Consumption"
);
$timezone = "Europe/Berlin";

$specialItems = array(
    "Gas_Current_Daily_Consumption" => array( "Gas_Current_Count", 20, [ "daily" ], "0 */5 * * * ?" ),
    "Gas_Current_Consumption" => array( "Gas_Current_Count", 0.2, [ "interval", 300, 615, 3, 2 ], "0 */5 * * * ?" ),

    "Electricity_Current_Daily_Consumption" => array( "Electricity_Meter", 50, [ "daily" ], "15 */5 * * * ?" ),
    "Electricity_Current_Consumption" => array( "Electricity_Meter", 20000, [ "interval", 300, 360, 3, 2 ], "15 */5 * * * ?" ),

    "Wind_Garden_Current" => array( "Wind_Garden_Converted", 150, [ "interval", "MAX", 900, 1, 99 ], "0 */15 * * * ?" ),
    "Rain_Garden_Current" => array( "Rain_Garden_Counter", 30, [ "interval", "DIFF", 3600, 1, 99 ], "0 0 * * * ?" ),
    "Rain_Garden_Current_Daily" => array( "Rain_Garden_Counter", 100, [ "daily" ], "0 */5 * * * ?" ),

    // Maybe improve interval. Sometimes we messure every minute
    "Heating_Burner_Starts_Current_Daily" => array( "Heating_Burner_Starts", 100, [ "daily" ], "15 0 0 * * ?" ),
    "Heating_Burner_Hours_Current_Daily" => array( "Heating_Burner_Hours", 15, [ "daily" ], "15 0 0 * * ?" ),

    "Heating_Solar_Power_Current_Daily" => array( "Heating_Solar_Power", 50, [ "daily" ], "15 0 0 * * ?" ),
    "Heating_Solar_Power_Current5Min" => array( "Heating_Solar_Power", 30, [ "interval", 300, 3600, 3, 2 ], "45 */30 * * * ?" ),
);

$db = new DBConnectorOpenhab( "127.0.0.1", "openhab", "jwsitfd3dd", "openhab" );

$itemMap = $db->selectMap( "items" );

//$converter = new IntervalCalculator( $db, $timezone, $detailedLog );

foreach( $specialItems as $name => $data )
{
    if( !empty( $allowedItems ) && !in_array( $name, $allowedItems ) )
    {
        continue;
    }

    if( !$this->dryRun )
    {
        //$this->db->cleanTable( $targetItemId );
    }

    //$entries = $converter->generateValues( $name, $data, $itemMap );

    Logger::log( Logger::LEVEL_INFO, "COUNT: " . $name . " " . count( $entries ) );

    if( !$this->dryRun )
    {
        //$this->db->saveEntries( $targetItemId, $entries );
    }
}