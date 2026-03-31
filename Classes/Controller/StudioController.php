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
  	* @var Neos\Flow\Configuration\ConfigurationManager
  	*/
  	protected $configurationManager;

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

        #$categories = $this->getSelectedMenu($selectedNode);

        $context = $this->contextFactory->create();
        $q = new FlowQuery([$context->getCurrentSiteNode()]);

        $categories = $q->find('#' . $selectedNode)->children('menuItems')->children('[instanceof ISP.Carteo:Menu.Course]')->get();

        $menuNodeObj = $q->find('#' . $selectedNode)->get(0);
        $menuName = $menuNodeObj->getProperty('name');

        $logoPath = $this->configurationManager->getConfiguration('Settings', 'ISP.Carteo.styling.pdf.logo');
        $cssPath = $this->configurationManager->getConfiguration('Settings', 'ISP.Carteo.styling.pdf.css');
        $bgImagePath = $this->configurationManager->getConfiguration('Settings', 'ISP.Carteo.styling.pdf.backgroundImage');

        $cssPath = $this->resourceManager->getPackageAndPathByPublicPath($cssPath);
        $bgImagePath = $this->resourceManager->getPackageAndPathByPublicPath($bgImagePath);
        $logoPath = $this->resourceManager->getPackageAndPathByPublicPath($logoPath);

        $output = '

        <!doctype html>
        <html lang="de">
            <head>
                <meta charset="utf-8" />
                <meta name="viewport" content="width=device-width, initial-scale=1" />
                <title>' . $menuName . '</title>
                <link rel="stylesheet" href="' . $resourceUri = $this->resourceManager->getPublicPackageResourceUri($cssPath[0], $cssPath[1]) . '"></link>
            </head>
            <body style="background:url('. $resourceUri = $this->resourceManager->getPublicPackageResourceUri($bgImagePath[0], $bgImagePath[1]) . ');background-image-resize: 6;">
                <div class="container">
                    <img style="text-align:center; height:50px;" src="' . $this->resourceManager->getPublicPackageResourceUri($logoPath[0], $logoPath[1]) . '" />
        ';
        foreach ($categories as $catName => $category) {
            $output .= '<h1>' . $category->getProperty('name') . '</h1>';
            
            $image = $category->getProperty('pic');
            if($image != null){
                $imageResource = $image->getResource();
                $output .= '<p>' . $this->resourceManager->getPublicPersistentResourceUri($imageResource) . '</p>';
            }
            
            $output .= '<table class="menu">';

            $qCat = new FlowQuery([$category]);
            $dishes = $qCat->children('courseItems')->children('[instanceof ISP.Carteo:Menu.Dish]')->get();

            foreach ($dishes as $dish){

                if(($dish->getProperty('name') != null) && ($dish->getProperty('description') != null)) {

                    $output .= '
                                <tr>
                                    <td class="title">' . $dish->getProperty('name') . '</td>
                                    <td class="price">' . $dish->getProperty('price') . '</td>
                                </tr>
                                <tr>
                                    <td class="desc">'. $dish->getProperty('description') . '</td>
                                    <td class="price">&nbsp;&nbsp;&nbsp;&nbsp;</td>
                                </tr>              
                    ';

                } elseif (($dish->getProperty('name') != null) && ($dish->getProperty('description') == null)) {
                    
                    $output .= '
                                <tr>
                                    <td class="title">' . $dish->getProperty('name') . '</td>
                                    <td class="price">' . $dish->getProperty('price') . '</td>
                                </tr>             
                    ';

                } else {

                    $output .= '
                                <tr>
                                    <td class="desc">' . $dish->getProperty('description') . '</td>
                                    <td class="price">' . $dish->getProperty('price') . '</td>
                                </tr>
                    ';

                } 
            }
            $output .= '</table>
                    <footer>
                        Alle Preise in Euro inkl. MwSt. · Allergene & Zusatzstoffe auf Nachfrage.
                    </footer>
            <pagebreak>';
        }

        $output .= '
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