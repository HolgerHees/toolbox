<?php
include dirname(__FILE__) . "/../../_lib/init.php";

Logger::init( "import" );

$mysql_db = Setup::getOpenHabMysql();
$influx_db = Setup::getOpenHabInfluxDB();
$rest = Setup::getOpenHabRest();

$allowed_groups = array(
    "gPersistance_History",
    "gGF_Sensor_Window",
    "gFF_Sensor_Window"
);
$allowed_items = array(
    "pGF_Corridor_Motiondetector_State",
    "pGF_Livingroom_Motiondetector_State",
    "pFF_Corridor_Motiondetector_State",
    "pGF_Corridor_Openingcontact_Door_State"
);

$chart_group = "PersistentChart";

$item_groups = $rest->getItemGroups();

//print_r($item_groups);

$itemMap = $mysql_db->selectItemMap( "items" );

foreach( $itemMap as $itemName => $itemTable )
{
    if( !isset( $item_groups[$itemName] ) )
    {
        Logger::log( Logger::LEVEL_INFO, $itemName . " (" . $itemTable . ") does not exists in openhab anymore" );
        //$mysql_db->dropItemTable($itemTable,$itemName);
        continue;
    }

    $dbEntry = $mysql_db->selectItemLastEntry( $itemTable );

    if(
        empty( array_intersect( $allowed_groups, $item_groups[$itemName] ) )
        and
        !in_array( $itemName, $allowed_items )
    )
    {
        Logger::log( Logger::LEVEL_INFO, $itemName . " (" . $itemTable . ") should not be tracked" );
        //$mysql_db->dropItemTable($itemTable,$itemName);
        continue;
    }
}

$tables = $influx_db->getTables();

foreach( $tables as $table )
{
    if( !isset( $item_groups[$table] ) or !in_array( $chart_group, $item_groups[$table] ) )
    {
        Logger::log( Logger::LEVEL_INFO, $table . " should not exists in influxdb anymore" );
        //$influx_db->dropTable( $table );
        continue;
    }
}
