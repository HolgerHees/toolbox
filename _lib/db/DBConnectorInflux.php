<?php

class DBConnectorInflux
{
    private $host;
    private $port;
    private $user;
    private $pass;
    private $database;

    public function __construct( $host, $port, $user, $pass, $database )
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
        $this->database = $database;
    }

    public function getTables()
    {
        $result = Request::makeRequest( "http://" . $this->host . ":" . $this->port . "/query?db=" . $this->database . "&q=SHOW%20MEASUREMENTS%20LIMIT%20100&epoch=ms",
            array( "Accept: application/json" ),
            null,
            200
        );

        $data = json_decode( $result );

        $tables = array();

        foreach( $data->{'results'}[0]->{'series'}[0]->{'values'} as $value )
        {
            $tables[] = $value[0];
        }

        return $tables;
    }

    public function insertValues( $itemName, $values )
    {
        Logger::log( Logger::LEVEL_INFO, $itemName . ": insert " . count( $values ) . " values into influxdb" );

        if( count( $values ) > 0 )
        {
            foreach( array_chunk( $values, 100000 ) as $_values )
            {
                $postData = array();
                foreach( $_values as $value )
                {
                    $postData[] = $itemName . " value=" . $value[0] . " " . $value[1];
                }

                Request::makeRequest(
                    "http://" . $this->host . ":" . $this->port . "/write?u=" . $this->user . "&p=" . $this->pass . "&db=" . $this->database . "&rp=autogen&precision=s&consistency=one",
                    array( "Content-Type: text/plain" ),
                    implode( "\n", $postData ), 204
                );
            }
        }
    }

    public function dropTable( $targetItemName )
    {
        Logger::log( Logger::LEVEL_INFO, $targetItemName . ": drop influxdb messurement" );

        Request::makeRequest(
            "http://" . $this->host . ":" . $this->port . "/query?u=" . $this->user . "&p=" . $this->pass . "&chunked=true&db=" . $this->database . "&epoch=ns&q=DROP+SERIES+FROM+%22" . $targetItemName . "%22",
            array( "Content-Type: text/plain" ),
            "", 200 );

        //Logger::log( Logger::LEVEL_INFO,  "Drop Item done" );
    }
}
