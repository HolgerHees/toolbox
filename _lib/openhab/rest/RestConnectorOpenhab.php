<?php

class RestConnectorOpenhab
{
    private $ip;
    private $port;

    /**
     * Converter constructor.
     */
    public function __construct( $ip, $port, $protocol )
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->protocol = $protocol;
    }
    
    public function getItemTypes()
    {
        $result = Request::makeRequest( $this->protocol . "://" . $this->ip . ":" . $this->port . "/rest/items?recursive=false",
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
        $result = Request::makeRequest( $this->protocol . "://" . $this->ip . ":" . $this->port . "/rest/items?recursive=false",
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
        $result = Request::makeRequest( $this->protocol . "://" . $this->ip . ":" . $this->port . "/rest/items?recursive=false",
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
    
    public function updateItem($item,$value)
    {
        $result = Request::makeRequest( $this->protocol . "://" . $this->ip . ":" . $this->port . "/rest/items/" . $item,
            array( "Accept: application/json", "Content-Type: text/plain" ),
            $value,
            200
        );
        
        return $result;
    }
}
