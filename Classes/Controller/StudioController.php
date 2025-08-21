<?php

namespace ISP\Carteo\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\View\FusionView;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\ContentRepository\Domain\Model\Node;

class StudioController extends \Neos\Flow\Mvc\Controller\ActionController {

    /**
  	* @Flow\Inject
  	* @var Neos\Flow\ResourceManagement\ResourceManager
  	*/
  	protected $resourceManager;

    /**
	* @Flow\Inject
	* @var Neos\ContentRepository\Domain\Service\ContextFactoryInterface
	*/
	protected $contextFactory;

    protected $defaultViewObjectName = FusionView::class;

    public function indexAction() {

        $context = $this->contextFactory->create();
        $q = new FlowQuery([$context->getCurrentSiteNode()]);

        $menus = $q->find('[instanceof ISP.Carteo:Menu]')->get();

        $this->view->assign('menus', $menus);

    }

    public function getSelectedMenu(string $selectedNode){

        $context = $this->contextFactory->create();
        $q = new FlowQuery([$context->getCurrentSiteNode()]);

        $categoriesQ = $q->find('#' . $selectedNode)->children('menuItems')->children('[instanceof ISP.Carteo:Menu.Course]')->get();

        $categories = [];

        foreach($categoriesQ as $categoryQ){

            $catName = $categoryQ->getProperty('name');

            $qCat = new FlowQuery([$categoryQ]);

            $categories[$catName] = $qCat->children('courseItems')->children('[instanceof ISP.Carteo:Menu.Dish]')->get();

        }

        return $categories;

    }

    public function showMenuAction(string $selectedNode) {

        $categories = $this->getSelectedMenu($selectedNode);

        $context = $this->contextFactory->create();
        $q = new FlowQuery([$context->getCurrentSiteNode()]);

        $menuNodeObj = $q->find('#' . $selectedNode)->get(0);
        $menuName = $menuNodeObj->getProperty('name');

        $this->view->assign('categories', $categories);
        $this->view->assign('selectedNode', $selectedNode);
        $this->view->assign('menuNodeObj', $menuNodeObj);
        $this->view->assign('menuName', $menuName);
    
    }

    public function exportMenuAction(string $selectedNode) { 

        $categories = $this->getSelectedMenu($selectedNode);

        $context = $this->contextFactory->create();
        $q = new FlowQuery([$context->getCurrentSiteNode()]);

        $menuNodeObj = $q->find('#' . $selectedNode)->get(0);
        $menuName = $menuNodeObj->getProperty('name');

        $output = '

        <!doctype html>
        <html lang="de">
            <head>
                <meta charset="utf-8" />
                <meta name="viewport" content="width=device-width, initial-scale=1" />
                <title>' . $menuName . '</title>
                <link rel="stylesheet" href="' . $resourceUri = $this->resourceManager->getPublicPackageResourceUri('ISP.Carteo', 'Styles/menuPdfStyling.css') . '"></link>
            </head>
            <body style="background:url('. $resourceUri = $this->resourceManager->getPublicPackageResourceUri('ISP.Carteo', 'Images/menu-bg.png') . ');background-image-resize: 6;">
                <div class="container">
                    <h1>' . $menuName . '</h1>
        ';
        foreach ($categories as $catName => $category) {
            $output .= '<h2>' . $catName . '</h2>';
            $output .= '<table class="menu">';
            foreach ($category as $dish){
                $output .= '
                            <tr>
                                <td class="title">' . $dish->getProperty('name') . '</td>
                                <td class="price">' . $dish->getProperty('price') . '</td>
                            </tr>
                            <tr>
                                <td colspan="2" class="desc">'. $dish->getProperty('description') . '</td>
                            </tr>              
                ';
            }
            $output .= '</table><pagebreak>';
        }

        $output .= '
        
                    <footer>
                        Alle Preise in Euro inkl. MwSt. · Allergene & Zusatzstoffe auf Nachfrage.
                    </footer>
                </div>
            </body>
        </html>
        
        ';

        $mpdf = new \Mpdf\Mpdf();

        $mpdf->WriteHTML($output);
        $mpdf->Output();
            
    }

}

?>