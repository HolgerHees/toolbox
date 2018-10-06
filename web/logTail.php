<!doctype html>
<html class="no-js" lang="">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>LogTail</title>
    <meta name="description" content="Log Viewer">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            display: flex;
            flex-direction: column;
            height: 100vh;
            margin: 0;
        }
        #toolbar {
            display: flex;
            flex-direction: row;
            margin: 5px;
        }
        .toolbar-item-fill {
            flex-grow: 1;
        }
        .toolbar-item {
            padding-right: 5px;
        }
        .toolbar-item:last-child {
            padding-right: 0;
        }
        #search {
            width: 100%;
            box-sizing: border-box;
        }
        .scrollable {
            flex-grow: 1;
        }
    </style>
    <script type="text/javascript">
        var ws_host = "smartmarvin.de";
        var ws_port = "443";
        var ws_path = "/web/logtail/";
        var ws_url = "wss://" + ws_host + ":" + ws_port + ws_path ;

        try
        {
            socket = new WebSocket(ws_url);

            // Handlerfunktionen definieren
            socket.onopen = function()
            {
                alert("Sie sind erfolgreich verbunden");

                // Willkommensnachricht an den Server senden
                socket.send("Ich bin drin !");
            };

            socket.onmessage = function(msg)
            {
                alert("Neue Nachricht: " + msg.data);
            };

            socket.onclose = function(msg)
            {
                alert("Verbindung wurde getrennt");
            };
        }
        catch(ex)
        {
            alert("Exception: " + ex);
        }
    </script>
</head>
<body>
<div id='toolbar'>
    <div class='toolbar-item' id='file-select'>
        <select name="file" tabindex='1'>
            <option value="/dataDisk/logs/openhab/openhab.log">/dataDisk/logs/openhab/openhab.log</option>
            <option value="/dataDisk/logs/openhab/events.log">/dataDisk/logs/openhab/events.log</option>
        </select>
    </div>
    <div class='toolbar-item' id='command-select'>
        <select name="command" tabindex='2'>
            <option value="tail">tail</option>
            <option value="cat">cat</option>
        </select>
    </div>
    <div class='toolbar-item toolbar-item-fill'>
        <div id='script-input' tabindex='3'>
            <input id="search" name='search' placeholder='searchstring'>
            <div><i class='icon-bookmark'></i></div>
            <div><i class='icon-code'></i></div>
        </div>
    </div>
</div>
<div class='scrollable'>
    <div id='logviewer' class='log-view'></div>
</div>
</body>
</html>
