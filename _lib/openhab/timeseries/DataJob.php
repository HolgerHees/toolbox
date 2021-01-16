<?php

class DataJob
{
    private $mysql_db;
    private $influx_db;
    private $rest;
    private $calculator;
    private $allowedItems;
    private $dryRun;

    /**
     * Converter constructor.
     */
    public function __construct( DBConnectorOpenhab $mysql_db, DBConnectorInflux $influx_db, RestConnectorOpenhab $rest, IntervalCalculator $calculator, $allowedItems, $dryRun )
    {
        $this->mysql_db = $mysql_db;
        $this->influx_db = $influx_db;
        $this->rest = $rest;
        $this->calculator = $calculator;
        $this->allowedItems = $allowedItems;
        $this->dryRun = $dryRun;
    }

    public function copyValues( $allowedGroups, $specialItems )
    {
        $itemMap = $this->mysql_db->selectItemMap();

        #$result = Request::makeRequest( "http://192.168.0.50:8080/rest/items?recursive=false",
        #    array( "Accept: application/json" ),
        #    null,
        #    200
        #);
    
        $chartEntries = $this->rest->getItems( $this->allowedItems, $allowedGroups );
        
        foreach( $chartEntries as $entry )
        {
            if( empty( $itemMap[$entry] ) && !isset( $specialItems[$entry] ) )
            {
                Logger::log( Logger::LEVEL_WARNING, "SKIP " . $entry . ". No Data found" );
                continue;
            }
            #else
            #{
            #    Logger::log( Logger::LEVEL_INFO, "Handle " . $entry );
            #}

            //echo "HANDLE: " . $entry . "\n";
            if( !$this->dryRun )
            {
                $this->influx_db->dropTable( $entry );
            }

            if( isset( $specialItems[$entry] ) )
            {
                $entries = $this->calculator->generateValues( $entry, $specialItems[$entry], $itemMap );
            }
            else
            {
                $entries = $this->mysql_db->selectItemEntries( $itemMap[$entry] );
            }

            //continue;

            $data = $this->calculator->fillHourlyData( $entry, $entries );

            //break;
            if( !$this->dryRun )
            {
                $this->influx_db->insertValues( $entry, $data );
            }
        }
    }
    
    public function generateValues( $allowedGroups, $specialItems )
    {
        $itemMap = $this->mysql_db->selectItemMap();

        #$result = Request::makeRequest( "http://192.168.0.50:8080/rest/items?recursive=false",
        #    array( "Accept: application/json" ),
        #    null,
        #    200
        #);

        $chartEntries = $this->rest->getItems( $this->allowedItems, $allowedGroups );

        foreach( $chartEntries as $entry )
        {
            #Logger::log( Logger::LEVEL_INFO, $entry );  
            
            if( empty( $itemMap[$entry] ) || !isset( $specialItems[$entry] ) )
            {
                Logger::log( Logger::LEVEL_WARNING, "SKIP " . $entry . ". No Data found" );
                continue;
            }

            $entries = $this->calculator->generateValues( $entry, $specialItems[$entry], $itemMap );

            $data = $this->calculator->fillHourlyData( $entry, $entries );
            
            $start = count($data);
            
            $maxTime = new DateTime( $entry == "pGF_Utilityroom_Electricity_Current_Daily_Demand" ? "2019-05-08 08:03:00" : "2019-05-06 08:06:00" );
            $maxTime = $maxTime->getTimestamp();
            
            if( !$this->dryRun )
            {
                $this->mysql_db->deleteItemData( $itemMap[$entry], $maxTime );
            }
            
            $current_index = 0;
            $current_value = 0;
            while( $current_index < count($data) )
            {
              $value = $data[$current_index];
              
              //echo $value[1] . " " . $maxTime . "\n";
              
              if( $value[1] > $maxTime )
              {
                  array_splice($data,$current_index);
                  break;
              }
              
              if( $value[0] != $current_value )
              {
                  $current_value = $value[0];
                  $current_index++;
              }
              else
              {
                  array_splice($data,$current_index,1);
              }
            }
            
            #Logger::log( Logger::LEVEL_INFO, $entry . " from " . $start . " to " . count($data) );  
            
            if( !$this->dryRun )
            {
                $this->mysql_db->insertItemData( $itemMap[$entry], $data );
            }
        }
    }
}
