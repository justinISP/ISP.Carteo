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
class OrderRepository extends Repository
{
    public function getNewEntries() {
        
        $entries = '\ISP\Carteo\Domain\Model\Order';
        $closed = "0";
        $query = $this->persistenceManager->createQueryForType($entries);
        $result = $query->matching($query->equals('closed', $closed))->execute();
        return $result;
        
    }

    public function getClosedEntries() {
        
        $entries = '\ISP\Carteo\Domain\Model\Order';
        $closed = "1";
        $query = $this->persistenceManager->createQueryForType($entries);
        $result = $query->matching($query->equals('closed', $closed))->execute();
        return $result;
        
    }

}
