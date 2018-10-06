<?php

abstract class DBConnector
{
    /* @var $connection mysqli */
    private $connection;

    /**
     * DBConnectorOpenhab constructor.
     * @param $config
     */
    public function __construct( $host, $user, $pass, $dbname )
    {
        $this->connect( $host, $user, $pass, $dbname );
    }

    /**
     * @param mysqli_result $result
     * @return array|null
     */
    protected function fetch( mysqli_result $result )
    {
        if( $result->num_rows > 0 ) return $result->fetch_assoc();

        return null;
    }

    /**
     * @param string $value
     * @return string
     */
    protected function escape( $value )
    {
        return $this->connection->escape_string( $value );
    }

    /**
     * @return int
     */
    protected function getLastInsertedId()
    {
        return $this->connection->insert_id;
    }

    /**
     * @param $sql
     * @return mysqli_result
     */
    protected function query( $sql )
    {
        return $this->_query( $sql, true );
    }

    /**
     * @param $sql
     * @param bool $reconnect
     * @return mysqli_result
     */
    private function _query( $sql, $reconnect )
    {
        //$start = microtime(true);
        $result = @$this->connection->query( $sql );
        //$end = microtime(true);

        //Logger::log(Logger::LEVEL_INFO, $end-$start . " " . $sql);

        if( $result === false )
        {
            // Server has gone away
            //if( $reconnect && $this->connection->errno == 2006 && $this->connect() )
            //{
            //    return $this->_query( $sql, false );
            //}
            //else
            //{
            Logger::fatal( 'Invalid query: ' . $this->connection->errno . " " . $this->connection->error . " for query '" . $sql . "'" );
            //}
        }

        return $result;
    }

    /**
     * @return bool
     */
    private function connect( $host, $user, $pass, $dbname )
    {
        $this->connection = new mysqli( $host, $user, $pass );
        if( $this->connection->connect_error )
        {
            Logger::fatal( 'Can\'t connect: ' . $this->connection->connect_error );
            return false;
        }

        $db_selected = $this->connection->select_db( $dbname );
        if( !$db_selected )
        {
            Logger::fatal( 'Can\'t use ' . $dbname . ' : ' . $this->connection->connect_error );
            return false;
        }

        return true;
    }
}
