<?php

class DBConnectorOpenhab extends DBConnector
{
    public function cleanTable( $itemTable )
    {
        $this->query( "TRUNCATE TABLE " . $itemTable );
    }

    public function dropItemTable( $itemTable, $itemName )
    {
        $this->query( "DROP TABLE " . $itemTable );
        $this->query( "DELETE FROM items WHERE `ItemName` = '" . $itemName . "'" );
    }

    public function selectItemMap()
    {
        $entries = array();

        $result = $this->query( "SELECT * FROM items" );

        if( $result->num_rows > 0 )
        {
            while( $data = $result->fetch_assoc() )
            {
                $entries[$data['ItemName']] = "Item" . $data['ItemId'];
            }
        }
        return $entries;
    }

    public function alterItemTimestamp( $itemTable )
    {
        $this->query( "ALTER TABLE `" . $itemTable . "` CHANGE `time` `time` TIMESTAMP(3) on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP" );
    }
    
    public function showItemFields( $itemTable )
    {
        $entries = array();

        $result = $this->query( "SHOW FIELDS FROM " . $itemTable );
        
        if( $result->num_rows > 0 )
        {
            while( $data = $result->fetch_assoc() )
            {
                if( strtolower( $data['Field'] ) == 'time' )
                {
                    $entries[ 'Time' ] = $data['Type'];
                }
                else if( strtolower( $data['Field'] ) == 'value' )
                {
                    $entries[ 'Value' ] = $data['Type'];
                }
                
                $entries[ 'ColumnNames' ] = array();
                if( strtolower( $data['Field'] ) == 'time' && $data['Field'] != 'time' )
                {
                    $entries[ 'ColumnNames' ]['time'] = $data['Field'];
                }
                if( strtolower( $data['Field'] ) == 'value' && $data['Field'] != 'value' )
                {
                    $entries[ 'ColumnNames' ]['value'] = $data['Field'];
                }
            }
        }
        return $entries;
    }

    public function selectItemEntries( $table )
    {
        $entries = array();

        $result = $this->query( "SELECT FLOOR(UNIX_TIMESTAMP(`time`)) AS 'time', `value` AS 'value' FROM " . $table . " ORDER BY `Time` ASC" );

        if( $result->num_rows > 0 )
        {
            while( $data = $result->fetch_assoc() )
            {
                $entries[] = array( $data['time'], $data['value'] );
            }
        }
        return $entries;
    }

    public function selectItemLastEntry( $table )
    {
        $result = $this->query( "SELECT FLOOR(UNIX_TIMESTAMP(`time`)) AS 'time', `value` AS 'value' FROM " . $table . " ORDER BY `Time` DESC LIMIT 1" );

        if( $result->num_rows > 0 )
        {
            while( $data = $result->fetch_assoc() )
            {
                return array( $data['time'], $data['value'] );
            }
        }
        return null;
    }
    
    public function insertWeatcherData( $sql )
    {
        $this->query( $sql );
    }
    
    public function getWeatherData( $offset )
    {
        $result = $this->query( "SELECT * FROM weather_forecast WHERE `datetime` > DATE_ADD(NOW(), INTERVAL " . ( $offset - 1 ) . " HOUR)  ORDER BY `datetime` ASC LIMIT 1" );

        if( $result->num_rows > 0 )
        {
            while( $data = $result->fetch_assoc() )
            {
                $this->prepateDatetime($data);
                return $data;
            }
        }
        return null;
    }
    
    public function getWeatherDataList($from, $to)
    {
		//echo "SELECT * FROM weather_forecast WHERE `datetime` >= '".$from->format("Y-m-d H:i:s")."' AND `datetime` <= '".$to->format("Y-m-d H:i:s")."'  ORDER BY `datetime`";
    
        $result = $this->query( "SELECT * FROM weather_forecast WHERE `datetime` >= '".$from->format("Y-m-d H:i:s")."' AND `datetime` < '".$to->format("Y-m-d H:i:s")."'  ORDER BY `datetime`" );

        if( $result->num_rows > 0 )
        {
			$list = array();
            while( $data = $result->fetch_assoc() )
            {
                $this->prepateDatetime($data);
                $list[] = $data;
            }
            return $list;
        }
        return null;
    }
    
    public function getWeatherDataWeekList()
    {
        $result = $this->query( "SELECT * FROM weather_forecast WHERE `datetime` >= CURDATE() AND `datetime` < DATE_ADD(CURDATE(), INTERVAL 8 DAY)  ORDER BY `datetime`" );

        if( $result->num_rows > 0 )
        {
			$list = array();
            while( $data = $result->fetch_assoc() )
            {
                $this->prepateDatetime($data);
                $list[] = $data;
            }
            return $list;
        }
        return null;
    }

    private function prepateDatetime(&$data)
    {
        $data['datetime'] = DateTime::createFromFormat('Y-m-d H:i:s',$data['datetime']);
        $data['datetime']->setTimezone(Setup::getTimezone());
    }
}
