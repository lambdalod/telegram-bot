<?php
require_once '../bot/api.php';
class UserNotFoundException extends Exception {}
class User {
    private int $id;
    private ?int $telegram_id;
    private string $phone;

    function __construct(int $telegram_id, bool $createuser = false, ?string $phone_number = NULL) {
        global $sql;
        if (!is_null($phone_number) && !preg_match("/^[0-9+]+$/", $phone_number)) throw new InvalidArgumentException("Phone number is invalid format!");
        $q = $sql->query("SELECT * FROM users WHERE telegram_id = '$telegram_id'") or die($sql->error);
        if ($q->num_rows != 1) {
            if ($createuser) {
                if (is_null($phone_number)) throw new InvalidArgumentException("Phone number is NULL");
                $q = $sql->query("SELECT * FROM users WHERE phone = '$phone_number'");
                if ($q->num_rows != 1) throw new Exception("User has no ability to use bot!");
                $sql->query("UPDATE users SET telegram_id = '$telegram_id' WHERE phone = '$phone_number'");
            }
            else throw new UserNotFoundException("User is not found!");
        }
        $d = $q->fetch_assoc();
        $this->telegram_id = $telegram_id;
        $this->id = $d['id'];
        $this->phone = $d['phone'];
    }

    public function getID(): int {
        return $this->id;
    }

    public function getTelegramID(): int {
        return $this->telegram_id;
    }

    public function getPhone(): int {
        return $this->phone;
    }

    public function sendNotification(string $text): void {
        API::sendMessage($this->id, $text, 1);
    }
}