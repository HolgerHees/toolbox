<?php
include dirname(__FILE__) . "/../_lib/init.php";

// Error Reporting und Zeitlimit für Serverbetrieb setzen
error_reporting(E_ERROR);
set_time_limit (0);

$host = '192.168.0.50'; // Serverhost auf der gelauscht werden soll
$port = 1414; // Port auf dem Verbindungen angenommen werden sollen

// Socket erstellen
$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

// Socket an Adresse und Port binden
socket_bind($sock, $host, $port);

// An Port lauschen
socket_listen($sock);

$sockets = array($sock);
$arClients = array();

while (true)
{

    echo "Warte auf Verbindung...";

    $sockets_change = $sockets;
    $ready = socket_select($sockets_change, $write = null, $expect = null, null);

    echo "Verbindung angenommen.";

    foreach($sockets_change as $s)
    {
        if ($s == $sock)
        {
            // Änderung am Serversocket
            $client = socket_accept($sock);
            array_push($sockets, $client);
            error_log("serversocket");
        }
        else
        {
            // Eingehende Nachrichten der Clientsockets
            $bytes = @socket_recv($s, $buffer, 2048, 0);
            error_log("else");
        }
    }
}
