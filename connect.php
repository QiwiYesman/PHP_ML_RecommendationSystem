<?php
function connectToDB(): mysqli
{
    #pass here value to connect or replace this code with you needed
    $db_name = "dbName";
    $password = "passwordDB";
    $user_name = "userName";
    return new mysqli('localhost', $user_name, $password, $db_name);
}

