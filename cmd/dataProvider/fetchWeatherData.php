<?php
include dirname(__FILE__) . "/../../_lib/init.php";

$mysql_db = Setup::getOpenHabMysql();
$openhab_rest = Setup::getOpenHabRest();
$location = Setup::getGeoLocation();
$auth = Setup::getWeatherAuth();

$forecast_url = 'https://point-forecast.weather.mg/search?locatedAt={location}&validPeriod={period}&fields={fields}&validFrom={from}&validUntil={to}';
$forecast_config = array(
	'PT0S' => array( 
		"airTemperatureInCelsius", 
		"feelsLikeTemperatureInCelsius", 
        "relativeHumidityInPercent",
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

$current_url = 'https://point-observation.weather.mg/search?locatedAt={location}&observedPeriod={period}&fields={fields}&observedFrom={from}&observedUntil={to}';
$current_config = array(
	'PT0S' => array( 
        "airTemperatureInCelsius", 
		"feelsLikeTemperatureInCelsius",
		"relativeHumidityInPercent",
		"windSpeedInKilometerPerHour",
		"windDirectionInDegree",
		"effectiveCloudCoverInOcta"
	)
);

$collect_forcasts = array(
	'0' => array(
        "airTemperatureInCelsius" => 'pOutdoor_Weather_Current_Temperature',
		"effectiveCloudCoverInOcta" => 'pOutdoor_Weather_Current_Cloud_Cover'
	),
	'4' => array(
		"airTemperatureInCelsius" => 'pOutdoor_Weather_Forecast_Temperature_4h', 
		"effectiveCloudCoverInOcta" => 'pOutdoor_Weather_Forecast_Cloud_Cover_4h'
	),
	'8' => array(
		"airTemperatureInCelsius" => 'pOutdoor_Weather_Forecast_Temperature_8h', 
		"effectiveCloudCoverInOcta" => 'pOutdoor_Weather_Forecast_Cloud_Cover_8h'
	),
);

$autorization_url = "https://auth.weather.mg/oauth/token";

date_default_timezone_set('Europe/Berlin');

//2018-04-20T11:00:00.000Z
$date = new DateTime();
$from_forecast = $date->format('c');

$diff = new DateInterval('PT169H');
$date = new DateTime();
$date->add($diff);
$to_forecast = $date->format('c');
	

$date = new DateTime();
//$date = new DateTime('2020-09-16T18:59:59');
$to_current = $date->format('c');

$diff = new DateInterval('PT2H');
$date = new DateTime();
//$date = new DateTime('2020-09-16T18:59:59');
$date->sub($diff);
$from_current = $date->format('c');
//echo $from . " - ".$to . "\n";

$token = getAutorization($autorization_url,$auth);
if( $token )
{
    fetchCurrent( $token, $mysql_db, $current_config, $current_url, $location, $from_current, $to_current );
    fetchForecast( $token, $mysql_db, $forecast_config, $forecast_url, $location, $from_forecast, $to_forecast );
    updateOpenhab( $collect_forcasts, $mysql_db, $openhab_rest );
}

function updateOpenhab( $collect_forcasts, $mysql_db, $openhab_rest )
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
            $openhab_rest->updateItem($openhab_item,$fields[$field]);
        }
    }
}

function fetchCurrent( $token, $mysql_db, $config, $url, $location, $from, $to )
{
    $_location = $location->getLongitude() . "," . $location->getLatitude();
	$entries = array();
	foreach( $config as $period => $fields )
	{
		$_url = $url;
		$_url = str_replace("{location}",$_location,$_url);
		$_url = str_replace("{period}",$period,$_url);
		$_url = str_replace("{fields}",implode(",",$fields),$_url);
		
		$_url = str_replace("{from}",urlencode($from),$_url);

		$_url = str_replace("{to}",urlencode($to),$_url);

		$data = fetch($_url,$token);
          
		if( !$data ) throw new Exception("unable to parse result from " . $_url );
		if( !isset($data->{'observations'}) ) throw new Exception("unable to get observations from " . $_url . " " . print_r($data,true) );
		
		$i = 0;
		foreach( $data->{'observations'} as $observation )
		{
            if( count((array)$observation) != 10 )
            {
                continue;
            }
            
			$key = $observation->{'observedFrom'};
		
            $update_values = array();
            foreach( $fields as $field )
            {
                $update_values[] = "`".$field."`='".$observation->{$field}."'";
            }
            
            $mysql_db->updateWeatcherData("from_unixtime(".strtotime($key).")",$update_values);

            $i++;
        }
        
        if( $i == 0 )
        {
            print_r($data);
            throw new Exception("wrong current values");
        }
    }
}

