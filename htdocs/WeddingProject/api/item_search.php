<?php
    require_once 'common_functions.php';

    function itemSearchAll ($searchString = NULL, $filterByCategories = NULL, $sortBy = "review_scores", $page = NULL) {
        $new_connection = connectToDatabase(); 

        //These clauses will append all filter conditions that the user chooses
        $whereClause = "";
        $orderByClause = "";
        $sortOrderClause = " DESC";
        $paginationClause = " LIMIT 20 OFFSET 0";

        if ($searchString != NULL) {
            $whereClause .= " AND (LOWER(item_name) LIKE LOWER('%".$searchString."%') OR LOWER(item_category) LIKE LOWER('%".$searchString."%'))";
        }

        if ($filterByCategories != NULL) {
            $whereClause .= " AND (";
            foreach ($filterByCategories as $category) {
                $whereClause .= "LOWER(item_category) LIKE LOWER('%".$category."%') OR ";
            }
            $whereClause .= "0)";
        }

        if ($sortBy == "price_asc") {
            $sortBy = "price";
            $sortOrderClause = " ASC";
        }

        if ($page) {
            $paginationClause = " LIMIT 20 OFFSET ".(($page-1)*20);
        }

        //Get number of pages without limiting
        if ($result = $new_connection -> query("SELECT * FROM items WHERE is_available=1".$whereClause." ORDER BY (stock_level = 0) ASC, ".$sortBy.$sortOrderClause)) {
            $numPages = intdiv((($result -> num_rows) - 1), 20) + 1;
            // Free result set
            $result -> free_result();
        }

        // Perform query, do not show unavailable items and sort out-of-stock items to the end
        if ($result = $new_connection -> query("SELECT * FROM items WHERE is_available=1".$whereClause." ORDER BY (stock_level = 0) ASC, ".$sortBy.$sortOrderClause.$paginationClause)) {
            $itemsList = $result -> fetch_all(MYSQLI_ASSOC);
            // Free result set
            $result -> free_result();
        }
    
        $new_connection -> close();
        return '{"numPages": '.$numPages.',"itemsList": '.json_encode($itemsList).'}';
    }

    function itemSearchFilterByWedding ($searchString = NULL, $filterByCategories = NULL, $sortBy = "review_scores", $page = NULL, $weddingToFilter = NULL) {
        $new_connection = connectToDatabase();

        //Create a temporary table that contains all registry items linked to a wedding registered by the attendee
        $new_connection -> query ("CREATE TEMPORARY TABLE all_wedding_items AS (SELECT item_id, registry_item_id FROM registry_items WHERE wedding_id='$weddingToFilter')");

        //Commit the query
        if (!$new_connection -> commit()) {
            echo "Commit transaction failed";
            exit();
        }

        //These clauses will append all filter conditions that the user chooses
        $whereClause = "";
        $orderByClause = "";
        $sortOrderClause = " DESC";
        $paginationClause = " LIMIT 20 OFFSET 0";

        if ($searchString != NULL) {
            $whereClause .= " AND (LOWER(item_name) LIKE LOWER('%".$searchString."%') OR LOWER(item_category) LIKE LOWER('%".$searchString."%'))";
        }

        if ($filterByCategories != NULL) {
            $whereClause .= " AND (";
            foreach ($filterByCategories as $category) {
                $whereClause .= "LOWER(item_category) LIKE LOWER('%".$category."%') OR ";
            }
            $whereClause .= "0)";
        }

        if ($sortBy == "price_asc") {
            $sortBy = "price";
            $sortOrderClause = " ASC";
        }

        if ($page) {
            $paginationClause = " LIMIT 20 OFFSET ".(($page-1)*20);
        }

        //Get number of pages without limiting
        if ($result = $new_connection -> query("SELECT * FROM items INNER JOIN all_wedding_items ON items.item_id = all_wedding_items.item_id WHERE is_available=1".$whereClause." ORDER BY (stock_level = 0) ASC, ".$sortBy.$sortOrderClause)) {
            $numPages = intdiv((($result -> num_rows) - 1), 20) + 1;
            // Free result set
            $result -> free_result();
        }

        // Perform query, do not show unavailable items and sort out-of-stock items to the end
        if ($result = $new_connection -> query("SELECT *, all_wedding_items.registry_item_id AS registry_id FROM items INNER JOIN all_wedding_items ON items.item_id = all_wedding_items.item_id WHERE is_available=1".$whereClause." ORDER BY (stock_level = 0) ASC, ".$sortBy.$sortOrderClause.$paginationClause)) {
            $itemsList = $result -> fetch_all(MYSQLI_ASSOC);
            // Free result set
            $result -> free_result();
        }
    
        $new_connection -> close();
        return '{"numPages": '.$numPages.',"itemsList": '.json_encode($itemsList).'}';
    }

    function getItemDetails ($item_id) {
        $new_connection = connectToDatabase();

        //Get all details for a single item
        if ($result = $new_connection -> query("SELECT * FROM items WHERE item_id=$item_id")) {
            //Check if the item details were found
            if ($result -> num_rows == 0) {
                //Notify the user if item is not found
                return "Item was not found";
            }

            $itemDetails = $result -> fetch_assoc();
            // Free result set
            $result -> free_result();
        }
    
        $new_connection -> close();
        return $itemDetails;
    }
?>