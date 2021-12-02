<?php

class TelegramException extends Exception { }
class API {
    private const TOKEN = 'TOKEN';
    private const API_URL = 'https://api.telegram.org/bot' . self::TOKEN . '/';
    private static function issueWebhookAnswer(string $method, array|false $params = false): void {
        if ($params === false) {
            $params = array();
        }
        $params['method'] = $method;
        header("Content-Type: application/json");
        echo json_encode($params); // issueWebhookAnswer echoes json payload to webhook instead of actually calling Telegram API
    }
    private static function curlExec(CurlHandle $handle): mixed {
        $response = curl_exec($handle);
        if ($response === false) { // cURL request failed
            $errno = curl_errno($handle);
            $error = curl_error($handle);
            curl_close($handle);
            throw new Exception("CURL returned error {$errno}: {$error}");
        }
        $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
        curl_close($handle);
        if ($http_code >= 500) { // do not want to DDOS server if something goes wrong
            sleep(10);
            return self::curlExec($handle); // Try executing request again
        } elseif ($http_code != 200) { // If request was not successful
            $response = json_decode($response, true);
            if ($http_code == 401) {
                throw new InvalidArgumentException('Invalid access token provided');
            } else {
                throw new TelegramException("Request has failed with error {$response['error_code']}: {$response['description']}");
            }
        } else {
            $response = json_decode($response);
            $response = $response->result;
        }
        return $response;
    }
    public static function executeMethod(string $method, array|false $params = false): mixed {
        if ($params === false) {
            $params = array();
        }
        foreach ($params as $key => &$val) {
            // encoding to JSON array parameters, for example reply_markup
            if (!is_numeric($val) && !is_string($val)) {
                $val = json_encode($val);
            }
        }
        $url = self::API_URL . $method . '?' . http_build_query($params);
        $handle = curl_init($url); // Init cURL handle with required options
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($handle, CURLOPT_TIMEOUT, 60);
        try {
            return self::curlExec($handle);
        } catch (TelegramException $e) {
            throw new TelegramException($e->getMessage(), $e->getCode(), $e);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }
    public static function executeMethodJSON(string $method, array|false $parameters = false): mixed {
        if ($parameters === false) {
            $parameters = array();
        }

        $parameters["method"] = $method;

        $handle = curl_init(self::API_URL);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($handle, CURLOPT_TIMEOUT, 60);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
        curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

        try {
            return self::curlExec($handle);
        } catch (TelegramException $e) {
            throw new TelegramException($e->getMessage(), $e->getCode(), $e);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }
    private static function methodExecutor(string $method, array|false $array = false, int $executor = 0): void {
        if ($array === false) $array = array();
        try {
            switch ($executor) {
                case 0:
                default:
                    self::issueWebhookAnswer($method, $array);
                    break;
                case 1:
                    self::executeMethodJSON($method, $array);
                    break;
                case 2:
                    self::executeMethod($method, $array);
                    break;
            }
        } catch (TelegramException $e) {
            throw new TelegramException($e->getMessage(), $e->getCode(), $e);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }
    public static function sendMessage(int|string $chat, string $msg, int $type = 0): void {
        if (empty($msg)) {
            throw new InvalidArgumentException("You are trying to send blank message!");
        }
        try {
            self::methodExecutor("sendMessage", array('chat_id' => $chat, "text" => $msg), $type);
        } catch (TelegramException $e) {
            throw new TelegramException($e->getMessage(), $e->getCode(), $e);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }
    public static function sendKeyboardedMessage(int|string $chat, string $msg, string $kb, int $type = 0): void {
        if (empty($msg)) {
            throw new InvalidArgumentException("You are trying to send blank message!");
        } elseif (empty($kb)) {
            throw new InvalidArgumentException("You are trying to send empty keyboard");
        }
        try {
            self::methodExecutor("sendMessage", array('chat_id' => $chat, "text" => $msg, "reply_markup" => $kb), $type);
        } catch (TelegramException $e) {
            throw new TelegramException($e->getMessage(), $e->getCode(), $e);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }
    public static function sendLocation(int|string $chat, float $lat, float $lon, int $type = 1) {
        try {
            self::methodExecutor("sendLocation", array('chat_id' => $chat, "latitude" => $lat, "longitude" => $lon), $type);
        } catch (TelegramException $e) {
            throw new TelegramException($e->getMessage(), $e->getCode(), $e);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }
    public static function editInlineMessage(int $editid, int|string $chatid, string $msg, string $kb, int $type = 0): void {
        if (empty($msg)) {
            throw new InvalidArgumentException("You are trying to send blank message!");
        } elseif (empty($kb)) {
            throw new InvalidArgumentException("You are trying to send empty keyboard!");
        }
        try {
            self::methodExecutor("editMessageText", array('chat_id' => $chatid, 'message_id' => $editid, "text" => $msg, "reply_markup" => $kb), $type);
        } catch (TelegramException $e) {
            throw new TelegramException($e->getMessage(), $e->getCode(), $e);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }
    public static function getFile(string $file_id): mixed {
        if (empty($file_id)) throw new InvalidArgumentException("Empty file id!");
        try {
            return self::executeMethod("getFile", array('file_id' => $file_id));
        } catch (TelegramException $e) {
            throw new TelegramException($e->getMessage(), $e->getCode(), $e);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }
    public static function downloadFile(string $file_path) {
        $link = "https://api.telegram.org/file/bot" . self::TOKEN . "/" . $file_path;
        $file = file_get_contents($link);
        return $file;
    }
}
class Message {
    private int|string $peer;
    private int $uid;
    private ?string $text;
    private ?object $contact;
    private ?array $image;
    function __construct(object $msg) {
        if (!isset($msg->chat)) throw new InvalidArgumentException("No chat arg, wrong var passed?");
        $this->peer = $msg->chat->id;
        $this->uid = $msg->from->id;
        $this->text = $msg->text ?? NULL;
        $this->contact = $msg->contact ?? NULL;
        $this->image = $msg->photo ?? NULL;
    }
    public function getPeer(): int|string {
        return $this->peer;
    }
    public function getUserID(): int {
        return $this->uid;
    }
    public function getText(): ?string {
        return $this->text;
    }
    public function getUptext(): string {
        return mb_strtoupper($this->text);
    }
    public function getContact(): ?object {
        return $this->contact;
    }
    public function getImage(): ?array {
        return $this->image;
    }
}
