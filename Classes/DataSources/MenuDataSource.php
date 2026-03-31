<?php
namespace ISP\Carteo\DataSources;

use Neos\Neos\Service\DataSource\AbstractDataSource;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Eel\FlowQuery\FlowQuery;

use Neos\Flow\Annotations as Flow;

class MenuDataSource extends AbstractDataSource
{

	/**
	* @Flow\Inject
	* @var Neos\ContentRepository\Domain\Service\ContextFactoryInterface
	*/
	protected $contextFactory;

    /**
     * @var string
     */
    static protected $identifier = 'isp-carteo-menus';

    public function getData(NodeInterface $node = null, array $arguments = [])
    {
        $context = $node->getContext();
        $siteNode = new FlowQuery([$context->getCurrentSiteNode()]);

        $menus = [];

        foreach ($siteNode->find('[instanceof ISP.Carteo:Menu]') as $menu) {
            $menus[] = [
                'label' => $menu->getProperty('name') ?: $menu->getLabel(),
                'value' => $menu->getIdentifier()
            ];
        }

        return $menus;
    }
}