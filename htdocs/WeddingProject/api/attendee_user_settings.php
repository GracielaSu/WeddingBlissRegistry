<?php 
    require_once 'common_functions.php';

    function updateAttendeeEmail ($attendee_id, $new_email) {
        $new_connection = connectToDatabase(); 

        //Check that email is not already in use by an attendee account
        if ($attendee_result = $new_connection -> query("SELECT linked_wedding FROM attendees WHERE attendee_email=$new_email")) {
            if ($attendee_result -> num_rows != 0) {
                return "Email is already in use";
            }

            //Check if there is a linked wedding account
            $linked_wedding = $attendee_result -> fetch_column();
            
            //If there is not a linked account, check that the email is not already in use by a wedding account
            if ($linked_wedding == NULL) {
                if ($wedding_result = $new_connection -> query("SELECT * FROM wedding_couples WHERE wedding_email=$new_email")) {
                    if ($wedding_result -> num_rows != 0) {
                        return "Email is already in use";
                    }
                }
            }

            //Update the attendee email to the new email after passing all checks
            $new_connection -> query ("UPDATE attendees SET attendee_email=$new_email WHERE attendee_id=$attendee_id");

            //Commit the query
            if (!$new_connection -> commit()) {
                echo "Commit transaction failed";
                exit();
            }

            //If there is a linked wedding account we also need to update that email as well
            if ($linked_wedding != NULL) {
                $new_connection -> query ("UPDATE wedding_couples SET wedding_email=$new_email WHERE wedding_id=$linked_wedding");

                //Commit the query
                if (!$new_connection -> commit()) {
                    echo "Commit transaction failed";
                    exit();
                }
            }
        }

        $new_connection -> close();
    };

    //Update any parameter that also has a matching element in the wedding table
    function updateAttendeeParameter($attendee_id, $parameter, $new_value) {
        $new_connection = connectToDatabase();

        $new_connection -> query ("UPDATE attendees SET attendee_$parameter=$new_value WHERE attendee_id=$attendee_id");

        //Commit the query
        if (!$new_connection -> commit()) {
            echo "Commit transaction failed";
            exit();
        }

        if ($attendee_result = $new_connection -> query("SELECT linked_attendee FROM attendees WHERE attendee_id=$attendee_id")) {
            //Check if there is a linked wedding account
            $linked_wedding = $attendee_result -> fetch_column();

            //If there is a linked wedding account we also need to update that parameter as well
            if ($linked_wedding != NULL) {
                $new_connection -> query ("UPDATE wedding_couples SET wedding_$parameter=$new_value WHERE wedding_id=$linked_attendee");

                //Commit the query
                if (!$new_connection -> commit()) {
                    echo "Commit transaction failed";
                    exit();
                }
            }
        }

        $new_connection -> close();
    };

    function addWeddingToAttend($attendee_id, $wedding_id) {
        $new_connection = connectToDatabase();

        //Check for duplicate entry in wedding_has_attendees 
        if ($result = $new_connection -> query ("SELECT * FROM wedding_has_attendees WHERE attendee_id=$attendee_id AND wedding_id=$wedding_id")) {
            if ($result -> num_rows != 0) {
                return "Attendee is already registered to wedding";
            }
        }

        //Create a new entry in wedding_has_attendees 
        $new_connection -> query ("INSERT INTO wedding_has_attendees (wedding_id, attendee_id) 
        VALUES ($wedding_id, $attendee_id)");

        //Commit the query
        if (!$new_connection -> commit()) {
            echo "Commit transaction failed";
            exit();
        }

        //Get name of the current attendee
        $result = $new_connection -> query("SELECT attendee_name FROM attendees WHERE attendee_id=$attendee_id");
        $result_attendee_name = $result -> fetch_column();

        //Notify the wedding couple of the registration
        $new_connection -> query ("INSERT INTO notifications_for_wedding_couples (wedding_id, notification_text) 
        VALUES ($wedding_id, '$result_attendee_name has registered for your wedding!')");

        if (!$new_connection -> commit()) {
            echo "Commit transaction failed";
            exit();
        }

        $new_connection -> close();
    };

    function removeAttendeeFromWedding($attendee_id, $wedding_id) {
        $new_connection = connectToDatabase();

        //Check for entry in wedding_has_attendees 
        if ($result = $new_connection -> query ("SELECT * FROM wedding_has_attendees WHERE attendee_id=$attendee_id AND wedding_id=$wedding_id")) {
            if ($result -> num_rows == 0) {
                return "Attendee is not registered to wedding";
            }
        }

        //Remove entry from wedding_has_attendees 
        $new_connection -> query ("DELETE FROM wedding_has_attendees (wedding_id, attendee_id) 
        VALUES ($wedding_id, $attendee_id)");

        //Commit the query
        if (!$new_connection -> commit()) {
            echo "Commit transaction failed";
            exit();
        }

        //Get name of the current attendee
        $result = $new_connection -> query("SELECT attendee_name FROM attendees WHERE attendee_id=$attendee_id");
        $result_attendee_name = $result -> fetch_column();

        //Notify the wedding couple of the unregistration
        $new_connection -> query ("INSERT INTO notifications_for_wedding_couples (wedding_id, notification_text) 
        VALUES ($wedding_id, '$result_attendee_name has unregistered from your wedding')");

        if (!$new_connection -> commit()) {
            echo "Commit transaction failed";
            exit();
        }

        $new_connection -> close();
    };

    function editGiftMessage($attendee_id, $registry_item_id, $new_message) {
        $new_connection = connectToDatabase();

        //Check for entry in registry_items 
        if ($result = $new_connection -> query ("SELECT * FROM registry_items WHERE purchaser_id=$attendee_id AND registry_item_id=$registry_item_id")) {
            if ($result -> num_rows == 0) {
                return "Attendee did not purchase the specified item";
            }
        }

        //Edit gift message for the item
        $new_connection -> query ("UPDATE registry_items SET purchaser_comment=$new_message WHERE registry_item_id=$registry_item_id");

        //Commit the query
        if (!$new_connection -> commit()) {
            echo "Commit transaction failed";
            exit();
        }

        //Get name of the current attendee
        $result = $new_connection -> query("SELECT attendee_name FROM attendees WHERE attendee_id=$attendee_id");
        $result_attendee_name = $result -> fetch_column();

        //Get the wedding couple ID for the current item
        $result = $new_connection -> query("SELECT wedding_id FROM registry_items WHERE registry_item_id=$registry_item_id");
        $result_wedding_id = $result -> fetch_column();

        //Notify the wedding couple of the new message
        $new_connection -> query ("INSERT INTO notifications_for_wedding_couples (wedding_id, notification_text) 
        VALUES ($result_wedding_id, '$result_attendee_name has updated their gift message!')");

        if (!$new_connection -> commit()) {
            echo "Commit transaction failed";
            exit();
        }

        $new_connection -> close();
    };

    $getOrderDetails = function ($attendee_id) {
        $new_connection = connectToDatabase();

        
        if ($result = $new_connection -> query("SELECT * FROM registry_items WHERE purchaser_id=$attendee_id")) {
            if ($result -> num_rows != 0) {
                return [];
            }

            $result_order_history = $result -> fetch_all(MYSQLI_ASSOC);
            // Free result set
            $result -> free_result();
        }

        $new_connection -> close();
        return $result_order_history;
    };

    function deleteAttendeeAccount($attendee_id) {
        $new_connection = connectToDatabase();

        $new_connection -> query ("UPDATE attendees SET is_disabled=1 WHERE attendee_id=$attendee_id");

        //Commit the query
        if (!$new_connection -> commit()) {
            echo "Commit transaction failed";
            exit();
        }

        //Get details about the current attendee
        $result = $new_connection -> query("SELECT attendee_name FROM attendees WHERE attendee_id=$attendee_id");
        $result_attendee_name = $result -> fetch_column();

        //Notify all current weddings the attendee has reigstered for
        $weddings_to_notify = getWeddingsforAttendee($attendee_id);

        foreach ($weddings_to_notify AS $row) {
            $new_connection -> query ("INSERT INTO notifications_for_wedding_couples (wedding_id, notification_text) 
            VALUES ($row[wedding_id], 'The attendee $result_attendee_name has closed their account and is no longer registered to your wedding')");

            if (!$new_connection -> commit()) {
                echo "Commit transaction failed for wedding id $row[wedding_id]";
            }
        }

        if ($attendee_result = $new_connection -> query("SELECT linked_wedding FROM attendees WHERE attendee_id=$attendee_id")) {
            //Check if there is a linked wedding account
            $linked_wedding = $attendee_result -> fetch_column();

            //If there is a linked wedding account we also need to mark that account as disabled
            if ($linked_wedding != NULL) {
                $new_connection -> query ("UPDATE wedding_couples SET is_disabled=1 WHERE wedding_id=$linked_wedding");

                //Commit the query
                if (!$new_connection -> commit()) {
                    echo "Commit transaction failed";
                    exit();
                }

                //Get details about the current wedding with the wedding date to check if it has already passed
                $result = $new_connection -> query("SELECT wedding_name, wedding_date FROM wedding_couples WHERE wedding_id=$linked_wedding");
                $result_wedding_name = $result -> fetch_column();
                $result_wedding_date = $result -> fetch_column();

                $current_time = $new_connection -> query("SELECT CURRENT_TIME();");

                //Notify all current attendees if the wedding has not already happened
                if ($result_wedding_date < $current_time) {
                    $attendees_to_notify = getAttendeesForWedding($linked_wedding);

                    foreach ($attendees_to_notify AS $row) {
                        $new_connection -> query ("INSERT INTO notifications_for_attendees (attendee_id, notification_text) 
                        VALUES ($row[attendee_id], 'The wedding for $result_wedding_name has closed the account and you have been unregistered')");

                        if (!$new_connection -> commit()) {
                            echo "Commit transaction failed for attendee id $row[attendee_id]";
                        }
                    }
                }
            }
        }

        $new_connection -> close();
    }

    /*$attendee_endpoints = array(
        array("endpoint"=>"updateAttendeeEmail","method"=>"POST","function"=>$updateAttendeeEmail,"variables"=>array($_POST["userID"], $_POST["newEmail"])),
        array("endpoint"=>"getOrders","method"=>"GET","function"=>$getOrderDetails,"variables"=>array($_COOKIE["userID"])),
    );*/
?>