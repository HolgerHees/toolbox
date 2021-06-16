<?php
class CmdArgs
{
    public static function printUsage($arg_config)
    {
        $usage_msg = "\n";
        $usage_msg .= "Usage:\n";
        $usage_msg .= "\tdocker exec -it php sh -c \"php -d memory_limit=4096M -f " . __FILE__ . " --";
        foreach( $arg_config as $name => $value )
        {
            $usage_msg .= " ";
            if( !$value[0] ) $usage_msg .= "[";
            $usage_msg .= "--" . $name . "={";
            $usage_msg .= is_array($value[1]) ? implode("|",$value[1]) : $value[1];
            $usage_msg .= "}";
            if( !$value[0] ) $usage_msg .= "]";
        }
        $usage_msg .= "\"\n";
        return $usage_msg;
    }

    public static function processOptions($arg_config)
    {
        $shortopts  = "";
        $longopts  = array();
        foreach( $arg_config as $name => $value )
        {
            $longopts[] = $name . ( $value[0] ? ':' : '::' );
        }

        $options = getopt($shortopts, $longopts);

        $error_list = array();
        foreach( $arg_config as $name => $value )
        {
            if( $value[0] && empty($options[$name]) )
            {
                $msg = "--" . $name . "={";
                $msg .= is_array($value[1]) ? implode("|",$value[1]) : $value[1];
                $msg .= "} missing";
                $error_list[] = $msg;
            }
            
            if( is_array($value[1]) && !empty($options[$name]) && !in_array($options[$name],$value[1]) )
            {
                $msg = "--" . $name . "={";
                $msg .= is_array($value[1]) ? implode("|",$value[1]) : $value[1];
                $msg .= "} has unsupported value '" . $options[$name] . "'";
                $error_list[] = $msg;
            }
            
            if( isset($value[2]) )
            {
                $options[$name] = $value[2]( isset($options[$name]) ? $options[$name] : null );
            }
        }
        
        if( count($error_list) > 0 )
        {
            $error_msg = "Error:\n";
            $error_msg .= "\t" . implode("\t\n",$error_list);

            $error_msg .= CmdArgs::printUsage($arg_config);

            $error_msg .= "\n";
            
            return $error_msg;
        }

        echo "Are you sure you want to do continue with the following settings?\n";
        foreach( $options as $name => $value )
        {
            echo "\t" . $name . "\t=> " . var_export($value,true) . "\n";
        }
        echo "\nType 'yes' to continue: ";
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);
        if( $line === false )
        {
            $error_msg = "\n\nError:\n";
            $error_msg .= "\tphp was not started in interactive mode (no command line input stream available).\n";
            $error_msg .= CmdArgs::printUsage($arg_config);
            return $error_msg;
        }
        if(trim($line) != 'yes') return "\nABORTING!\n\n";
        fclose($handle);
        echo "\nThank you, continuing...\n\n";

        return $options;
    }
}
