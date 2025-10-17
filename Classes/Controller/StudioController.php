<?php

namespace ISP\Carteo\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\View\FusionView;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\Media\Domain\Model\ThumbnailConfiguration;
use Neos\Media\Domain\Service\AssetService;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Media\Domain\Model\Adjustment\CropImageAdjustment;
use Neos\Media\Domain\Model\Thumbnail;

class StudioController extends \Neos\Flow\Mvc\Controller\ActionController {

    /**
  	* @Flow\Inject
  	* @var Neos\Flow\ResourceManagement\ResourceManager
  	*/
  	protected $resourceManager;

    /**
  	* @Flow\Inject
  	* @var Neos\Media\Domain\Repository\AssetRepository
  	*/
  	protected $assetRepository;

    /**
  	* @Flow\Inject
  	* @var Neos\Media\Domain\Repository\ThumbnailRepository
  	*/
  	protected $thumbnailRepository;

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

    public function getExportValues($selectedNode){

        /* query for needed values and props */
        $context = $this->contextFactory->create();
        $q = new FlowQuery([$context->getCurrentSiteNode()]);

        $categories = $q->find('#' . $selectedNode)->children('menuItems')->children('[instanceof ISP.Carteo:Menu.Course]')->get();

        $menuNodeObj = $q->find('#' . $selectedNode)->get(0);
        
        $menuName = $menuNodeObj->getProperty('name');
        $teaser = $menuNodeObj->getProperty('teaser');
        $teaserPic = $menuNodeObj->getProperty('teaserPic');
        $pic = $menuNodeObj->getProperty('pic');

        /* get resourcepaths for logo, css, background */
        $logoPath = $this->configurationManager->getConfiguration('Settings', 'ISP.Carteo.styling.pdf.logo');
        $cssPath = $this->configurationManager->getConfiguration('Settings', 'ISP.Carteo.styling.pdf.css');
        $bgImagePath = $this->configurationManager->getConfiguration('Settings', 'ISP.Carteo.styling.pdf.backgroundImage');

        /* convert resourcepaths for logo, css, background */
        $cssPath = $this->resourceManager->getPackageAndPathByPublicPath($cssPath);
        $bgImagePath = $this->resourceManager->getPackageAndPathByPublicPath($bgImagePath);
        $logoPath = $this->resourceManager->getPackageAndPathByPublicPath($logoPath);

        return [
            'categories' => $categories,
            'menuName' => $menuName,
            'logoPath' => $logoPath,
            'cssPath' => $cssPath,
            'bgImagePath' => $bgImagePath,
            'teaser' => $teaser,
            'teaserPic' => $teaserPic,
            'pic' => $pic
        ];

    }

    public function generateMenuThumbnails($image, $width, $height){

        /* create thumbnail */
        $thumbnailConfiguration = new ThumbnailConfiguration($width, false, $height, false, true, true, false);
        $thumbnail = new Thumbnail($image, $thumbnailConfiguration);
        $image->addThumbnail($thumbnail);
        $this->persistenceManager->persistAll();

        /* get created thumbnail */
        $addedThumbnail = $image->getThumbnail($width, $height);
        $imageResource = $this->resourceManager->getPublicPersistentResourceUri($thumbnail->getResource());

        return $imageResource;

    }

    public function landscapeExportAction(string $selectedNode) { 

        $exportValues = $this->getExportValues($selectedNode);

        $picResource = $this->generateMenuThumbnails($exportValues['pic'], '856', '1063');

        /* config */
        $mpdf = new \Mpdf\Mpdf([
            'default_font' => 'dejavusans',
                'format'       => 'A4-L',
                'margin_left'   => 0,
                'margin_right'  => 0,
                'margin_top'    => 0,
                'margin_bottom' => 0
        ]);

        /* generate pdf */

        /* header */
        $output = '
            <head>
                <meta charset="utf-8" />
                <meta name="viewport" content="width=device-width, initial-scale=1" />
                <link rel="stylesheet" href="' . $resourceUri = $this->resourceManager->getPublicPackageResourceUri($exportValues['cssPath'][0], $exportValues['cssPath'][1]) . '"></link>
            </head> 

                <table width="297mm">
                    <tr height="180mm">
                        <td style="width:148.5mm; text-align:center;">
                            <img src="' . $picResource . '" width="145mm" height="180mm" />
                        </td>
                        <td style="width:148.5mm; text-align:center;padding-left: 5mm;">
                            <img style="padding-top:5mm;height:10mm;" src="' . $this->resourceManager->getPublicPackageResourceUri($exportValues['logoPath'][0], $exportValues['logoPath'][1]) . '" />
        ';

        /* loop query results */
        foreach ($exportValues['categories'] as $catName => $category) {

            $output .= '
                            <table style="padding-top:5mm;" class="course-header">
                                <tr>
                                    <td style="padding-bottom: 0mm;">
                                        <h2>' . $category->getProperty('name') . '</h2>
                                    </td>
                                </tr>
                            </table>
                            <table class="dish-table"> 
            ';

            $qCat = new FlowQuery([$category]);
            $dishes = $qCat->children('courseItems')->children('[instanceof ISP.Carteo:Menu.Dish]')->get();

            foreach ($dishes as $dish){

                $output .=  '
                                <tr>
                                    <td style="padding-bottom: 1mm;" class="dish-name">
                                        ' . $dish->getProperty('name') . '
                                        <div class="description">'. $dish->getProperty('description') . '</div>
                                        <div class="moreInfo">'. $dish->getProperty('moreInfo') . '</div>
                                    </td>
                                    <td class="dish-price">' . $dish->getProperty('price') . '</td>
                                </tr>
                            ';

            }
            $output .= '
                            </table>
            ';
        }        

        $output .= '
                        </td>
                    </tr>
                </table>
        ';

        $teaserPicResource = $this->generateMenuThumbnails($exportValues['teaserPic'], '886', '886');

        $output .= '
            <pagebreak>
                <table width="297mm">
                    <tr height="180mm" style="vertical-align:middle;">
                        <td style="width:148.5mm; text-align:center;">
                            &nbsp;
                        </td>
                        <td style=" text-align:center;">
                            <img src="' . $teaserPicResource . '" width="150mm" height="150mm" />
                            <p>&nbsp;</p>
                            <p style="font-size:25px;">' . $exportValues['teaser'] . '</p>
                        </td>
                    </tr>
                </table>
            
        ';


        $mpdf->WriteHTML($output);
        $mpdf->Output();

    }

