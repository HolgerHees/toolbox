<?php

class DBConnectorInflux
{
    private $host;
    private $port;
    private $user;
    private $pass;
    private $database;

    public function __construct( $host, $port, $database, $pass, $user)
    {
        $this->host = $host;
        $this->port = $port;
        $this->database = $database;
        $this->pass = $pass;
        $this->user = $user;
    }

    public function getTables()
    {
        $fields = array( "Accept: application/json" );
        if( !$this->user ) 
        {
            $fields[] = "Authorization: Token " . $this->pass;
        }
    
        $result = Request::makeRequest( "http://" . $this->host . ":" . $this->port . "/query?db=" . $this->database . "&q=SHOW%20MEASUREMENTS%20LIMIT%20100&epoch=ms",
            $fields,
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

                $fields = array( "Content-Type: text/plain" );
                if( $this->user ) 
                {
                    $auth = "u=" . $this->user . "&p=" . $this->pass . "&";
                }
                else 
                {
                    $auth = "";
                    $fields[] = "Authorization: Token " . $this->pass;
                }
                
                Request::makeRequest(
                    "http://" . $this->host . ":" . $this->port . "/write?" . $auth . "db=" . $this->database . "&rp=autogen&precision=s&consistency=one",
                    $fields,
                    implode( "\n", $postData ), 204
                );
            }
        }
    }

    public function dropTable( $targetItemName )
    {
        Logger::log( Logger::LEVEL_INFO, $targetItemName . ": drop influxdb messurement" );

        $fields = array( "Content-Type: text/plain" );
        if( $this->user ) 
        {
            $auth = "u=" . $this->user . "&p=" . $this->pass . "&";
        }
        else 
        {
            $auth = "";
            $fields[] = "Authorization: Token " . $this->pass;
        }

        Request::makeRequest(
            "http://" . $this->host . ":" . $this->port . "/query?" . $auth . "chunked=true&db=" . $this->database . "&epoch=ns&q=DROP+SERIES+FROM+%22" . $targetItemName . "%22",
            $fields,
            "", 200 );

        //Logger::log( Logger::LEVEL_INFO,  "Drop Item done" );
    }
}
