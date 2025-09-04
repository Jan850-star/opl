<?php
    $hostname = "127.0.0.1";
    $username = "mariadb"; // getenv("db_user")
    $password = "mariadb"; // getenv("db_pass")
    $database = "mariadb"; // getenv("db_name")


    $connection = mysqli_connect($hostname, $username, $password, $database);

    if(!$connection) {
        echo "Cannot establish connection to the database. " . mysqli_connect_error();
    }
?>