<?php
function connectToDB(): mysqli
{
    $db_name = "practice";
    $password = "Qiwiman123";
    $user_name = "root";
    return new mysqli('localhost', $user_name, $password, $db_name);
}

