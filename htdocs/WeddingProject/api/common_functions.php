<?php
    function connectToDatabase () {
        $mysqli = new mysqli("localhost","root","","wedding_database");

        if ($mysqli -> connect_errno) {
            echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
            exit();
        }

        $mysqli -> autocommit(false);

        return $mysqli;
    }

    function getWeddingList () {
        $new_connection = connectToDatabase(); 
        // Perform query
        if ($result = $new_connection -> query("SELECT * FROM wedding_couples")) {
            $weddingList = $result -> fetch_all(MYSQLI_ASSOC);
            // Free result set
            $result -> free_result();
        }
    
        $new_connection -> close();
        return $weddingList;
    }

    function getAttendeeList () {
        $new_connection = connectToDatabase(); 
        // Perform query
        if ($result = $new_connection -> query("SELECT * FROM attendees")) {
            $attendeeList = $result -> fetch_all(MYSQLI_ASSOC);
            // Free result set
            $result -> free_result();
        }
    
        $new_connection -> close();
        return $attendeeList;
    }

    function getAttendeesForWedding ($wedding_id) {
        $new_connection = connectToDatabase();

        //Get all attendee ids linked to the selected wedding 
        $result = $new_connection -> query("SELECT attendee_id FROM wedding_has_attendees WHERE wedding_id=$wedding_id");

        $attendeesList = $result -> fetch_all(MYSQLI_ASSOC);

        $result -> free_result();
        $new_connection -> close();

        return $attendeesList;
    }

    function getWeddingsForAttendee ($attendee_id) {
        $new_connection = connectToDatabase();

        //Get all wedding ids linked to the selected attendee 
        $result = $new_connection -> query("SELECT wedding_id FROM wedding_has_attendees WHERE attendee_id=$attendee_id");

        $weddingsList = $result -> fetch_all(MYSQLI_ASSOC);

        $result -> free_result();
        $new_connection -> close();

        return $weddingsList;
    }

    function getWeddingInfo ($wedding_id) {
        $new_connection = connectToDatabase(); 
        // Perform query
        if ($result = $new_connection -> query("SELECT * FROM wedding_couples WHERE wedding_id=$wedding_id")) {
            $wedding = $result -> fetch_all(MYSQLI_ASSOC);
            // Free result set
            $result -> free_result();
        }
    
        $new_connection -> close();
        return json_encode($wedding);
    }
?> 