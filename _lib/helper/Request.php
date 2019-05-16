<?php

class Request
{
    public static function makeRequest( $url, $headers, $postData, $expectedCode )
    {
        // DROP OLD DATA
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        
        if( $postData !== null )
        {
            curl_setopt( $ch, CURLOPT_POST, 1 );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $postData );
        }

        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

        $result = curl_exec( $ch );

        $returnCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

        if( curl_errno( $ch ) )
        {
            echo 'Error:' . curl_error( $ch );
        }
        else if( $returnCode != $expectedCode )
        {
            throw new RuntimeException( "Request CODE: " . $returnCode . ", RESULT: " . $result );
        }

        curl_close( $ch );

        return $result;
    }
}
