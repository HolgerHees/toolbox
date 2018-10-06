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

        $result = Request::makeRequest( "http://192.168.0.50:8080/rest/items?recursive=false",
            array( "Accept: application/json" ),
            null,
            200
        );

        $chartEntries = $this->rest->getItems( $this->allowedItems, $allowedGroups );

        foreach( $chartEntries as $entry )
        {
            if( empty( $itemMap[$entry] ) && !isset( $specialItems[$entry] ) )
            {
                Logger::log( Logger::LEVEL_WARNING, "SKIP " . $entry . ". No Data found" );
                continue;
            }

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
}
