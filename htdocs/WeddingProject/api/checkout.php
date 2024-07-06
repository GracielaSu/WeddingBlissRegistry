<?php
    require_once 'common_functions.php';

    class RegistryItem {
        public $registry_id;
        public $item_id;
        public $wedding_id;
        public $price;

        function __construct($registry_id, $item_id, $wedding_id, $price) {
            $this->registry_id = $registry_id;
            $this->item_id = $item_id;
            $this->wedding_id = $wedding_id;
            $this->price = $price;
        }
    }

    function validateRegistryItem($item_to_validate) {
        $new_connection = connectToDatabase();

        if ($result = $new_connection -> query("SELECT * FROM registry_items WHERE registry_item_id=$item_to_validate->registry_id")) {
            //Validate registry_id exists
            if ($result -> num_rows == 0) {
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                throw new Exception("Registry item was not found");
            }

            $registryDetails = $result -> fetch_assoc();

            //Validate the registry item is not already purchased
            if ($registryDetails["is_purchased"]) {
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                throw new Exception("Registry item is already purchased");
            }

            //Validate the item id matches the registry item
            if ($registryDetails["item_id"] != $item_to_validate->item_id) {
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                throw new Exception("Registry item does not match item id");
            }

            //Validate the wedding id matches the registry item
            if ($registryDetails["wedding_id"] != $item_to_validate->wedding_id) {
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                throw new Exception("Registry item does not match wedding id");
            }

            // Free result set
            $result -> free_result();
        }

        if ($result = $new_connection -> query("SELECT * FROM items WHERE item_id=$item_to_validate->item_id")) {
            //Validate item_id exists
            if ($result -> num_rows == 0) {
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                throw new Exception("Item was not found in database");
            }

            $itemDetails = $result -> fetch_assoc();

            //Validate the item is available for purchase
            if (!$itemDetails["is_available"]) {
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                throw new Exception("Item is not available");
            }

            //Validate the item has stock available
            if ($itemDetails["stock_level"] < 1) {
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                throw new Exception("Item is out of stock");
            }

            //Validate the item price matches the given price
            if ($registryDetails["wedding_id"] != $item_to_validate->wedding_id) {
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                throw new Exception("Item price does not match");
            }

            // Free result set
            $result -> free_result();
        }

        $new_connection -> close();
        return True;
    }

    function submitPayment($payment_details) {
        //TODO: Connect to payment vendor and validate card details, then submit payments
        return True;
    }

    function purchaseRegistryItem($attendee_id, $item_details, $payment_details) {
        $new_connection = connectToDatabase();

        $purchaser_comment = $item_details['purchaser_comment'];
        $registry_id = $item_details['registry_id'];
        $item_id = $item_details['item_id'];

        $item_details_json = json_encode($item_details);
        $payment_details_json = json_encode($item_details);

        $item_to_purchase = new RegistryItem($item_details["registry_id"], $item_details["item_id"], $item_details["wedding_id"], $item_details["price"]);

        try {
            if (validateRegistryItem($item_to_purchase)) {
                if (submitPayment($payment_details)) {
                    //After passing all validation checks, first update the registry_items table
                    $new_connection -> query ("UPDATE registry_items SET is_purchased=1, purchaser_id='$attendee_id', purchaser_comment='$purchaser_comment', purchase_date=NOW(), payment_info='$payment_details_json' WHERE registry_item_id=$registry_id");

                    //Commit the query
                    if (!$new_connection -> commit()) {
                        echo "Commit transaction failed";
                        exit();
                    }

                    //Then update the stock level for items
                    $new_connection -> query ("UPDATE items SET stock_level=stock_level-1 WHERE item_id=$item_id");

                    //Commit the query
                    if (!$new_connection -> commit()) {
                        echo "Commit transaction failed";
                        exit();
                    }

                    //Lastly, send an order to the attendee
                    /*$new_connection -> query ("SELECT JSON_ARRAY_APPEND(order_history, '$.order_item_details', CAST('$item_details_json' AS JSON), '$.order_payment_details', CAST('$payment_details_json' AS JSON), '$.purchase_date', NOW()) FROM attendees WHERE attendee_id=$attendee_id");

                    //Commit the query
                    if (!$new_connection -> commit()) {
                        echo "Commit transaction failed";
                        exit();
                    }*/
                }
            }
            return '{"response": "Item successfully purchased"}';
        }
        catch (Exception $e) {
            return '{"response": "'.$e->getMessage().'"}';
        }
    }
?>