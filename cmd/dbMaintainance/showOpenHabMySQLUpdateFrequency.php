<?php
include dirname(__FILE__) . "/../../_lib/init.php";

Logger::init( "import" );

$mysql_db = Setup::getOpenHabMysql();
$rest = Setup::getOpenHabRest();

$itemMap = $mysql_db->selectItemMap( "items" );

$to = new DateTime();

$diff = new DateInterval('PT1H');
$from = new DateTime();
$from->sub($diff);

$values = [];
foreach( $itemMap as $itemName => $itemTable )
{
    $count = $mysql_db->countItemValues($itemTable,$from,$to);
    
    if( $count == 0 ) continue;
    
    $values[] = [$itemName, $itemTable, $count];
}

usort($values, function($a, $b) {
    return ($a[2] <=> $b[2]) * -1;
});

foreach( $values as $value )
{
    Logger::log( Logger::LEVEL_INFO, str_pad($value[0],40) . " - " . str_pad($value[1],7) . " - " . $value[2] );
}
