<?php

class GenericAuth
{
    private $user;
    private $pass;

    public function __construct( $user, $pass )
    {
        $this->user = $user;
        $this->pass = $pass;
    }
    
    public function getUsername()
    {
        return $this->user;
    }

    public function getPassword()
    {
        return $this->pass;
    }
}
