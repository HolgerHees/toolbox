<?php
include dirname(__FILE__) . "/../_lib/init.php";

$mysql_db = Setup::getOpenHabMysql();

$from = new DateTime();
$hour = $from->format("H");
if( $hour >= 22 ) $from->setTime( 22, 0, 0 );
else if( $hour >= 17 ) $from->setTime( 17, 0, 0 );
else if( $hour >= 12 ) $from->setTime( 12, 0, 0 );
else if( $hour >= 7 ) $from->setTime( 7, 0, 0 );
else if( $hour >= 2 ) $from->setTime( 2, 0, 0 );
else 
{
	$from->sub(new DateInterval('PT24H'));
	$from->setTime( 22, 0, 0 );
}

$to = clone $from;
$to->add(new DateInterval('PT24H'));

$list = $mysql_db->getWeatherDataList( $from, $to );


/******* SUMMARY ************/
/*$from = new DateTime();
$from->setTime(0,0,0);
$to = clone $from;
$to->setTime(23,59,59);

$dayList = $mysql_db->getWeatherDataList($from, $to);*/
list( $minTemperature, $maxTemperature, $maxWindSpeed, $sumSunshine, $sumRain ) = Weather::calculateSummary( $list );

/******* OVERVIEW ************/
$blockConfigs = array(
	'22' => array( 'title' => "Nachts", 'class' => 'night' ),
	'17' => array( 'title' => "Abends", 'class' => 'evening' ),
	'12' => array( 'title' => "Mittags", 'class' => 'lunch' ),
	'07' => array( 'title' => "Früh", 'class' => 'morning' ),
	'02' => array( 'title' => "Nachts", 'class' => 'night' ),
);

//echo print_r($list,true);

$values = array();
$block_entry = false;
for( $i = 0; $i < count($list); $i++ )
{
	$entry = $list[$i];

	$hour = $entry['datetime']->format("H");
	
	if( isset($blockConfigs[$hour]) )
	{
        if($block_entry) 
        {
            $block_entry['to'] = $entry['datetime'];
            $values[] = $block_entry;
        }
        $block_entry = Weather::initBlockData($entry['datetime']);
        $block_entry = array_merge( $block_entry, $blockConfigs[$hour] );
	}

	Weather::applyBlockData( $block_entry, $entry );
	
	// don't collect more then 4 blocks
	if( count( $values ) == 4 )
	{
		break;
	}
}

//echo print_r($values,true);
?>
<div class="weatherForecast weatherTodayForecast">
	<?php /*echo time();*/ ?>
	<div class="headlines">
<?php foreach( $values as $blockData ){ ?>
        <div class="cell"><?php echo $blockData['title']; ?></div>
<?php } ?>
	</div>
	<div class="details">
<?php foreach( $values as $blockData ){ ?>
		<div class="cell <?php echo $blockData['class']; ?>">
            <div class="time"><div class="from"><?php echo Weather::formatHour($blockData['from']) . ' -</div><div class="to">' . Weather::formatHour($blockData['to']) ; ?></div></div>
            <div class="sun"><?php echo Weather::convertOctaToSVG($blockData['from'],$blockData,1,"light");?>
            </div>
            <div class="value temperature"><div class="main"><?php echo $blockData['airTemperatureInCelsius']; ?></div><div class="sub">°C</div></div>
            <div class="value precipitationProbability">
                <?php echo Weather::getSVG('rain', 'self_rain_grayscaled') . "<div class=\"main\">" . $blockData['precipitationProbabilityInPercent']; ?></div><div class="sub">%</div>
            </div>
            <div class="value precipitationAmount">
                <div class="main"><?php echo $blockData['precipitationAmountInMillimeterSum']; ?></div><div class="sub">mm</div>
            </div>
		</div>
<?php } ?>
	</div>
	<div class="summary">
		<div class="cell"><div class="txt">Min.:</div><div class="icon temperature"><?php echo Weather::getSVG('temperature', 'self_temperature_grayscaled') . "</div><div class=\"value\">" . $minTemperature; ?> °C</div></div>
		<div class="bullet">•</div>
		<div class="cell"><div class="txt">Max.:</div><div class="icon temperature"><?php echo Weather::getSVG('temperature', 'self_temperature_grayscaled') . "</div><div class=\"value\">" . $maxTemperature; ?> °C</div></div>
		<div class="bullet">•</div>
		<div class="cell"><div class="txt">Max.:</div><div class="icon wind"><?php echo Weather::getSVG('wind', 'self_wind_grayscaled') . "</div><div class=\"value\">" . $maxWindSpeed; ?> km/h</div></div>
		<div class="bullet">•</div>
		<div class="cell"><div class="txt">Sum:</div><div class="icon rain"><?php echo Weather::getSVG('rain', 'self_rain_grayscaled') . "</div><div class=\"value\">" . $sumRain; ?> mm</div></div>
		<div class="bullet">•</div>
		<div class="cell"><div class="txt">Dauer:</div><div class="icon sun"><?php echo Weather::getSVG('sun', 'self_sun_grayscaled') . "</div><div class=\"value\">" . Weather::formatDuration( $sumSunshine ); ?></div></div>
	</div>
</div>
