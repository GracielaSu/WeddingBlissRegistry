<?php
    require_once 'common_functions.php';

    function addItemToRegistry($item_id, $wedding_id) {
        $new_connection = connectToDatabase();

        //Check for duplicate items in registry
        if ($result = $new_connection -> query ("SELECT * FROM registry_items WHERE item_id=$item_id AND wedding_id=$wedding_id")) {
            if ($result -> num_rows != 0) {
                $new_connection -> close();
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                return '{"response": "Item already added to registry"}';
            }
        }

        //Create a query to insert into the registry table
        $new_connection -> query ("INSERT INTO registry_items (item_id, wedding_id) 
        VALUES ($item_id, $wedding_id)");

        //Commit the query
        if (!$new_connection -> commit()) {
            $new_connection -> close();
            header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Server Error", true, 500);
            return '{"response": "Commit transaction failed"}';
        }

        $new_connection -> close();
        return '{"response": "Item successfully added"}';
    };

    function removeItemFromRegistry($item_id, $wedding_id) {
        $new_connection = connectToDatabase();

        if ($result = $new_connection -> query ("SELECT is_purchased FROM registry_items WHERE item_id=$item_id AND wedding_id=$wedding_id")) {
            //Check that item is in registry
            if ($result -> num_rows == 0) {
                $new_connection -> close();
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                return '{"response": "Item is not in registry"}';
            }

            //Check that item has not been purchased
            $is_purchased = $result -> fetch_column();
            if ($is_purchased) {
                $new_connection -> close();
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                return '{"response": "Item has already been purchased"}';
            }
        }

        //Create a query to remove the row from the registry table
        $new_connection -> query ("DELETE FROM registry_items WHERE item_id=$item_id AND wedding_id=$wedding_id");

        //Commit the query
        if (!$new_connection -> commit()) {
            $new_connection -> close();
            header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Server Error", true, 500);
            return '{"response": "Commit transaction failed"}';
        }

        $new_connection -> close();
        return '{"response": "Item successfully removed"}';
    };

    function checkItemInRegistry($item_id, $wedding_id) {
        $new_connection = connectToDatabase();

        if ($result = $new_connection -> query ("SELECT * FROM registry_items WHERE item_id=$item_id AND wedding_id=$wedding_id")) {
            if ($result -> num_rows != 0) {
                $new_connection -> close();
                return '{"response": true}';
            }
            else {
                $new_connection -> close();
                return '{"response": false}';
            }
        }
    };

    function checkIfItemPurchased($registry_id) {
        $new_connection = connectToDatabase();

        if ($result = $new_connection -> query ("SELECT is_purchased FROM registry_items WHERE registry_item_id=$registry_id")) {
            $is_purchased = $result -> fetch_column();
            if ($is_purchased) {
                $new_connection -> close();
                return '{"response": true}';
            }
            else {
                $new_connection -> close();
                return '{"response": false}';
            }
        }
    };
?>