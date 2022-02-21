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

$influx_db = SystemConfig::getOpenHabInfluxDB();
$rest = SystemConfig::getOpenHabRest();

$chart_groups = Environment::getInfluxPersistanceGroups();

$item_groups = $rest->getItemGroups();

$tables = $influx_db->getTables();

foreach( $tables as $table )
{
    if( !isset( $item_groups[$table] ) or empty( array_intersect( $chart_groups, $item_groups[$table] ) ) )
    {
        Logger::log( Logger::LEVEL_INFO, $table . " should not exists in influxdb anymore" );
        if( !$dryRun ) $influx_db->dropTable( $table );
        continue;
    }
}
