<?php

class Ticket
{

    private $connection;
    private $tableName = "ticket";

    public $id;
    public $parsing_date;
    public $sector;
    public $row;
    public $col;
    public $price;
    public $event_id;

    public function __construct($db)
    {
        $this->connection = $db;
    }

    public function getTickets($eventId, $date)
    {
        $sql= "SELECT * FROM " . $this->tableName ." WHERE ( `event_id` = :event_id AND `parsing_date` = :parsing_date )";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':event_id', $eventId, PDO::PARAM_INT);
        $stmt->bindValue(':parsing_date', $date, PDO::PARAM_STR);
        $stmt->execute();
        $tickets = $stmt->fetchAll();
        return $tickets;
    }

    public function insertFree($parsedData)
    {
        $colholders = $this->prepareMultiplyRowscolholders($parsedData);
        $params = $this->prepareMultiplyRowsParams($parsedData);
        $query = "INSERT INTO `" . $this->tableName . "` (`parsing_date`, `sector`, `row`, `col`, `price`, `event_id`) VALUES " . $colholders;
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    private function prepareMultiplyRowscolholders($parsedData)
    {
        $row_length = count($parsedData[0]);
        $nb_rows = count($parsedData);
        $length = $nb_rows * $row_length;
        $preparedData = implode(',', array_map(
            function ($el) {
                return '(' . implode(',', $el) . ')';
            },
            array_chunk(array_fill(0, $length, '?'), $row_length)
        ));
        return $preparedData;
    }

    private function prepareMultiplyRowsParams($parsedData)
    {
        $params = [];
        foreach ($parsedData as $row) {
            foreach ($row as $value) {
                $params[] = $value;
            }
        }
        return $params;
    }

}