<?php
require_once '../bot/api.php';
class UserNotFoundException extends Exception {}
class User {
    private int $id;
    private ?int $telegram_id;
    private string $phone;
    private string $name;
    private ?string $patr;

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
        $this->name = $d['name'];
        $this->patr = $d['patronymic'];
    }

    public function getName(): string {
        return $this->name;
    }

    public function getNameAndPatronymic(): string {
        if (is_null($this->patr)) $p = "";
        else $p = " {$this->patr}";
        return "{$this->name}{$p}";
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

    public function getNewPassword($length = 12): string {
        global $sql;
        $q = $sql->query("SELECT hash FROM users WHERE id = '$this->id'");
        $hash = $q->fetch_row()[0];
        if (is_null($hash)) {
            $str = substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
            $hash = password_hash($str, PASSWORD_DEFAULT);
            $sql->query("UPDATE users SET hash = '$hash' WHERE id = '$this->id'");
            return $str;
        } else throw new ValueError("Password is already set for user!");
    }
}