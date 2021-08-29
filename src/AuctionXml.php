<?php

namespace Yahooauc;

use SimpleXMLElement;

class AuctionXml
{
    private $html;
    private $xml;

    public function __construct($body)
    {
        $this->html = Parser::getHtmlDom($body);
        $this->xml = new SimpleXMLElement('<ResultSet><Result></Result></ResultSet>');

        $this->addTitle();
        $this->addSellerId();
        $this->addImagesUrl();
        $this->addPrice();
        $this->addDetail();
        $this->addStatus();
    }

    public function setAuctionId($auctionId)
    {
        $this->xml->Result->AuctionID = $auctionId;
    }

    public function setAuctionUrl($url)
    {
        $this->xml->Result->AuctionItemUrl = $url;
    }

    public function getXml()
    {
        return $this->xml;
    }

    private function addTitle()
    {
        $this->xml->Result->Title = Parser::getAuctionTitle($this->html);
    }

    private function addSellerId()
    {
        $this->xml->Result->Seller->Id = Parser::getAuctionSellerId($this->html);
    }

    public function addImagesUrl()
    {
        $this->xml->Result->addChild('Img');

        foreach (Parser::getAuctionImagesUrl($this->html) as $key => $url) {
            $childName = 'Image'.++$key;
            $this->xml->Result->Img->$childName = $url;
        }
    }

    public function addPrice()
    {
        $data = Parser::getAuctionPrice($this->html);

        $this->xml->Result->Price = $data['price'];
        $this->xml->Result->TaxinPrice = ($data['taxPrice'] > 0) ? $data['taxPrice'] : $data['price'];
    }

    public function addDetail()
    {
        $data = Parser::getAuctionDetail($this->html);

        $this->xml->Result->StartTime = $data['start'];
        $this->xml->Result->EndTime = $data['end'];
    }

    private function addStatus()
    {
        $this->xml->Result->Status = Parser::getAuctionStatus($this->html);
    }
}