    public function portraitExportAction(string $selectedNode) {

        $exportValues = $this->getExportValues($selectedNode);

        /* config */
        $mpdf = new \Mpdf\Mpdf([
            'default_font' => 'dejavusans',
                'format'       => 'A4',
                'margin_left'   => 0,
                'margin_right'  => 0,
                'margin_top'    => 0,
                'margin_bottom' => 0
        ]);

        /* generate pdf */

        /* header */
        $output = '
            <head>
                <meta charset="utf-8" />
                <meta name="viewport" content="width=device-width, initial-scale=1" />
                <link rel="stylesheet" href="' . $resourceUri = $this->resourceManager->getPublicPackageResourceUri($exportValues['cssPath'][0], $exportValues['cssPath'][1]) . '"></link>
            </head> 
        ';

        /* loop query results */
        foreach ($exportValues['categories'] as $catName => $category) {

            $direction = $category->getProperty('direction');
            $image = $category->getProperty('pic');

            if(($direction == 'left') && ($image != null)){

                $imageResource = $this->generateMenuThumbnails($image, '189', '1119');

                /* render layout */
                $output .= '
                            <table class="category-table">
                                <tr>
                                    <td style="width:25%; text-align:left;">
                                        <img src="' . $imageResource . '" width="50mm" height="296mm" />
                                    </td>
                                    <td style="width:75%; padding-left:8mm; padding-right:10mm;vertical-align: top;">
                                        <table class="course-header">
                                            <tr>
                                                <td>
                                                    <img style="height:50px;" src="' . $this->resourceManager->getPublicPackageResourceUri($exportValues['logoPath'][0], $exportValues['logoPath'][1]) . '" />
                                                </td>
                                                <td>
                                                    <h2>' . $category->getProperty('name') . '</h2>
                                                </td>
                                            </tr>
                                        </table>
                                        <table class="dish-table">
                        ';
                $imageImplement = '';
                unset($imageResource);

            } elseif (($direction == 'right') && ($image != null)) {

                $output .= '<table class="category-table">
                                <tr>
                                    <td style="width:75%; padding-left:10mm; padding-right:8mm; vertical-align: top;">
                                        <table class="course-header">
                                            <tr>
                                                <td>
                                                    <img style="height:50px;" src="' . $this->resourceManager->getPublicPackageResourceUri($exportValues['logoPath'][0], $exportValues['logoPath'][1]) . '" />
                                                </td>
                                                <td>
                                                    <h2>' . $category->getProperty('name') . '</h2>
                                                </td>
                                            </tr>
                                        </table>
                                        <table class="dish-table">
                            ';

                $imageResource = $this->generateMenuThumbnails($image, '189', '1119');

                $imageImplement = ' <td style="width:25%; text-align:right;">
                                        <img src="' . $imageResource . '" width="50mm" height="296mm" />
                                    </td>
                                    ';

                unset($imageVariants);
                unset($imageVariant);
                unset($thumbnails);
                unset($thumbnail);
                unset($imageResource);

            } else {

                $output .= '<table class="category-table" style="padding-left:10mm; padding-right:10mm; vertical-align: top;">
                                <tr>
                                    <td style="width:100%;">
                                        <table class="course-header">
                                            <tr>
                                                <td>
                                                    <img style="height:50px;" src="' . $this->resourceManager->getPublicPackageResourceUri($exportValues['logoPath'][0], $exportValues['logoPath'][1]) . '" />
                                                </td>
                                                <td>
                                                    <h2>' . $category->getProperty('name') . '</h2>
                                                </td>
                                            </tr>
                                        </table>
                                        <table class="dish-table">
                            ';
                $imageImplement = '';
                
            }

            $qCat = new FlowQuery([$category]);
            $dishes = $qCat->children('courseItems')->children('[instanceof ISP.Carteo:Menu.Dish]')->get();

            foreach ($dishes as $dish){

                $output .=  '
                
                                            <tr>
                                                <td class="dish-name">
                                                    ' . $dish->getProperty('name') . '
                                                    <div class="description">'. $dish->getProperty('description') . '</div>
                                                    <div class="moreInfo">'. $dish->getProperty('moreInfo') . '</div>
                                                </td>
                                                <td class="dish-price">' . $dish->getProperty('price') . '</td>
                                            </tr>


                            ';

            }
            $output .= '                </table>
                                    </td>
                                    ' . $imageImplement . '
                                </tr>
                            </table>
                <div style="text-align:center; font-size:9pt;">
                    Alle Preise in Euro inkl. MwSt. · Allergene & Zusatzstoffe auf Nachfrage.
                </div>
            <pagebreak>';
        }        

        $mpdf->WriteHTML($output);
        $mpdf->Output();

    }
}

?>