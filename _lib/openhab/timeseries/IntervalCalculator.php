<?php

class IntervalCalculator
{
    private $db;
    private $detailedLog;
    private $timezone;

    private $startTime;

    private $minLogTime;

    private $TWO_YEARS_OLD = 60 * 60 * 24 * 365 * 2;
    private $ONE_YEARS_OLD = 60 * 60 * 24 * 365;
    private $ONE_MONTH_OLD = 60 * 60 * 24 * 31;
    private $ONE_WEEK_OLD = 60 * 60 * 24 * 7;

    /**
     * Converter constructor.
     */
    public function __construct( DBConnectorOpenhab $db, $timezone, $detailedLog )
    {
        date_default_timezone_set( $timezone->getName() );

        $this->db = $db;
        $this->timezone = $timezone;
        $this->detailedLog = $detailedLog;

        $this->startTime = time();

        $this->minLogTime = time() - ( 60 * 24 * 60 * 60 );
    }

    public function generateValues( $name, IntervalConfig $config, $itemMap )
    {
        switch( $config->getType() )
        {
            case "interval":
                return $this->createIntervalValues( $name, $itemMap[$config->getSourceItem()], $config );
            case "daily":
                return $this->createDailyValues( $name, $itemMap[$config->getSourceItem()], $config );
            default:
                throw new RuntimeException("Non supported type '" . $config->getType() . "'" );
        }
    }

    private function createIntervalValues( $name, $sourceItemId, IntervalConfig $config )
    {
        Logger::log( Logger::LEVEL_INFO, $name . ": create custom interval data" );

        $cron_r = explode( " ", $config->getCron() );

        $dbEntries = $this->db->selectItemEntries( $sourceItemId );

        $intervalInSeconds = $this->generateIntervalInSeconds( $cron_r );
        $currentTimestamp = $this->generateIntervalStartTime( $dbEntries[0][0], $intervalInSeconds, $cron_r );

        $currentDbIndex = 0;
        $lastEntryValue = 0;

        $endTime = time();

        $entries = array();

        while( $currentTimestamp <= $endTime )
        {
            for( $i = $currentDbIndex; $i < count( $dbEntries ); $i++ )
            {
                if( $currentTimestamp < $dbEntries[$i][0] )
                {
                    $currentDbIndex = $i - 1;
                    break;
                }
            }

            if( $i == count( $dbEntries ) )
            {
                $currentDbIndex = count( $dbEntries ) - 1;
            }

            $currentTimestampFormatted = date( "Y-m-d H:i:s", $currentTimestamp );

            if( $currentTimestamp - $config->getOutdatetTime() > $dbEntries[$currentDbIndex][0] )
            {
                if( $lastEntryValue != 0 )
                {
                    if( $this->isDetailedLog( $currentTimestamp ) ) Logger::log( Logger::LEVEL_INFO, $name . ": DUMMY " . $currentTimestampFormatted );

                    $entries[$currentTimestampFormatted] = array( $currentTimestamp, 0 );

                    $lastEntryValue = 0;
                }
            }
            else
            {
                $currentValue = $this->getIntervalValue( $intervalInSeconds, $config, $currentTimestamp, $currentDbIndex, $dbEntries, $dbEntries[$currentDbIndex][0], $dbEntries[$currentDbIndex][1] );

                $currentValue = $this->convertValue( $name, $currentValue );

                if( abs( $currentValue ) > $config->getMaxValue() || $currentValue < 0 )
                {
                    Logger::log( Logger::LEVEL_WARNING, $name . ": SKIP value=" . $currentValue . ", time=" . $currentTimestamp . ", date=" . $currentTimestampFormatted );

                    //throw new RuntimeException();
                }
                else if( $lastEntryValue != $currentValue )
                {
                    if( $this->isDetailedLog( $currentTimestamp ) ) Logger::log( Logger::LEVEL_INFO, $name . ": CALC " . $currentTimestampFormatted . ", value " . $currentValue );

                    $entries[$currentTimestampFormatted] = array( $currentTimestamp, $currentValue );

                    $lastEntryValue = $currentValue;
                }
            }

            $currentTimestamp = $currentTimestamp + $intervalInSeconds;
        }

        Logger::log( Logger::LEVEL_INFO, $name . ": " . count( $entries ) . " created" );

        return $entries;
    }

