<?php
namespace ISP\Carteo\Domain\Repository;

/*
 * This file is part of the ISP.Carteo package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Repository;

/**
 * @Flow\Scope("singleton")
 */
class DishRepository extends Repository
{
    public function getEntries() {
        $entries = '\ISP\Carteo\Domain\Model\Dish';
        $query = $this->persistenceManager->createQueryForType($entries);
        $result = $query->execute();
        return $result;
    }
}
