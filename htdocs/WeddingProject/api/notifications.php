<?php
    require_once 'common_functions.php';

    function getUserNotifications($user_id, $wedding_or_attendee) {
        $new_connection = connectToDatabase();

        if ($wedding_or_attendee == "wedding") {
            $table = "notifications_for_wedding_couples";
            $id_type = "wedding_id";
        }
        else if ($wedding_or_attendee == "attendee") {
            $table = "notifications_for_attendees";
            $id_type = "attendee_id";
        }
        else {
            $new_connection -> close();
            return "Invalid ID type";
        }

        //Pull all notifications that match the given user_id, sorted by unread first and newer first
        if ($result = $new_connection -> query("SELECT * FROM $table WHERE $id_type=$user_id ORDER BY (notification_read = 1) ASC, notification_timestamp DESC")) {
            $notificationList = $result -> fetch_all(MYSQLI_ASSOC);
            // Free result set
            $result -> free_result();
        }
    
        $new_connection -> close();
        return $notificationList;
    }

    function markNotificationAsRead($notification_id, $user_id, $wedding_or_attendee) {
        $new_connection = connectToDatabase();

        if ($wedding_or_attendee == "wedding") {
            $table = "notifications_for_wedding_couples";
            $id_type = "wedding_id";
        }
        else if ($wedding_or_attendee == "attendee") {
            $table = "notifications_for_attendees";
            $id_type = "attendee_id";
        }
        else {
            $new_connection -> close();
            return "Invalid ID type";
        }

        //Update the notification as read if it also matches the id
        $result = $new_connection -> query("UPDATE $table SET notification_read=1 WHERE notification_id=$notification_id AND $id_type=$user_id");
        
        //Check if a notification was actually returned to update
        if ($result -> num_rows == 0) {
            //Notify the user if the notification was not found or is not associated with their user ID
            return "Notification was not found";
        }

        //Commit the query
        if (!$new_connection -> commit()) {
            echo "Commit transaction failed";
            exit();
        }
    
        $new_connection -> close();
        return "Notification marked as read";
    }
?>