    private function createDailyValues( $name, $sourceItemId, IntervalConfig $config )
    {
        Logger::log( Logger::LEVEL_INFO, $name . ": create daily interval data" );

        $cron_r = explode( " ", $config->getCron() );

        $dbEntries = $this->db->selectItemEntries( $sourceItemId );

        $currentValue = 0;

        $lastEntryTime = 0;
        $lastEntryKey = 0;
        $lastEntryValue = 0;

        $lastValue = 0;

        $entries = array();

        for( $i = 0; $i < count( $dbEntries ); $i++ )
        {
            $dbEntry = $dbEntries[$i];

            $entryTime = $dbEntry[0];
            $entryKey = $this->generateDailyKey( $entryTime );
            $entryValue = $dbEntry[1];

            $this->createDailyDummy( $name, $lastEntryKey, $lastEntryTime, $entryKey, $entryTime, $cron_r, $lastValue, $currentValue, $entries );

            $diff = $entryValue - $lastEntryValue;

            $diff = $this->convertValue( $name, $diff );

            $entryTimeFormatted = date( "Y-m-d H:i:s", $entryTime );

            if( abs( $diff ) > $config->getMaxValue() || $diff < 0 )
            {
                Logger::log( Logger::LEVEL_WARNING, $name . ": SKIP value=" . $diff . ", time=" . $entryTime . ", date=" . $entryTimeFormatted );
            }
            else
            {
                $currentValue += $diff;

                if( $lastValue != $currentValue )
                {
                    if( $this->isDetailedLog( $entryTime ) ) Logger::log( Logger::LEVEL_INFO, $name . ": REAL " . $entryTimeFormatted . ", value " . round( $currentValue, 2 ) );
                    $entries[$entryTimeFormatted] = array( $entryTime, $currentValue );
                    $lastValue = $currentValue;
                }
            }

            $lastEntryTime = $entryTime;
            $lastEntryKey = $entryKey;
            $lastEntryValue = $entryValue;
        }

        $entryTime = time();
        $entryKey = $this->generateDailyKey( $entryTime );

        $this->createDailyDummy( $name, $lastEntryKey, $lastEntryTime, $entryKey, $entryTime, $cron_r, $lastValue, $currentValue, $entries );

        Logger::log( Logger::LEVEL_INFO, $name . ": " . count( $entries ) . " created" );

        return $entries;
    }

    private function createDailyDummy( $name, $lastEntryKey, $lastEntryTime, $entryKey, $entryTime, $cron_r, &$lastValue, &$currentValue, &$entries )
    {
        if( $lastEntryKey != 0 && $lastEntryKey != $entryKey )
        {
            $dummyTimestamp = $this->generateDailyDummy( $lastEntryTime, $cron_r );

            if( $entryTime > $dummyTimestamp )
            {
                if( $lastValue != 0 )
                {
                    if( $this->isDetailedLog( $dummyTimestamp ) ) Logger::log( Logger::LEVEL_INFO, $name . ": DUMMY " . date( "Y-m-d H:i:s", $dummyTimestamp ) );
                    $entries[date( "Y-m-d H:i:s", $dummyTimestamp )] = array( $dummyTimestamp, 0 );
                    $lastValue = 0;
                }
            }

            $currentValue = 0;
        }
    }

