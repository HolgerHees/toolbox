<?php
include dirname(__FILE__) . "/../../_lib/init.php";

Logger::init( "import" );

$arg_config = array(
    'dry'     => array(false,array("true","false"),function($val){ return empty($val) ? true : ( $val == "true" ? true : false ); }),
);

$result = CmdArgs::processOptions($arg_config);
if( !is_array( $result ) )
{
    echo $result;
    exit(1);
}

$dryRun = $result['dry'];

$mysql_db = SystemConfig::getOpenHabMysql();
$influx_db = SystemConfig::getOpenHabInfluxDB();
$rest = SystemConfig::getOpenHabRest();

$db_groups = Environment::getMySQLPersistanceGroups();

$item_groups = $rest->getItemGroups();

$itemMap = $mysql_db->selectItemMap();

foreach( $itemMap as $itemName => $itemTable )
{
    # item exists in "items" table but not as an item in openhab anymore
    if( !isset( $item_groups[$itemName] ) )
    {
        Logger::log( Logger::LEVEL_INFO, $itemName . " (" . $itemTable . ") does not exists in openhab anymore" );
        if( !$dryRun ) $mysql_db->dropItemTable($itemTable,$itemName);
        continue;
    }

    # item exists but should not persistant anymore
    if( empty( array_intersect( $db_groups, $item_groups[$itemName] ) ) )
    {
        Logger::log( Logger::LEVEL_INFO, $itemName . " (" . $itemTable . ") should not be tracked" );
        if( !$dryRun ) $mysql_db->dropItemTable($itemTable,$itemName);
        continue;
    }
}
