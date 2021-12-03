<?php
/*
 * Handler of Telegram callbacks
 */

require '../service/requireall.php';

$content = file_get_contents("php://input");
$update = json_decode($content);

if (!$update) {
    // received wrong update, must not happen
    exit;
}

if (isset($update->message)) {
    // new message
    $msg = $update->message;
    $msg = new Message($msg);
    $peer = $msg->getPeer();
    $uid = $msg->getUserID();
    $text = $msg->getText();
    $uptext = $msg->getUptext();
    $contact = $msg->getContact();
    $image = $msg->getImage();

    // БЛОК РЕГИСТРАЦИИ
    try {
        $user = new User($uid);
    } catch (UserNotFoundException $e) {
        if (!is_null($contact)) {
            if ($contact->user_id != $uid) {
                API::sendMessage($peer, "Произошла ошибка! Повторите операцию!");
                exit;
            }
            $phone = $contact->phone_number;
            if (mb_substr($phone, 0, 1) != "+") $phone = "+".$phone;
            try {
                $user = new User($uid, true, $phone);
            } catch (Exception $e) {
                API::sendMessage($peer, "Вы не зарегистрированы в системе!");
                exit;
            }
            $password = $user->getNewPassword();
            API::sendKeyboardedMessage($peer, "Здравствуйте, {$user->getNameAndPatronymic()}! Это чат-бот Промсвязьбанка для сотрудников. Высылаю Вам логин и пароль для доступа в личный кабинет:\n\nЛогин: {$phone}\nПароль: {$password}\n\nРады видеть Вас в компании!\n\nВ случае возникновения трудностей - зайдайте вопрос в этом чате и мы постараемся ответить :)", json_encode(array("remove_keyboard" => true), JSON_UNESCAPED_UNICODE));
        } else {
            $keyboard = array(
                "resize_keyboard" => true,
                "one_time_keyboard" => true,
                "keyboard" => array(
                    array(
                        array(
                            "text" => "Отправить номер телефона",
                            "request_contact" => true
                        )
                    )
                )
            );
            $kb = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
            API::sendKeyboardedMessage($peer, "Для использования бота войдите с помощью номера телефона.", $kb);
            exit;
        }
    }
    $bca = BotQuestions::checkAnswer(mb_strtolower($text));
    if (!is_null($bca)) {
        API::sendMessage($peer, "{$user->getName()}, cпасибо за вопрос!\n\n{$bca}");
    } else {
        API::sendMessage($peer, "{$user->getName()}, cпасибо за вопрос! К сожалению, я не понимаю, о чём идёт речь. Ваш вопрос переадресован ментору.");
    }
}
