<?php

require_once 'phpQuery.php';

class Parser
{
    private $url;

    public function __construct($url)
    {
        $this->url = $url;
    }

    public function parse()
    {
        $tickets = [];
        if (strpos($this->url, 'tickethunt.net') !== false) {
            $tickets = self::parseTickethunt();
        } elseif (strpos($this->url, 'kasa.in.ua') !== false) {
            $tickets = self::parseKassa();
        }
        return $tickets;
    }

    public function parseTickethunt()
    {
        $doc = $this->getHtmlDocument();
        return 'Tickethunt' . $this->getHtmlDocument();
    }

    public function parseKassa()
    {
        $doc = $this->getHtmlDocument();
        $content = $doc->find('body')->html();
        $eventId = $this->getStringBetween($content, "'id': '", "',");
        $accessToken = $this->getStringBetween($content, " __kasa('access_token', '", "');");
        $getTicketInfoJson = file_get_contents("https://hall.tms.net.ua/scheme?eventId=$eventId&mode=buy_tickets&access_token=$accessToken");
        $data = json_decode($getTicketInfoJson);
        $freeTickets = $this->proccessKassaSvg($data->svg->data, $eventId);
        return $freeTickets;
    }

    private function proccessKassaSvg($svg, $eventId)
    {
        $freeTickets = [];
        $html = phpQuery::newDocumentHTML($svg);
        $ticketPaths = $html->find('path.rect');
        $date = date("Y-m-d");
        foreach ($ticketPaths as $ticketPath) {
            $pqTicketPath = pq($ticketPath);
            $isFree = (bool)$pqTicketPath->attr('data-selectable');
            $type = $pqTicketPath->attr('data-type');
            if ($isFree && ($type == 'seat')) {
                $id = $pqTicketPath->attr('id');
                $row = $this->getStringBetween($id, 'row', 'col');
                $col = $this->getStringBetween($id, 'col', 'sector');
                $sector = explode('sector', $id)[1];
                //$color = $pqTicketPath->attr('style');
                $price = 50; //demo
                if ($row && $col && $sector) {
                    //structure - `parsing_date`, `sector`, `row`, `col`, `price`, `event_id`
                    $freeTickets[] = [$date, $sector, $row, $col, $price, $eventId];
                }
            }
        }
        return $freeTickets;
    }

    private function getHtmlDocument()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $html = curl_exec($ch);
        curl_close($ch);
        $doc = phpQuery::newDocumentHTML($html);
        return $doc;
    }

    private function getStringBetween($string, $start, $end)
    {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

}