function fetchForecast( $token, $mysql_db, $config, $url, $location, $from, $to )
{
    $_location = $location->getLongitude() . "," . $location->getLatitude();
	$entries = array();
	foreach( $config as $period => $fields )
	{
		$_url = $url;
		$_url = str_replace("{location}",$_location,$_url);
		$_url = str_replace("{period}",$period,$_url);
		$_url = str_replace("{fields}",implode(",",$fields),$_url);
		
		$_url = str_replace("{from}",urlencode($from),$_url);

		$_url = str_replace("{to}",urlencode($to),$_url);

		$data = fetch($_url,$token);
		
		if( !$data ) throw new Exception("unable to parse result from " . $_url );
		if( !isset($data->{'forecasts'}) ) throw new Exception("unable to get forecasts from " . $_url . " " . print_r($data,true) );
		
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
        if( count($values) != 16 )
        {
            print_r($values);
            throw new Exception("wrong forecast values");
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
        
        $mysql_db->insertWeatcherData($insert_values,$update_values);
        
        //echo $sql."\n";
    }
}

function fetch($url,$token)
{
	$c = curl_init();
	
	curl_setopt($c, CURLOPT_URL, $url );
	curl_setopt($c, CURLOPT_HTTPHEADER, array( "Authorization: Bearer " . $token ));
    //curl_setopt($c, CURLOPT_HTTPAUTH, CURLAUTH_BEARER );
    //curl_setopt($c, CURLOPT_XOAUTH2_BEARER, $token );
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_HEADER, true);  

    //curl_setopt($c, CURLINFO_HEADER_OUT, true);
    
	$response = curl_exec($c);
	
	$status = curl_getinfo($c, CURLINFO_RESPONSE_CODE);
	
    $header_size = curl_getinfo($c, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $content = substr($response, $header_size);

    //$information = curl_getinfo($c,CURLINFO_HEADER_OUT);
	//print_r($information);

    curl_close($c);

	if( empty( $content ) ) 
	{
        throw new Exception( $url . " request failed with a " . $status . " and no result" );
	}

	$data = json_decode($content);
	
	if( $status != '200' )
	{
        throw new Exception( $url . " failed with a " . $status . "\n" . print_r( $data, true ) );
	}
	
	return $data;
}

function getAutorization($url,$auth)
{
	$c = curl_init();
	
	curl_setopt($c, CURLOPT_URL, $url );
    //curl_setopt($c, CURLOPT_HTTPHEADER, array( 'Content-Type: application/x-www-form-urlencoded; charset=utf-8' ));
    curl_setopt($c, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($c, CURLOPT_USERPWD, $auth->getUsername() . ":" . $auth->getPassword());
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($c, CURLOPT_POST, true);
	curl_setopt($c, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($c, CURLOPT_HEADER, true);  

	//curl_setopt($c, CURLINFO_HEADER_OUT, true);
	
	$response = curl_exec($c);
	
	$status = curl_getinfo($c, CURLINFO_RESPONSE_CODE);
	
    $header_size = curl_getinfo($c, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $content = substr($response, $header_size);

    //print_r($status);
	//print_r($content);
	
	//$information = curl_getinfo($c,CURLINFO_HEADER_OUT);
	//print_r($information);
	curl_close($c);
	
	if( empty( $content ) ) 
	{
        throw new Exception( "Authorisation failed with a " . $status . " and no result" );
	}

	$data = json_decode($content);
	
	if( $status != '200' || !isset( $data->{'access_token'} ) )
	{
        throw new Exception( "Authorisation failed with a " . $status . "\n" . print_r( $data, true ) );
	}

	return $data->{'access_token'};
}
