<?php

namespace ISP\Carteo\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Fusion\View\FusionView;

/**
 * @noinspection PhpUnused
 * @Flow\Scope("singleton")
 */
class OverviewController extends AbstractModuleController
{
    /**
     * @Flow\InjectConfiguration(package="Neos.Neos")
     * @var array
     */
    protected $neosSettings;

    protected $defaultViewObjectName = FusionView::class;

    public function indexAction(): void
    {
        $this->view->assign('neosSettings', $this->neosSettings);
    }
}