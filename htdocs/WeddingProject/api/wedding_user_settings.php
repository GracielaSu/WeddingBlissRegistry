<?php 
    require_once 'common_functions.php';

    function updateWeddingEmail($wedding_id, $new_email) {
        $new_connection = connectToDatabase(); 

        //Check that email is not already in use by a wedding account
        if ($wedding_result = $new_connection -> query("SELECT linked_attendee FROM wedding_couples WHERE wedding_email=$new_email")) {
            if ($wedding_result -> num_rows != 0) {
                return "Email is already in use";
            }

            //Check if there is a linked attendee account
            $linked_attendee = $wedding_result -> fetch_column();
            
            //If there is not a linked account, check that the email is not already in use by an attendee account
            if ($linked_attendee == NULL) {
                if ($attendee_result = $new_connection -> query("SELECT * FROM attendees WHERE attendee_email=$new_email")) {
                    if ($attendee_result -> num_rows != 0) {
                        return "Email is already in use";
                    }
                }
            }

            //Update the wedding email to the new email after passing all checks
            $new_connection -> query ("UPDATE wedding_couples SET wedding_email=$new_email WHERE wedding_id=$wedding_id");

            //Commit the query
            if (!$new_connection -> commit()) {
                echo "Commit transaction failed";
                exit();
            }

            //If there is a linked attendee account we also need to update that email as well
            if ($linked_attendee != NULL) {
                $new_connection -> query ("UPDATE attendees SET attendee_email=$new_email WHERE attendee_id=$linked_attendee");

                //Commit the query
                if (!$new_connection -> commit()) {
                    echo "Commit transaction failed";
                    exit();
                }
            }
        }

        $new_connection -> close();
    };

    //Update any parameter that also has a matching element in the attendee table
    function updateWeddingParameter($wedding_id, $parameter, $new_value) {
        $new_connection = connectToDatabase();

        $new_connection -> query ("UPDATE wedding_couples SET wedding_$parameter=$new_value WHERE wedding_id=$wedding_id");

        //Commit the query
        if (!$new_connection -> commit()) {
            echo "Commit transaction failed";
            exit();
        }

        if ($wedding_result = $new_connection -> query("SELECT linked_attendee FROM wedding_couples WHERE wedding_id=$wedding_id")) {
            //Check if there is a linked attendee account
            $linked_attendee = $wedding_result -> fetch_column();

            //If there is a linked attendee account we also need to update that parameter as well
            if ($linked_attendee != NULL) {
                $new_connection -> query ("UPDATE attendees SET attendee_$parameter=$new_value WHERE attendee_id=$linked_attendee");

                //Commit the query
                if (!$new_connection -> commit()) {
                    echo "Commit transaction failed";
                    exit();
                }
            }
        }

        $new_connection -> close();
    };

    function updateWeddingAddress($wedding_id, $new_delivery_address) {
        $new_connection = connectToDatabase();

        $new_connection -> query ("UPDATE wedding_couples SET delivery_address=$new_delivery_address WHERE wedding_id=$wedding_id");

        //Commit the query
        if (!$new_connection -> commit()) {
            echo "Commit transaction failed";
            exit();
        }

        $attendees_to_notify = getAttendeesForWedding($wedding_id);

        //Get details about the current wedding with the new delivery address
        $result = $new_connection -> query("SELECT wedding_name, delivery_address FROM wedding_couples WHERE wedding_id=$wedding_id");
        $result_wedding_name = $result -> fetch_column();
        $result_wedding_location = $result -> fetch_column();

        //Notify all current attendees
        foreach ($attendees_to_notify AS $row) {
            $new_connection -> query ("INSERT INTO notifications_for_attendees (attendee_id, notification_text) 
            VALUES ($row[attendee_id], 'The wedding for $result_wedding_name has updated the venue location to $result_wedding_location')");

            if (!$new_connection -> commit()) {
                echo "Commit transaction failed";
                exit();
            }
        }

        $new_connection -> close();
    };

    function updateWeddingDate($wedding_id, $new_wedding_date) {
        $new_connection = connectToDatabase();

        $new_connection -> query ("UPDATE wedding_couples SET wedding_date=$new_wedding_date WHERE wedding_id=$wedding_id");

        //Commit the query
        if (!$new_connection -> commit()) {
            echo "Commit transaction failed";
            exit();
        }

        $attendees_to_notify = getAttendeesForWedding($wedding_id);

        //Get details about the current wedding with the new wedding date
        $result = $new_connection -> query("SELECT wedding_name, wedding_date FROM wedding_couples WHERE wedding_id=$wedding_id");
        $result_wedding_name = $result -> fetch_column();
        $result_wedding_date = $result -> fetch_column();

        //Notify all current attendees
        foreach ($attendees_to_notify AS $row) {
            $new_connection -> query ("INSERT INTO notifications_for_attendees (attendee_id, notification_text) 
            VALUES ($row[attendee_id], 'The wedding for $result_wedding_name has updated the wedding date to $result_wedding_date')");

            if (!$new_connection -> commit()) {
                echo "Commit transaction failed for attendee id $row[attendee_id]";
            }
        }

        $new_connection -> close();
    };

    function deleteWeddingAccount($wedding_id) {
        $new_connection = connectToDatabase();

        $new_connection -> query ("UPDATE wedding_couples SET is_disabled=1 WHERE wedding_id=$wedding_id");

        //Get details about the current wedding with the wedding date to check if it has already passed
        $result = $new_connection -> query("SELECT wedding_name, wedding_date FROM wedding_couples WHERE wedding_id=$wedding_id");
        $result_wedding_name = $result -> fetch_column();
        $result_wedding_date = $result -> fetch_column();

        $current_time = $new_connection -> query("SELECT CURRENT_TIME();");

        //Notify all current attendees if the wedding has not already happened
        if ($result_wedding_date < $current_time) {
            $attendees_to_notify = getAttendeesForWedding($wedding_id);

            foreach ($attendees_to_notify AS $row) {
                $new_connection -> query ("INSERT INTO notifications_for_attendees (attendee_id, notification_text) 
                VALUES ($row[attendee_id], 'The wedding for $result_wedding_name has closed the account and you have been unregistered')");

                if (!$new_connection -> commit()) {
                    echo "Commit transaction failed for attendee id $row[attendee_id]";
                }
            }
        }

        if ($wedding_result = $new_connection -> query("SELECT linked_attendee FROM wedding_couples WHERE wedding_id=$wedding_id")) {
            //Check if there is a linked attendee account
            $linked_attendee = $wedding_result -> fetch_column();

            //If there is a linked attendee account we also need to mark that account as disabled
            if ($linked_attendee != NULL) {
                $new_connection -> query ("UPDATE attendees SET is_disabled=1 WHERE attendee_id=$linked_attendee");

                //Commit the query
                if (!$new_connection -> commit()) {
                    echo "Commit transaction failed";
                    exit();
                }

                //Get details about the current attendee
                $result = $new_connection -> query("SELECT attendee_name FROM attendees WHERE attendee_id=$linked_attendee");
                $result_attendee_name = $result -> fetch_column();

                //Notify all current weddings the attendee has reigstered for
                $weddings_to_notify = getWeddingsforAttendee($linked_attendee);

                foreach ($weddings_to_notify AS $row) {
                    $new_connection -> query ("INSERT INTO notifications_for_wedding_couples (wedding_id, notification_text) 
                    VALUES ($row[wedding_id], 'The attendee $result_attendee_name has closed their account and is no longer registered to your wedding')");

                    if (!$new_connection -> commit()) {
                        echo "Commit transaction failed for wedding id $row[wedding_id]";
                    }
                }
            }
        }

        $new_connection -> close();
    }
?>