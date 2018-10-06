<?php

class RestConnectorOpenhab
{
    private $ip;
    private $port;

    /**
     * Converter constructor.
     */
    public function __construct( $ip, $port )
    {
        $this->ip = $ip;
        $this->port = $port;
    }
    
    public function getItemTypes()
    {
        $result = Request::makeRequest( "http://" . $this->ip . ":" . $this->port . "/rest/items?recursive=false",
            array( "Accept: application/json" ),
            null,
            200
        );

        $itemTypes = array();
        $entries = json_decode( $result );
        foreach( $entries as $entry )
        {
            $itemTypes[$entry->{'name'}] = $entry->{'type'};
        }
        
        return $itemTypes;
    }

    public function getItems( $allowedItems, $allowedGroups )
    {
        $result = Request::makeRequest( "http://" . $this->ip . ":" . $this->port . "/rest/items?recursive=false",
            array( "Accept: application/json" ),
            null,
            200
        );

        $chartEntries = array();
        $entries = json_decode( $result );
        foreach( $entries as $entry )
        {
            if( !empty( $allowedItems ) && !in_array( $entry->{'name'}, $allowedItems ) )
            {
                continue;
            }
            
            foreach( $entry->{'groupNames'} as $group )
            {
                if( empty( $allowedGroups ) || in_array( $group, $allowedGroups ) )
                {
                    $chartEntries[$entry->{'name'}] = $entry->{'name'};
                    break;
                }
            }
        }
        
        return $chartEntries;
    }
    
    public function getItemGroups()
    {
        $result = Request::makeRequest( "http://" . $this->ip . ":" . $this->port . "/rest/items?recursive=false",
            array( "Accept: application/json" ),
            null,
            200
        );

        $chartEntries = array();
        $entries = json_decode( $result );
        foreach( $entries as $entry )
        {
            $chartEntries[$entry->{'name'}] = $entry->{'groupNames'};
        }
        
        return $chartEntries;
    }
}
