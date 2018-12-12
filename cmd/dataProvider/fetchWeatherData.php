<?php
include dirname(__FILE__) . "/../../_lib/init.php";
//include "src/helper/Logger.php";
//include "src/helper/Request.php";
//include "src/db/_DBConnector.php";
//include "src/db/DBConnectorOpenhab.php";

//$today_forecast_url = 'https://point-forecast.weather.mg/search?locatedAt=13.62140,52.34772&validPeriod=PT0S&fields=airTemperatureInCelsius,feelsLikeTemperatureInCelsius,windSpeedInKilometerPerHour';

$mysql_db = Setup::getOpenHabMysql();

$location = "13.62140,52.34772";
$auth = "aG9sZ2VyLmhlZXM6TXpLUFY2eGpQSDdHOXNQbg==";

$openhab_ip = "127.0.0.1";
$openhab_port = "8080";

$table = "weather_forecast";

$forecast_config = array(
	'PT0S' => array( 
		"airTemperatureInCelsius", 
		"feelsLikeTemperatureInCelsius", 
		"windSpeedInKilometerPerHour", 
		"windDirectionInDegree", 
		"effectiveCloudCoverInOcta", 
		"thunderstormProbabilityInPercent",
		"freezingRainProbabilityInPercent",
		"hailProbabilityInPercent",
		"snowfallProbabilityInPercent",
		"precipitationProbabilityInPercent",
		// https://www.nodc.noaa.gov/archive/arc0021/0002199/1.1/data/0-data/HTML/WMO-CODE/WMO4677.HTM
		"precipitationType"
	),
	'PT1H' => array(
		"precipitationAmountInMillimeter", 
		"sunshineDurationInMinutes"
	),
	'PT3H' => array(
		"maxWindSpeedInKilometerPerHour"
	)
	
);

$forecast_url = 'https://point-forecast.weather.mg/search?locatedAt={location}&validPeriod={period}&fields={fields}&validFrom={from}&validUntil={to}';

$current_config = array(
	'PT0S' => array( 
		'feelsLikeTemperatureInCelsius',
		'windDirectionInDegree' => 'Wind_Direction',
		'effectiveCloudCoverInOcta' => 'Cloud_Cover_Current',
		'precipitationProbabilityInPercent',
		'temperatureMinInCelsius',
		'temperatureMaxInCelsius'
	)
);

$collect_forcasts = array(
	'0' => array(
		"windDirectionInDegree" => 'Wind_Direction', 
		"effectiveCloudCoverInOcta" => 'Cloud_Cover_Current'
	),
	'4' => array(
		"airTemperatureInCelsius" => 'Temperature_Garden_Forecast4', 
		"effectiveCloudCoverInOcta" => 'Cloud_Cover_Forecast4'
	),
	'8' => array(
		"airTemperatureInCelsius" => 'Temperature_Garden_Forecast8', 
		"effectiveCloudCoverInOcta" => 'Cloud_Cover_Forecast8'
	),
);

$current_url = 'https://point-observation.weather.mg/search?locatedAt={location}&validPeriod={period}&fields={fields}&validFrom={from}&validUntil={to}';

date_default_timezone_set('Europe/Berlin');

//2018-04-20T11:00:00.000Z
$date = new DateTime();
$from = $date->format('c');

//$diff = new DateInterval('P7D');
$diff = new DateInterval('PT169H');
//$diff = new DateInterval('PT24H');
$date->add($diff);
$to = $date->format('c');
	
//echo $from . " - ".$to . "\n";

//fetchCurrent( $auth, $current_config, $current_url, $location, $from, $to );

fetchForecast( $auth, $mysql_db, $table, $forecast_config, $forecast_url, $location, $from, $to );

updateOpenhab( $collect_forcasts, $mysql_db, $openhab_ip, $openhab_port );

