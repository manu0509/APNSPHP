<?php

require_once "vendor/autoload.php";

function getStatusCodeMessage($status)
{
    // these could be stored in a .ini file and loaded
    // via parse_ini_file()... however, this will suffice
    // for an example
    $codes = Array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    );

    return (isset($codes[$status])) ? $codes[$status] : '';
}

// Helper method to send a HTTP response code/message
function sendResponse($status = 200, $body = '', $content_type = 'text/html')
{
    $status_header = 'HTTP/1.1 ' . $status . ' ' . getStatusCodeMessage($status);
    header($status_header);
    header('Content-type: ' . $content_type);
    echo $body;
}


class PushService {
    private $db;
    public $message;
    public $url;


    function __construct() {
        $this->db = new mysqli('localhost', 'root', 'root', 'mobeinnovation');
        //  $this->db->autocommit(FALSE);
        if(isset($_GET["method"])) {
            $method = $_GET["method"];
            if(empty($method) === false) {
                if(strcasecmp($method,'register') == 0) {
                    $this->register();
                } else if(strcasecmp($method,'message') == 0) {
                    $this->pushMessage();
                } else {
                    sendResponse(405, 'Incorrect Method Specified');
                    return false;
                }
            } else {
                sendResponse(400, 'Incorrect Method Specified');
                return false;
            }
        }
    }

    // Destructor - close DB connection
    function __destruct() {
        $this->db->close();
    }

    function register() {
        if($this->db != null) {
            if (isset($_POST["number"]) && isset($_POST['deviceid'])) {
                $number = $_POST["number"];
                $deviceId = $_POST["deviceid"];
                $stmt = $this->db->prepare("INSERT INTO mobnumbers (number,deviceid) VALUES(?,?)");
                $stmt->bind_param("ss", $number,$deviceId);
                $stmt->execute();
                $insert_id = $stmt->insert_id;
                $result = array();
                if($insert_id > 0) {
                    $result[] = array('id'=>$insert_id);
                    sendResponse(200, json_encode($result));
                    return true;
                } else {
                    sendResponse(400, 'Bad Request');
                    return false;
                }
            } else {
                sendResponse(400, 'Bad Request');
                return false;
            }
        }
    }

    function pushMessage() {

        if($this->db != null) {
            if (isset($_GET["number"]) && isset($_GET['message'])) {
                $number = $_GET["number"];
                $message = $_GET["message"];

                $app = new \Push\Push();
                $service = $app->getService("ios","ck.pem");
               // $message = array("Notice" => "aaa");

                $stmt = $this->db->prepare('SELECT deviceid FROM mobnumbers where number='.$number);
                $stmt->execute();
                $stmt->bind_result($deviceId);
                $result = array();
                if($stmt->fetch()) {
                } else {
                    sendResponse(400, 'Device Not Registered');
                    return false;
                }
                $stmt->close();

                $done = $service->sendMessages($deviceId,$message);
                if($done) {
                    sendResponse(200, 'Message sent');
                    return true;
                } else {
                    sendResponse(500, 'Internal Server Error');
                    return false;
                }
            } else {
                sendResponse(400, 'Bad Request');
                return false;
            }
        }
    }
}

$api = new PushService();
?>