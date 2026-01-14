<?php

// namespace MyApp;

// use Ratchet\MessageComponentInterface;
// use Ratchet\ConnectionInterface;

// class Chat implements MessageComponentInterface
// {
//     protected $clients;
//     private $mysqli; // การประกาศตัวแปรสำหรับการเชื่อมต่อฐานข้อมูล

//     public function __construct()
//     {
//         $this->clients = new \SplObjectStorage;

//         // เชื่อมต่อกับฐานข้อมูล
//         $this->mysqli = new \mysqli('localhost', 'root', '', 'allable_db');

//         if ($this->mysqli->connect_error) {
//             die('Connection failed: ' . $this->mysqli->connect_error);
//         }
//     }

//     public function onOpen(ConnectionInterface $conn)
//     {
//         $this->clients->attach($conn);
//         echo "New connection! ({$conn->resourceId})\n";
//     }

//     public function getChatHistory($sender, $receiver)
//     {
//         $stmt = $this->mysqli->prepare("SELECT * FROM chat_messages 
//                                         WHERE (sender = ? AND receiver = ?) 
//                                         OR (sender = ? AND receiver = ?) 
//                                         ORDER BY timestamp ASC");
//         $stmt->bind_param("ssss", $sender, $receiver, $receiver, $sender);
//         $stmt->execute();
//         $result = $stmt->get_result();

//         $messages = [];
//         while ($row = $result->fetch_assoc()) {
//             $messages[] = $row;
//         }

//         $stmt->close();
//         return $messages;
//     }

//     public function onMessage(ConnectionInterface $from, $msg)
//     {
//         $messageData = json_decode($msg, true);

//         $message = $messageData['message'];
//         $sender = $messageData['sender'];
//         $recipient = $messageData['recipient'];
//         $isSent = $messageData['isSent'];

//         // echo "server: $_SERVER\n";
//         echo "Message: $message\n";
//         echo "Sender: $sender\n";
//         echo "Recipient: $recipient\n";
//         echo "isSent: " . ($isSent ? 'true' : 'false') . "\n";

//         // if ($isSent) {
//         //     $stmt = $this->mysqli->prepare("INSERT INTO chat_messages (sender, receiver, message) VALUES (?, ?, ?)");
//         //     $stmt->bind_param("sss", $sender, $recipient, $message);
//         //     $stmt->execute();
//         //     $stmt->close();
//         // }

//         $chatHistory = $this->getChatHistory($sender, $recipient);

//         // ส่งข้อมูลแชทเก่ากลับไปที่ Client
//         foreach ($chatHistory as $message) {
//             $chatMessage = [
//                 'message' => $message['message'],
//                 'sender' => $message['sender'],
//                 'recipient' => $message['receiver'],
//                 'isSent' => true // เปลี่ยนให้เหมาะสม
//             ];
//             $from->send(json_encode($chatMessage));
//         }

//         // ส่งข้อความใหม่ไปยังผู้ใช้
//         $from->send($msg);
//     }

//     public function onClose(ConnectionInterface $conn)
//     {
//         $this->clients->detach($conn);
//         echo "Connection {$conn->resourceId} has disconnected\n";
//     }

//     public function onError(ConnectionInterface $conn, \Exception $e)
//     {
//         echo "An error has occurred: {$e->getMessage()}\n";
//         $conn->close();
//     }

//     // ปิดการเชื่อมต่อฐานข้อมูลเมื่อไม่ใช้งาน
//     public function __destruct()
//     {
//         $this->mysqli->close();
//     }
// }



namespace MyApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class DatabaseHandler
{
    private $mysqli;

    public function __construct()
    {
        $this->mysqli = new \mysqli('localhost', 'root', '', 'perfume_db');
        if ($this->mysqli->connect_error) {
            die('Connection failed: ' . $this->mysqli->connect_error);
        }
    }

    public function getChatHistory($sender, $receiver)
    {
        $stmt = $this->mysqli->prepare("SELECT * FROM chat_messages 
                                        WHERE (sender = ? AND receiver = ?) 
                                        OR (sender = ? AND receiver = ?) 
                                        ORDER BY timestamp ASC");
        $stmt->bind_param("ssss", $sender, $receiver, $receiver, $sender);
        $stmt->execute();
        $result = $stmt->get_result();

        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }

        $stmt->close();
        return $messages;
    }

    public function insertMessage($sender, $receiver, $message)
    {
        $stmt = $this->mysqli->prepare("INSERT INTO chat_messages (sender, receiver, message) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $sender, $receiver, $message);
        $stmt->execute();
        $stmt->close();
    }

    public function __destruct()
    {
        $this->mysqli->close();
    }
}

class Chat implements MessageComponentInterface
{
    protected $clients;
    private $dbHandler;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->dbHandler = new DatabaseHandler(); // Use the DatabaseHandler class
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $messageData = json_decode($msg, true);

        $message = $messageData['message'];
        $sender = $messageData['sender'];
        $recipient = $messageData['recipient'];
        $isSent = $messageData['isSent'];

        // echo "Message: $message\n";
        // echo "Sender: $sender\n";
        // echo "Recipient: $recipient\n";
        // echo "isSent: " . ($isSent ? 'true' : 'false') . "\n";

        if ($isSent) {
            $this->dbHandler->insertMessage($sender, $recipient, $message);
        }

        // $chatHistory = $this->dbHandler->getChatHistory($sender, $recipient);

        // Send chat history back to the client
        // foreach ($chatHistory as $message) {
            // $chatMessage = [
            //     'message' => $message['message'],
            //     'sender' => $message['sender'],
            //     'recipient' => $message['receiver'],
            //     'isSent' => true
            // ];
            // $from->send(json_encode($chatMessage));
        // }

        // Send new message to the user
        $from->send($msg);
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    public function __destruct()
    {
        // Optional: You may want to destroy the DatabaseHandler instance here, but it's not necessary as it's already handled in the DatabaseHandler's __destruct
    }
}
