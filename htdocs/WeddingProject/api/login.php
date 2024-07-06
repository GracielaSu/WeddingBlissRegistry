<?php
    require_once 'common_functions.php';

    function checkLogin($email_to_check, $password_to_check) {
        $new_connection = connectToDatabase();

        //Check for login match in wedding couples
        if ($result = $new_connection -> query ("SELECT wedding_id, linked_attendee FROM wedding_couples WHERE wedding_email='$email_to_check' AND wedding_password='$password_to_check'")) {
            if ($result -> num_rows != 0) {
                $wedding_id = $result -> fetch_column();
                $result_linked_attendee = $result -> fetch_column();
                
                //Create a new session token if the login is successful
                $result_session_token = $new_connection -> query ("SELECT UUID();");
                $session_token =  $result_session_token -> fetch_column();

                $new_connection -> query ("UPDATE wedding_couples SET session_token='$session_token' WHERE wedding_id=$wedding_id");
                
                //Commit the query
                if (!$new_connection -> commit()) {
                    header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Server Error", true, 500);
                    echo '{"response": "Commit transaction failed"}';
                    exit();
                }

                //If there is a linked attendee update their session token as well
                if ($result_linked_attendee != NULL) {
                    $new_connection -> query ("UPDATE attendees SET session_token='$session_token' WHERE attendee_id=$result_linked_attendee");
                
                    //Commit the query
                    if (!$new_connection -> commit()) {
                        header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Server Error", true, 500);
                        echo '{"response": "Commit transaction failed"}';
                        exit();
                    }
                }

                $new_connection -> close();

                //Return session token, userID, and userType to be added to client cache
                return '{"sessionToken": '.json_encode($session_token).', "userID": '.$wedding_id.', "userType": "wedding"}';
            }
            //Search through the attendee table as well
            else {
                if ($result = $new_connection -> query ("SELECT attendee_id FROM attendees WHERE attendee_email='$email_to_check' AND attendee_password='$password_to_check'")) {
                    if ($result -> num_rows != 0) {
                        $attendee_id = $result -> fetch_column();

                        //Create a new session token if the login is successful
                        $result_session_token = $new_connection -> query ("SELECT UUID();");
                        $session_token =  $result_session_token -> fetch_column();
        
                        $new_connection -> query ("UPDATE attendees SET session_token='$session_token' WHERE attendee_id=$attendee_id");
                        
                        //Commit the query
                        if (!$new_connection -> commit()) {
                            header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Server Error", true, 500);
                            echo '{"response": "Commit transaction failed"}';
                            exit();
                        }

                        $new_connection -> close();

                        //Return session token, userID, and userType to be added to client cache
                        return '{"sessionToken": '.json_encode($session_token).', "userID": '.$attendee_id.', "userType": "attendee"}';
                    }
                    else {
                        $new_connection -> close();
                        //Let user know login failed
                        header($_SERVER["SERVER_PROTOCOL"]." 403 Unauthenticated", true, 403);
                        return '{"response": "Login failed"}';
                    }
                }
            }
        }
    }

    function logoutAccount($wedding_id, $attendee_id) {
        $new_connection = connectToDatabase();

        if ($wedding_id != NULL) {
            //Delete session token
            $new_connection -> query ("UPDATE wedding_couples SET session_token=NULL WHERE wedding_id=$wedding_id");
                        
            //Commit the query
            if (!$new_connection -> commit()) {
                header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Server Error", true, 500);
                echo '{"response": "Commit transaction failed"}';
                exit();
            }
        }

        if ($attendee_id != NULL) {
            //Delete session token
            $new_connection -> query ("UPDATE attendees SET session_token=NULL WHERE attendee_id=$attendee_id");
                        
            //Commit the query
            if (!$new_connection -> commit()) {
                header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Server Error", true, 500);
                echo '{"response": "Commit transaction failed"}';
                exit();
            }
        }

        $new_connection -> close();

        //Return message to let the client know to delete their session token as well
        return '{"response": "Session deleted"}';
    }

    function createAccount($email, $password, $accountInfo) {
        $new_connection = connectToDatabase();
        $new_connection -> autocommit(TRUE);

        if ($accountInfo['accountType'] == 'wedding') {
            $weddingName = $accountInfo['weddingName'];
            $weddingDate = $accountInfo['weddingDate'];
            $weddingAddress = $accountInfo['weddingAddress'];

            $new_connection -> query ("INSERT INTO wedding_couples (wedding_email, wedding_password, wedding_name, wedding_date, delivery_address, wedding_profile_pic)
            VALUES ('$email', '$password', '$weddingName', '$weddingDate', '$weddingAddress', 'img')");

            $wedding_id = $new_connection -> insert_id;

            $result_session_token = $new_connection -> query ("SELECT UUID();");
            $session_token =  $result_session_token -> fetch_column();

            $new_connection -> query ("UPDATE wedding_couples SET session_token='$session_token' WHERE wedding_id=$wedding_id");
            
            $new_connection -> close();

            //Return session token, userID, and userType to be added to client cache
            return '{"sessionToken": '.json_encode($session_token).', "userID": '.$wedding_id.', "userType": "wedding"}';
        }
        else {
            $attendeeName = $accountInfo['attendeeName'];
            $new_connection -> query ("INSERT INTO attendees (attendee_email, attendee_password, attendee_name, attendee_profile_pic)
            VALUES ('$email', '$password', '$attendeeName', 'img')");

            $attendee_id = $new_connection -> insert_id;

            $result_session_token = $new_connection -> query ("SELECT UUID();");
            $session_token =  $result_session_token -> fetch_column();

            $new_connection -> query ("UPDATE attendees SET session_token='$session_token' WHERE attendee_id=$attendee_id");

            $new_connection -> close();

            //Return session token, userID, and userType to be added to client cache
            return '{"sessionToken": '.json_encode($session_token).', "userID": '.$attendee_id.', "userType": "attendee"}';
        }

        $new_connection -> close();

        //Return message to let the client know to delete their session token as well
        return "Session deleted";
    }
?>