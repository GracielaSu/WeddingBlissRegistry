<?php
    error_reporting(E_ALL ^ E_WARNING);
    header("Access-Control-Allow-Origin: null");
    header("Access-Control-Allow-Credentials: true");
    header("Content-Type: application/json");

    $method = $_SERVER['REQUEST_METHOD'];
    $path = explode('/',$_SERVER['REQUEST_URI']);

    //Initialize cookie values to NULL
    if (!isset($_COOKIE["userType"])) {
        $_COOKIE["userType"] = NULL;
    }

    if (!isset($_COOKIE["userID"])) {
        $_COOKIE["userID"] = NULL;
    }

    if (!isset($_COOKIE["sessionToken"])) {
        $_COOKIE["sessionToken"] = NULL;
    }  

    require_once 'common_functions.php';
    include 'admin_items.php';
    include 'attendee_user_settings.php';
    include 'checkout.php';
    include 'item_search.php';
    include 'login.php';
    include 'notifications.php';
    include 'wedding_registry.php';
    include 'wedding_user_settings.php';

    function validateSession ($sessionToken, $user_id, $user_type) {
        $new_connection = connectToDatabase();

        if ($user_type == "wedding") {
            $table = "wedding_couples";
            $id_type = "wedding_id";
        }
        else if ($user_type == "attendee") {
            $table = "attendees";
            $id_type = "attendee_id";
        }
        else {
            return False;
        }

        if ($result = $new_connection -> query ("SELECT * FROM $table WHERE $id_type=$user_id AND session_token='$sessionToken'")) {
            if ($result -> num_rows == 0) {
                $new_connection -> close();
                return False;            
            }
            else {
                $new_connection -> close();
                return True;
            }
        }
    }
    
    switch ($path[2]) {
        case 'attendee':
            if ($_COOKIE["userType"] != "attendee") {
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request, invalid userType", true, 400);
                break;
            }

            if (!validateSession($_COOKIE["sessionToken"], $_COOKIE["userID"], $_COOKIE["userType"])) {
                header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthenticated", true, 401);
                break;
            }

            if ($method == 'GET') {
                if ($path[3] == 'getOrders') {
                    echo json_encode(getOrderDetails($_COOKIE["userID"]));
                    break;
                }
            }
            else if ($method == 'POST') {
                
            }
            else {
                header($_SERVER["SERVER_PROTOCOL"]." 404 Endpoint Not Found", true, 404);
                break;
            }

        case 'checkout':
            if ($method == 'POST') {
                if ($path[3] == 'checkout') {
                    echo purchaseRegistryItem($_POST["attendeeID"], $_POST["itemDetails"], $_POST["paymentDetails"]);
                    break;
                }
            }
            break;
        case 'commonLists':
            break;
        case 'items':
            if ($method == 'GET') {
                if (str_starts_with($path[3], 'getItemDetails')) {
                    echo json_encode(getItemDetails($_GET['id']));
                    break;
                }
                if (str_starts_with($path[3], 'getItems')) {
                    $categories = $_GET['categories'];
                    //convert category string to an array
                    if ($categories) {
                        $categories = explode(',', $categories);
                    }

                    $sortBy = $_GET['sortBy'];
                    //Set sortBy if it is not included in our search
                    if (!$sortBy) {
                        $sortBy = "review_scores";
                    }

                    echo itemSearchAll($_GET['searchString'], $categories, $sortBy, $_GET['page']);
                    break;
                }
            }
            break;
        case 'itemsRegistry':
            if ($method == 'GET') {
                if (str_starts_with($path[3], 'getItemDetails')) {
                    echo json_encode(getItemDetails($_GET['id']));
                    break;
                }
                if (str_starts_with($path[3], 'getItems')) {
                    $categories = $_GET['categories'];
                    //convert category string to an array
                    if ($categories) {
                        $categories = explode(',', $categories);
                    }

                    $sortBy = $_GET['sortBy'];
                    //Set sortBy if it is not included in our search
                    if (!$sortBy) {
                        $sortBy = "review_scores";
                    }

                    echo itemSearchFilterByWedding($_GET['searchString'], $categories, $sortBy, $_GET['page'], $_GET['weddingID']);
                    break;
                }
            }
            break;
        case 'login':
            if ($method == 'POST') {
                echo checkLogin($_POST['email'], $_POST['password']);
                break;
            }
            break;
        case 'logout':
            if (!validateSession($_COOKIE["sessionToken"], $_COOKIE["userID"], $_COOKIE["userType"])) {
                header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthenticated", true, 401);
                break;
            }
            if ($method == 'POST') {

            }
            break;
        case 'signup':
            if ($method == 'POST') {
                echo createAccount($_POST['email'], $_POST['password'], $_POST['accountInfo']);
                break;
            }
            break;
        case 'notifications':
            break;
        case 'registry':
            if (!validateSession($_COOKIE["sessionToken"], $_COOKIE["userID"], $_COOKIE["userType"])) {
                header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthenticated", true, 401);
                break;
            }

            if ($method == 'POST') {
                if ($_COOKIE["userType"] != "wedding") {
                    header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request, invalid userType", true, 400);
                    break;
                }

                if (str_starts_with($path[3], 'addItem')) {
                    echo addItemToRegistry($_POST['item_id'], $_COOKIE["userID"]);
                    break;
                }
                if (str_starts_with($path[3], 'removeItem')) {
                    echo removeItemFromRegistry($_POST['item_id'], $_COOKIE["userID"]);
                    break;
                }
            }
            else if ($method == 'GET') {
                if (str_starts_with($path[3], 'checkItem')) {
                    echo checkItemInRegistry($_GET['id'], $_GET["wedding_id"]);
                    break;
                }
                if (str_starts_with($path[3], 'checkIfItemPurchased')) {
                    echo checkIfItemPurchased($_GET['registryID']);
                    break;
                }
            }
            break;
        case 'wedding':
            if ($method == 'GET') {
                if (str_starts_with($path[3], 'getWeddingDetails')) {
                    echo getWeddingInfo($_GET["weddingID"]);
                    break;
                }
                if (str_starts_with($path[3], 'getWeddings')) {
                    echo json_encode(getWeddingList());
                    break;
                }
            }
    }
?>