    private function getIntervalValue( $intervalInSeconds, IntervalConfig $config, $currentTimestamp, $i, $dbEntries, $entryTime, $entryValue )
    {
        // look 3 times longer back
        $minAllowedTimestamp = $currentTimestamp - $config->getMessureTime();

        $startTimestampInMillis = 0;
        $startValue = 0;

        $itemCount = 0;

        //name: Electricity_Meter, new value: 780.0, from: 17157.85 (2018-02-26T13:35:15.000+01:00), to: 17157.98 (2018-02-26T13:45:14.630+01:00)
        //$isDebug = ( $currentTimestamp > strtotime("2018-02-26 13:42:00") );

        $max = 0;

        for( $j = $i - 1; $j > 0; $j-- )
        {
            $itemCount = $itemCount + 1;

            $startValue = $dbEntries[$j][1];

            if( $max < $startValue ) $max = $startValue;

            if( $dbEntries[$j][0] < $minAllowedTimestamp )
            {
                //Logger::log( Logger::LEVEL_INFO, "Time exceeded" );
                //echo "time exceeded\n";
                $startTimestampInMillis = $startTimestampInMillis - $intervalInSeconds;

                if( $dbEntries[$j][0] > $startTimestampInMillis )
                {
                    $startTimestampInMillis = $dbEntries[$j][0];
                }
                break;
            }
            else if( $itemCount >= $config->getMessureItemCount() )
            {
                //Logger::log( Logger::LEVEL_INFO, "Count exceeded " . $startValue . " " . $entryValue );
                //echo "count exceeded\n";
                $startTimestampInMillis = $dbEntries[$j][0];
                break;
            }
            else
            {
                $startTimestampInMillis = $dbEntries[$j][0];
            }
        }

        switch( $config->getValueTime() )
        {
            case "DIFF":
                $result = $entryValue - $startValue;
                break;
            case "MAX":
                $result = $max;
                break;
            default:
                $durationInSeconds = $entryTime - $startTimestampInMillis;
                $result = ( ( $entryValue - $startValue ) / $durationInSeconds ) * $config->getValueTime();
                break;
        }

        /*if( date("Y-m-d H:i:s", $currentTimestamp) == '2016-07-03 00:35:15' )
        {
            echo $result . "\n";
            echo $result * 12 * 1000 . "\n";
            echo ( $entryValue )."\n";
            echo ( $startValue )."\n";
            throw new RuntimeException();
        }*/

        return $result;
    }

    private function generateDailyKey( $entryTime )
    {
        return date( "Y-m-d", $entryTime );
    }

    private function generateDailyDummy( $entryTime, $cron_r )
    {
        $dummyTimestamp = $entryTime + 86400;

        $dummyDate = new DateTime();
        $dummyDate->setTimezone( $this->timezone );
        $dummyDate->setTimestamp( $dummyTimestamp );

        $seconds = $cron_r[0];
        $minutes = $seconds == 0 ? 5 : 0;
        $dummyDate->setTime( 0, $minutes, $seconds );

        return $dummyDate->getTimestamp();
    }

    private function generateIntervalInSeconds( $cron_r )
    {
        if( strpos( $cron_r[1], "/" ) !== false )
        {
            $intervalInSeconds = explode( "/", $cron_r[1] )[1] * 60;
        }
        else if( $cron_r[1] == '0' && $cron_r[2] == '*' )
        {
            $intervalInSeconds = 60 * 60;
        }
        else
        {
            print_r( $cron_r );
            throw new RuntimeException( "Could not detect interval seconds: UNKOWN INTERVAL" );
        }

        return $intervalInSeconds;
    }

    private function generateIntervalStartTime( $entryTime, $intervalInSeconds, $cron_r )
    {
        $dummyTimestamp = $entryTime + $intervalInSeconds;

        $dummyDate = new DateTime();
        $dummyDate->setTimezone( $this->timezone );
        $dummyDate->setTimestamp( $dummyTimestamp );

        if( $cron_r[1] == "*/5" )
        {
            $minutes = ( (int) ( $dummyDate->format( "i" ) / 5 ) ) * 5;
        }
        else if( $cron_r[1] == "*/15" )
        {
            $minutes = ( (int) ( $dummyDate->format( "i" ) / 15 ) ) * 15;
        }
        else if( $cron_r[1] == "*/30" )
        {
            $minutes = ( (int) ( $dummyDate->format( "i" ) / 30 ) ) * 30;
        }
        else if( $cron_r[1] == "0" )
        {
            $minutes = 0;
        }
        else
        {
            print_r( $cron_r );
            throw new RuntimeException( "Could not initialize start time: UNKOWN INTERVAL" );
        }

        $dummyDate->setTime( date( "H", $dummyTimestamp ), $minutes, $cron_r[0] );

        return $dummyDate->getTimestamp();
    }

