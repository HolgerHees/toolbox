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
        $this->query( "DELETE FROM items WHERE `itemname` = '" . $itemName . "'" );
    }

    public function selectItemMap()
    {
        $entries = array();

        $result = $this->query( "SELECT * FROM items" );

        if( $result->num_rows > 0 )
        {
            while( $data = $result->fetch_assoc() )
            {
                $entries[$data['itemname']] = "Item" . $data['itemid'];
            }
        }
        return $entries;
    }

    public function alterItemTimestamp( $itemTable )
    {
        $this->query( "ALTER TABLE `" . $itemTable . "` CHANGE `time` `time` TIMESTAMP(3) on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP" );
    }
    
    public function countItemValues( $itemTable, $from, $to )
    {
      $sql = "SELECT COUNT(*) AS 'count' FROM " . $itemTable;
      if( $from != null or $to != null )
      {
        $sql .= " WHERE";
        if( $from != null ) 
        {
          $sql .= "`time` >= '".$from->format("Y-m-d H:i:s")."'";
          if( $to != null ) $sql .= "AND ";
        }
        if( $to != null ) $sql .= "`time` <= '".$to->format("Y-m-d H:i:s")."'";
      }
      
      $result = $this->query( $sql );
      if( $result->num_rows > 0 )
      {
          return $result->fetch_assoc()['count'];
      }
      
      return -1;
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
    
    public function insertItemData( $table, $insert_values )
    {
        foreach( array_chunk( $insert_values, 1000 ) as $_values )
        {
            $rows = array();
            foreach( $_values as $_value )
            {
                $rows[] = "(FROM_UNIXTIME(" . $_value[1] . "),'" . $_value[0] . "')"; 
            }
            
            $sql = "INSERT INTO " . $table . " (`time`, `value`) VALUES " . implode( ",", $rows );
            $this->query( $sql );
        }
    }

    public function deleteItemData( $table, $refTime )
    {
        $sql = "DELETE FROM " . $table . " WHERE `time` < FROM_UNIXTIME(" . $refTime . ")";
        $this->query( $sql );
    }
}
