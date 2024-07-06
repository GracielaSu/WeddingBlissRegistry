<?php
    require_once 'common_functions.php';

    function addItem ($itemValues) {
        $new_connection = connectToDatabase();
        $new_connection -> query ("INSERT INTO items (item_name, item_description, item_category, stock_level, price)
        VALUES ($itemValues[0], $itemValues[1], $itemValues[2], $itemValues[3], $itemValues[4])");
        
    };

?>