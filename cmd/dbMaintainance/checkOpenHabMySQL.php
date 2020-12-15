<?php
include dirname(__FILE__) . "/../../_lib/init.php";

Logger::init( "import" );

$mappings = array(
    "COLOR" => "VARCHAR(70)",
    "CONTACT" => "VARCHAR(6)",
    "DATETIME" => "DATETIME",
    "DIMMER" => "TINYINT",
    "LOCATION" => "VARCHAR(30)",
    "NUMBER" => "DOUBLE",
    "ROLLERSHUTTER" => "TINYINT",
    "STRING" => "VARCHAR(65500)",
    "SWITCH" => "VARCHAR(6)"
);

$mysql_db = Setup::getOpenHabMysql();
$rest = Setup::getOpenHabRest();

$itemMap = $mysql_db->selectItemMap( "items" );

$itemTypes = $rest->getItemTypes();

foreach( $itemMap as $itemName => $itemTable )
{
    $fields = $mysql_db->showItemFields( $itemTable );
    
    if( !isset($itemTypes[$itemName]) )
    {
        Logger::log( Logger::LEVEL_WARNING, "Unexpected table '". $itemName . "' => '" . $itemTable . "' found");
        continue;
    }
    
    $currentItemType = $itemTypes[$itemName];
    
    $wantedDataType = strtolower( $mappings[ strtoupper( $currentItemType ) ] );
    
    if( !empty($fields['ColumnNames']) ){

        foreach( $fields['ColumnNames'] as $rightName => $wrongName )
        {
            Logger::log( Logger::LEVEL_ERR, $itemName . " (" . $itemTable . ") => (" . $wrongName . " => " . $rightName . ")" );
        }
    }
    
    if( strpos($fields['Time'],'timestamp(3)') !== 0 )
    {
        Logger::log( Logger::LEVEL_ERR, $itemName . " (" . $itemTable . ") has wrong timestamp" );
        
        //$mysql_db->alterItemTimestamp( $itemTable );
    }
    
    if( $wantedDataType != $fields['Value'] )
    {
        Logger::log( Logger::LEVEL_ERR, $itemName);
        Logger::log( Logger::LEVEL_ERR, $itemTable);
        Logger::log( Logger::LEVEL_ERR, $currentItemType);
        Logger::log( Logger::LEVEL_ERR, $wantedDataType . "(" . $fields['Value'] . ")");
        //print_r($fields);
        //exit;
    }
    else
    {
        #Logger::log( Logger::LEVEL_INFO, $itemName . "(" . $itemTable . ", " . $currentItemType . " => " . $wantedDataType . ")" . " is ok" );
    }
}
