<?php

namespace ISP\Carteo\Domain\Model;

/*
 * This file is part of the ISP.Carteo package.
 */

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Entity
 */
class Order {

    /**
     * @var string
     */
    protected $customerName;

    /**
     * @var string
     */
    protected $customerPhone;

    /**
     * @var string
     */
    protected $pickupTime;

    /**
     * @var string
     * @ORM\Column(nullable=true)
     * @ORM\Column(length=250)
     */
    protected $message;

    /**
     * @var string
     */
    protected $messageId;
    
    /**
     * @var \DateTime $created
     */
    protected $created;

    /**
     * @var integer
     */
    protected $closed;

    /**
     * @var string
     * @ORM\Column(length=10000)
     */
    protected $itemsJson;

    /**
     * @return string
     */
    public function getCustomerName()
    {
        return $this->customerName;
    }

    /**
     * @param string $customerName
     * @return void
     */
    public function setCustomerName($customerName)
    {
        $this->customerName = $customerName;
    }

    /**
     * @return string
     */
    public function getCustomerPhone()
    {
        return $this->customerPhone;
    }

    /**
     * @param string $customerPhone
     * @return void
     */
    public function setCustomerPhone($customerPhone)
    {
        $this->customerPhone = $customerPhone;
    }

    /**
     * @return string
     */
    public function getPickupTime()
    {
        return $this->pickupTime;
    }

    /**
     * @param string $pickupTime
     * @return void
     */
    public function setPickupTime($pickupTime)
    {
        $this->pickupTime = $pickupTime;
    }
    
    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return void
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * @param string $messageId
     * @return void
     */
    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;
    }
    
    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     * @return void
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return integer
     */
    public function getClosed()
    {
        return $this->closed;
    }

    /**
     * @param integer $closed
     * @return void
     */
    public function setClosed($closed)
    {
        $this->closed = $closed;
    }

    public function setItems(array $items) {
        $this->itemsJson = json_encode($items);
    }

    public function getItems(): array {
        return json_decode($this->itemsJson, true) ?? [];
    }
    
}

?>