function updateOpenhab( $collect_forcasts, $mysql_db, $openhab_ip, $openhab_port )
{
    foreach( $collect_forcasts as $offset => $collect_fields )
    {
        $fields = $mysql_db->getWeatherData( $offset );

        //print_r($fields);

        foreach( $collect_fields as $field => $openhab_item )
        {
        
            if( $openhab_item == 'Wind_Direction' )
            {
                $windDirection = $fields[$field];
                if( $windDirection >= 22.5 && $windDirection < 67.5 ) $windDirection = "Nordost";
                if( $windDirection >= 67.5 && $windDirection < 112.5 ) $windDirection = "Ost";
                if( $windDirection >= 112.5 && $windDirection < 157.5 ) $windDirection = "Südost";
                if( $windDirection >= 157.5 && $windDirection < 202.5 ) $windDirection = "Süd";
                if( $windDirection >= 202.5 && $windDirection < 247.5 ) $windDirection = "Südwest";
                if( $windDirection >= 247.5 && $windDirection < 292.5 ) $windDirection = "West";
                if( $windDirection >= 292.5 && $windDirection < 337.5 ) $windDirection = "Nordwest";
                if( $windDirection >= 337.5 || $windDirection < 22.5 ) $windDirection = "Nord";
                $fields[$field] = $windDirection;
            }
            
            if( $fields[$field] == "0" ) $fields[$field] = "0.0";
        
            //echo "UPDATE: " . $openhab_item . " :" . $fields[$field] . ":\n";
            //echo "http://" . $openhab_ip . ":" . $openhab_port . "/rest/items/" . $openhab_item . "\n";
            $result = Request::makeRequest( "http://" . $openhab_ip . ":" . $openhab_port . "/rest/items/" . $openhab_item,
                array( "Accept: application/json", "Content-Type: text/plain" ),
                $fields[$field],
                200
            );
        }
    }
}

function fetchForecast( $auth, $mysql_db, $table, $config, $url, $location, $from, $to )
{
	$entries = array();
	foreach( $config as $period => $fields )
	{
		$_url = $url;
		$_url = str_replace("{location}",$location,$_url);
		$_url = str_replace("{period}",$period,$_url);
		$_url = str_replace("{fields}",implode(",",$fields),$_url);
		
		$_url = str_replace("{from}",urlencode($from),$_url);

		$_url = str_replace("{to}",urlencode($to),$_url);

		$data = fetch($_url,$auth);
		
		if( !$data )
		{
            echo "unable to parse result from " . $url . "\n";
            continue;
		}
		
		foreach( $data->{'forecasts'} as $forecast )
		{
			$key = $forecast->{'validFrom'};

			if( !isset($entries[$key]) )
			{
				$values = array(); 
				$values['validFrom'] = $forecast->{'validFrom'};
			}
			else
			{
				$values = $entries[$key];
			}
			
			foreach( $fields as $field )
			{
				$values[$field] = $forecast->{$field};
			}
			
			$entries[$key] = $values;
		}
	}
	
	ksort( $entries );
	
	foreach( $config['PT3H'] as $field )
	{
		$value = null;
		
		foreach( $entries as &$values )
		{
			if( isset( $values[$field] ) )
			{
				$value = $values[$field];
			}
			else
			{
				$values[$field] = $value;
			}
		}
	}
	
	// remove 2 first elements and the last element
	$entries = array_slice($entries,2,count($entries)-3);
	
    foreach( $entries as $values )
    {
        if( count($values) != 15 )
        {
            throw new Exception("no values");
        }
        
        $insert_values = array( "`datetime`=from_unixtime(".strtotime($values['validFrom']).")" );
        $update_values = array();
        
        unset($values['validFrom']);

        foreach( $values as $field => $value )
        {
            $sql_setter = "`".$field."`='".$value."'";
            
            $insert_values[] = $sql_setter;
            $update_values[] = $sql_setter;
        }
        
        $sql = "INSERT INTO `" . $table . "` SET " . implode( ",", $insert_values ) . " ON DUPLICATE KEY UPDATE " . implode( ",", $update_values );

        $mysql_db->insertWeatcherData($sql);
        
        //echo $sql."\n";
    }
}

function fetch($url,$auth)
{
	$c = curl_init();
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	$headers = array( 'Authorization: Basic ' . $auth );
	curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($c, CURLOPT_URL, $url );
	$content = curl_exec($c);
	curl_close($c);

	if( empty( $content ) ) 
	{
		echo $url . " has no content" . "\n";
	}

	$data = json_decode($content);
	
	return $data;
}
