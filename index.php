<?php
/* header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, PATCH, OPTIONS');
	header('Access-Control-Allow-Headers: token, Content-Type');
	die();
}
 
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json'); */

require_once 'db/DB.php';
require_once './class/Client.php';
require_once './class/Lead.php';
require_once './class/Document.php';

    /*
     ** POSTS
     * *
     */
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $postBody = file_get_contents("php://input");
    $postBody = json_decode($postBody);
    
    if ($_GET['url'] == "login") {
        $ob = new Client();
        $resp = $ob->login ( $postBody->username, $postBody->password );
        if($resp){
            echo json_encode($resp);  
            http_response_code(200);
            } else {
                echo null;
                http_response_code(200);
            }

    } elseif ($_GET['url'] == "docs") {
        $ob = new Document();
        echo json_encode($ob->createBasicNeededDocList($postBody));
        http_response_code(200);

    } elseif ($_GET['url'] == "register") {
        $ob = new Client();
        echo json_encode($ob->registerClient($postBody));
        http_response_code(200);
    }
        
} elseif ($_SERVER['REQUEST_METHOD'] == "PUT") {
    $postBody = file_get_contents("php://input");
    $postBody = json_decode($postBody);
    if ($_GET['url'] == 'client') {
        $ob = new Client();
        if (isset($_GET['lead'])) {
            echo json_encode($ob->updateClient($_GET['lead'], $postBody));
        } else {
            echo json_encode($ob->insertClient($postBody));
        }
        http_response_code(200);
    } elseif ($_GET['url'] == 'docs') {
        $ob = new Document();
        echo json_encode($ob->saveDoc($_GET['lead'], $postBody));

        http_response_code(200);

    } elseif ($_GET['url'] == 'speed') {
        $ob = new Document();
        echo json_encode($ob->speedUp($_GET['lead'], $postBody));

        http_response_code(200);

    } elseif ($_GET['url'] == 'recover') {
        $ob = new Client();
        echo json_encode($ob->recoverSenha($postBody));

        http_response_code(200);
    }

} elseif ($_SERVER['REQUEST_METHOD'] == "GET") {
        if ($_GET['url'] == "client") {
            $ob = new Client();
            echo json_encode($ob->getClient($_GET['lead']));
            http_response_code(200);
            
        } elseif ($_GET['url'] == "doc") {
                $ob = new Document();
                echo json_encode($ob->getDoc($_GET['lead'], $_GET['linha']));
                http_response_code(200);    
                
        } elseif ($_GET['url'] == "docs") {
                $ob = new Document();
                if (isset($_GET['lead'])) {
                    echo json_encode($ob->getAskedDocs($_GET['lead']));
                }
                http_response_code(200);    
                
        } elseif ($_GET['url'] == "docsbase") {
            $ob = new Document();
            echo json_encode($ob->getBasicDocs($_GET['lead']));
            http_response_code(200);
            
        } elseif ($_GET['url'] == "lead") {
            $ob = new Lead();
            echo json_encode($ob->getProcessInfo($_GET['lead']));
            http_response_code(200);
            
        }

} elseif ($_SERVER['REQUEST_METHOD'] == "DELETE") {
    if ($_GET['url'] == "doc") {
        $ob = new Document();
        echo json_encode($ob->deleteReceivedDoc($_GET['lead'], $_GET['linha']));
        http_response_code(200);
        
    }
}
