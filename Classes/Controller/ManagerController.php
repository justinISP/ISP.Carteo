<?php

namespace ISP\Carteo\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\View\FusionView;
use ISP\Carteo\Domain\Model\Dish;
use ISP\Carteo\Domain\Repository\DishRepository;

class ManagerController extends \Neos\Flow\Mvc\Controller\ActionController {


    /**
     *
     * require repository as protected variable
     *
     * @Flow\Inject
     * @var DishRepository
     */
    protected $dishRepository;

    protected $defaultViewObjectName = FusionView::class;

    public function indexAction() {

        $allEntries = $this->dishRepository->getEntries();

        $this->view->assign('allEntries', $allEntries);

    }

    public function addMenuItemAction() {}

    /**
    *
    * create new Dish inside Menu
    *
    * @param Dish $newDish
    * @return void
    */
    public function execAddMenuItemAction(string $name, string $description, string $price, string $moreInfo = NULL, string $category){

        $newDish = new Dish;

        $newDish->setName($name);

        $newDish->setDescription($description);

        $newDish->setPrice($price);

        $newDish->setMoreInfo($moreInfo);

        $newDish->setCategory($category);

        $this->dishRepository->add($newDish);

        $this->redirect('index');

    }

    /**
    *
    * edit Dish inside Menu
    *
    * @param Dish $newDish
    * @return void
    */
    public function editEntryAction(Dish $dish, string $updateName, string $updateValue){

        switch ($updateName) {
            case "name":
                $dish->setName($updateValue);
                break;
            case "description":
                $dish->setDescription($updateValue);
                break;
            case "price":
                $dish->setPrice($updateValue);
                break;
            case "moreInfo":
                $dish->setMoreInfo($updateValue);
                break;
            case "category":
                $dish->setCategory($updateValue);
                break;
        }

        $this->dishRepository->update($dish);
        $this->persistenceManager->persistAll();

        $this->redirect('index');

    }

    /**
    *
    * delete Dish inside Menu
    *
    * @param Dish $newDish
    * @return void
    */
    public function deleteEntryAction(Dish $dish){

        $this->dishRepository->remove($dish);

        $this->redirect('index');

    }


    public function exportMenuAction() { 

        $allEntries = $this->dishRepository->getEntries();
        $dishNames = '';
        foreach($allEntries as $dish){

            $dishNames .= $dish->getName();

        }

        $mpdf = new \Mpdf\Mpdf();
        $mpdf->WriteHTML('<h1 style="color:red;">Hello world!</h1><br><br><p>' . $dishNames . '</p>');
        $mpdf->Output();
            
    }

}

?>