    private function convertValue( $name, $value )
    {
        switch( $name )
        {
            case "Electricity_Current_Consumption":
                return round( $value * 12 * 1000, 0 );
            case "Rain_Garden_Current":
                return round( ( $value * 295 / 1000 ), 2 );
            case "Rain_Garden_Current_Daily":
                return round( ( $value * 295 / 1000 ), 2 );
            default:
                return $value;
        }

    }

    public function fillHourlyData( $entryName, $entries )
    {
        Logger::log( Logger::LEVEL_INFO, $entryName . ": fill hourly data" );

        //$this->reset();

        $lastEntryTime = 0;
        $lastEntryValue = 0;

        $data = array();

        foreach( $entries as $entry )
        {
            $entryTime = $entry[0];
            $entryValue = $entry[1];

            $this->fillHourlyDummyData( $data, $entryTime, $lastEntryTime, $lastEntryValue );

            if( $this->isDetailedLog( $entryTime ) ) Logger::log( Logger::LEVEL_INFO, "REAL: " . date( "Y-m-d H:i:s", $entryTime ) . " (" . $entryTime . "), VALUE: " . $entryValue );
            $data[] = array( $entryValue, $entryTime );

            $lastEntryTime = $entryTime;
            $lastEntryValue = $entryValue;

            //$this->messure($data,$entryTime);
        }

        $this->fillHourlyDummyData( $data, time(), $lastEntryTime, $lastEntryValue );

        Logger::log( Logger::LEVEL_INFO, $entryName . ": " . count( $data ) . " created" );

        return $data;
    }

    private function fillHourlyDummyData( &$data, $entryTime, $lastEntryTime, $lastEntryValue )
    {
        $intervalHours = $this->getIntervalHours( $this->startTime - $entryTime );
        $intervalSeconds = $intervalHours * 60 * 60;

        // insert dummy values for every hour
        if( $lastEntryTime != 0 && $entryTime - $lastEntryTime > $intervalSeconds )
        {
            $dummyHour = date( "G", $lastEntryTime ) + $intervalHours;

            $dummyDate = new DateTime();
            $dummyDate->setTimestamp( $lastEntryTime );
            $dummyDate->setTime( $dummyHour, 0, 0 );

            $dummyTimestamp = $dummyDate->getTimestamp();

            while( $dummyTimestamp < $entryTime )
            {
                if( $this->isDetailedLog( $dummyTimestamp ) ) Logger::log( Logger::LEVEL_INFO, "DUMMY: " . date( "Y-m-d H:i:s", $dummyTimestamp ) . " (" . $dummyTimestamp . "), VALUE: " . $lastEntryValue . ", INTERVAL: " . $intervalHours );

                $data[] = array( $lastEntryValue, $dummyTimestamp );

                $dummyTimestamp = $dummyTimestamp + $intervalSeconds;

                //$this->messure($data,$dummyTimestamp);
            }
        }
    }

    private function getIntervalHours( $ageInSeconds )
    {
        if( $ageInSeconds > $this->TWO_YEARS_OLD )
        {
            return 48;
        }
        else if( $ageInSeconds > $this->ONE_YEARS_OLD )
        {
            return 24;
        }
        else if( $ageInSeconds > $this->ONE_MONTH_OLD )
        {
            return 12;
        }
        else if( $ageInSeconds > $this->ONE_WEEK_OLD )
        {
            return 6;
        }

        //Logger::log(Logger::LEVEL_INFO,"AGE: " . $age );
        return 1;
    }

    private function isDetailedLog( $currentTimestamp )
    {
        return $this->detailedLog && ( !$this->minLogTime || $currentTimestamp > $this->minLogTime );
    }
}
