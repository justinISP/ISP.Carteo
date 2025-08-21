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
class Dish
{

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $price;

    /**
     * @var string
     */
    protected $moreInfo;

    /**
     * @var string
     */
    protected $category;


    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }
    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
    /**
     * @return string
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param string $price
     * @return void
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }
    /**
     * @return string
     */
    public function getMoreInfo()
    {
        return $this->moreInfo;
    }

    /**
     * @param string $moreInfo
     * @return void
     */
    public function setMoreInfo($moreInfo)
    {
        $this->moreInfo = $moreInfo;
    }
    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param string $category
     * @return void
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }
}
