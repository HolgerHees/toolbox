<html>
<head>
<meta name="viewport" content="width=device-width,height=device-height,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=0">
<link rel="stylesheet" href="/static/theme/css/theme.css">
<style>
  .mvWidget {
    padding: 0;
    height: 100%;
  }
  .mvWidget .weatherDetailForecast,
  .mvWidget .weatherDetailForecast .hour > div > .temperature > .sub,
  .mvWidget .weatherDetailForecast .hour > div > .info,
  .mvWidget .weatherDetailForecast .hour > div > .time .from,
  .mvWidget .weatherDetailForecast .hour > div > .time .to,
  .mvWidget .weatherForecast .summary .value,
  .mvWidget .weatherForecast .summary .txt,
  .mvWidget .weatherForecast .summary .cell, 
  .mvWidget .weatherForecast .summary .bullet {
    font-size: 14px;
    line-height: 14px;  
  }
  .mvWidget .weatherDetailForecast .hour > div > .temperature > .main {
    font-size: 18px;
    line-height: 18px;
  }.mvWidget .weatherDetailForecast .hour > div > .info {
    line-height: 18px;  
  }
  .mvWidget .weatherDetailForecast .title {
    font-size: 18px;
    line-height: 18px;
  }
  .mvWidget .weatherDetailForecast .summary {
    margin-top: 10px;
    justify-content: left;
  }
  .mvWidget .weatherDetailForecast .summary .cell .txt {
    display: none;
  }
  .mvWidget .weatherDetailForecast .hour > div > .sun
  {
    width: 60px !important;
    height: 60px !important;
    min-width: 60px;
    text-align: center;
  }
  
  .mvWidget .weatherForecast .raindrop_background_1, 
  .mvWidget .weatherForecast .raindrop_background_2, 
  .mvWidget .weatherForecast .raindrop_background_3, 
  .mvWidget .weatherForecast .raindrop_background_4,
  .mvWidget .weatherForecast .snowflake_background_1, 
  .mvWidget .weatherForecast .snowflake_background_2, 
  .mvWidget .weatherForecast .snowflake_background_3, 
  .mvWidget .weatherForecast .snowflake_background_4 {
      height: 7px;
      margin-top: 10px;
      margin-bottom: 11px;
  }
  .mvWidget .weatherForecast .raindrop_background_1 {
      width: 10px;
  }
  .mvWidget .weatherForecast .snowflake_background_1 {
      width: 12px;
  }
  .mvWidget .weatherForecast .raindrop_background_2 {
      width: 17px;
  } 
  .mvWidget .weatherForecast .snowflake_background_2 {
      width: 18px;
  } 
  .mvWidget .weatherForecast .raindrop_background_3 {
      width: 22px;
  } 
  .mvWidget .weatherForecast .snowflake_background_3,
  .mvWidget .weatherForecast .snowflake_background_4 {
      width: 24px;
  } 
  .mvWidget .weatherForecast .raindrop_background_4 {
      width: 22px;
  }
  .mvWidget .weatherForecast svg.raindrop,
  .mvWidget .weatherForecast svg.snowflake,
  .mvWidget .weatherForecast svg.thunder {
      width: 29px;
      height: 29px;
  }

  
  
  
  .mvWidget .weatherDetailForecast .hour > div > .wind .compass, 
  .mvWidget .weatherDetailForecast .hour > div > .wind svg {
    width: 24px;
    height: 24px;
  }
  .hour {
    padding-top: 5px;
    margin-top: 5px;
    border-top: 1px solid #999;
  }
  .mvWidget .weatherForecast .summary .bullet {
    padding: 0 10px;
  }
  .mvWidget .weatherDetailForecast .today .hour, .weatherDetailForecast .today .summary {
    border-right: none;
  }
  .mvWidget .weatherDetailForecast .hour > div > .time {
    width: 12%;
  }
  .mvWidget .weatherDetailForecast .hour > div > .info {
    width: 20%;
  }
  .mvWidget .weatherDetailForecast .hour > div > .wind {
    width: 30%;
  }
  .mvWidget .weatherDetailForecast .hour > div > .info > div > div:nth-child(2) {
    padding-left: 5px;
  }
  .mvWidget .weatherDetailForecast .mvClickable, .weatherDetailForecast .today .hour > div {
    border: 1px solid transparent;
  }
  .mvWidget .weatherDetailForecast .today, .mvWidget .weatherDetailForecast .week{
    box-sizing: border-box;
  }
  .mvWidget .weatherDetailForecast .mvClickable:active {
    background-color: var(--widget-button-background-marker);
  }
  
  @media only screen and (min-width: 640px) {
    #openButton {
        display: none;
    }
  }
  @media only screen and (max-width: 640px) {
    .mvWidget .weatherDetailForecast .today, .mvWidget .weatherDetailForecast .week {
        width: 100%;
    }
    .mvWidget .weatherDetailForecast .today {
        padding-top: 20px;
    }
    .mvWidget .weatherDetailForecast .week {
        position: absolute;
        background-color: var(--body-bg);
        padding-left: 0;
        transform: translate3d(-100%, 0, 0);
        transition: transform 300ms ease;
        top: 20px;
        bottom: 0;
    }
    .mvWidget .weatherDetailForecast .week.open {
        transform: translate3d(0%, 0, 0);
    }
    #openButton {
        position: absolute;
        display: inline-block;
        top: 0;
        right: 0;
        background-color: var(--body-bg);
        color: var(--primary-color);
        z-index: 1;
        padding: 10px;
        border: 1px solid var(--primary-light-color);
    }
    #openButton.open {
        background-color: var(--primary-light-color);
        border: 1px solid var(--primary-color);  
    }
    #openButton, .mvClickable {
        cursor: pointer;
    }
  }
  
  body.dark {
    background-color: var(--body-bg);
    
    --body-bg: black;

    --primary-color: white !important;
    --primary-light-color: rgba(255,255,255,.50) !important;
    --primary-dark-color: rgba(255,255,255,1) !important;
    --primary-icon-color: var(--primary-dark-color) !important;
    --widget-text-color: white;
    --widget-value-color: white;
    --widget-value-color-weather-needle: white;
    --widget-value-color-weather-circle: white;
    --widget-value-color-weather-clouds: white;
    --widget-value-color-weather-info-icon: white;
    --sub-icon-color: white;
    --widget-text-color-nonimportant: white;
    --widget-value-color-weather-raindrop: white;
    --widget-button-background-marker: #333;
  }
  body.light {
    background-color: var(--body-bg);
    
    --body-bg: white;

    --primary-color: #333 !important;
    --primary-light-color: rgba(0,0,0,.50) !important;
    --primary-dark-color: rgba(0,0,0,1) !important;
    --primary-icon-color: var(--primary-dark-color) !important;
    --widget-text-color: #333;
    --widget-value-color: #333;
    --widget-value-color-weather-needle: #333;
    --widget-value-color-weather-circle: #333;
    --widget-value-color-weather-clouds: #333;
    --widget-value-color-weather-info-icon: #333;
    --sub-icon-color: #333;
    --widget-text-color-nonimportant: #333;
    --widget-value-color-weather-raindrop: #333;
    --widget-button-background-marker: #DDD;
  }
</style>
</head>
<body>
<script>
    var isPhone = ( navigator.userAgent.indexOf("Android") != -1 && navigator.userAgent.indexOf("Mobile") != -1 );
    var theme = isPhone || top.document.location.pathname.includes("habpanel") ? 'dark' : 'light';
    document.querySelector("body").classList.add(theme);
</script>
<div id="openButton">Woche</div>
<div class="mvWidget">
<?php
include "widgetWeatherDetailOverview.php"
?>
</div>
<script>
    var openButton = document.getElementById("openButton");
    openButton.addEventListener("click",function(){
    var weekList = document.querySelector(".mvWidget .weatherDetailForecast .week");
    if( weekList.classList.contains("open") )
    {
      weekList.classList.remove("open");
      openButton.classList.remove("open");
    }
    else
    {
      weekList.classList.add("open");
      openButton.classList.add("open");
    }
  });
  
  var elements = document.querySelectorAll('div[mv-url]');
  for( var i = 0; i < elements.length; i++)
  { 
    var element = elements[i];
    element.addEventListener("click",function()
    {
      var src = this.getAttribute("mv-url");
      var parameter = src.split("?")[1];
      document.location.href=document.location.pathname+"?"+parameter;
    });
  }
</script>
</body>
</html>
