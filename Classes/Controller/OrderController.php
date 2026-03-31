<?php

namespace ISP\Carteo\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\View\FusionView;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\ContentRepository\Domain\Model\Node;
use ISP\Carteo\Domain\Model\Order;
use ISP\Carteo\Domain\Repository\OrderRepository;

class OrderController extends \Neos\Flow\Mvc\Controller\ActionController {

    /**
	* @Flow\Inject
	* @var Neos\ContentRepository\Domain\Service\ContextFactoryInterface
	*/
	protected $contextFactory;

    /**
     * @Flow\Inject
     * @var OrderRepository
     */
    protected $orderRepository;

    protected $defaultViewObjectName = FusionView::class;

    public function indexAction() {}

    public function listOldAction() {

        $allEntries = $this->orderRepository->getClosedEntries();

        $preparedOrders = [];

        foreach ($allEntries as $order) {
            $items = $order->getItems();

            $total = 0;
            foreach ($items as $key => $item) {
                $total += $item['price'] * $item['qty'];
                $items[$key]['sumFormatted'] = number_format($item['price'] * $item['qty'], 2, ',', '.');
            }

            $preparedOrders[] = [
                'customerName' => $order->getCustomerName(),
                'customerPhone' => $order->getCustomerPhone(),
                'pickupTime' => $order->getPickupTime(),
                'created' => $order->getCreated(),
                'message' => $order->getMessage(),
                'items' => $items,
                'totalFormatted' => number_format($total, 2, ',', '.'),
                'orderObj' => $order
            ];
        }

        $this->view->assign('orders', $preparedOrders);

    }

    public function listNewAction() {

        $allEntries = $this->orderRepository->getNewEntries();

        $preparedOrders = [];

        foreach ($allEntries as $order) {
            $items = $order->getItems();

            $total = 0;
            foreach ($items as $key => $item) {
                $total += $item['price'] * $item['qty'];
                $items[$key]['sumFormatted'] = number_format($item['price'] * $item['qty'], 2, ',', '.');
            }

            $preparedOrders[] = [
                'customerName' => $order->getCustomerName(),
                'customerPhone' => $order->getCustomerPhone(),
                'pickupTime' => $order->getPickupTime(),
                'created' => $order->getCreated(),
                'message' => $order->getMessage(),
                'items' => $items,
                'totalFormatted' => number_format($total, 2, ',', '.'),
                'orderObj' => $order
            ];
        }

        $this->view->assign('orders', $preparedOrders);

    }

    /**
    * 
    * close Order
    * 
    * @param Order $order
    * @return void
    */
    public function closeAction($order) {

        $order->setClosed("1");
        $this->orderRepository->update($order);
        $this->persistenceManager->persistAll();
        $this->redirect('listNew');

    }

    /**
    * 
    * reopen Order
    * 
    * @param Order $order
    * @return void
    */
    public function openAction($order) {

        $order->setClosed("0");
        $this->orderRepository->update($order);
        $this->persistenceManager->persistAll();
        $this->redirect('listNew');

    }

    /**
    * 
    * delete Order
    * 
    * @param Order $order
    * @return void
    */
    public function deleteAction($order) {

        $this->orderRepository->remove($order);
        $this->persistenceManager->persistAll();
        $this->redirect('index');

    }

    /**
    *
    * create new Order
    *
    * @param Order $order
    * @return void
    *
    */
    public function submitAction() {

        $data = $this->request->getArguments();

        if (!$data) {
            return json_encode(['status' => 'error']);
        }

        $order = new Order();
        $order->setCustomerName($data['customer']['name'] ?? '');
        $order->setCustomerPhone($data['customer']['phone'] ?? '');
        $order->setPickupTime($data['customer']['pickupTime'] ?? '');

        $s = date('d.m.Y');
        $date = date_create_from_format('d.m.Y', $s);
        $date->getTimestamp();
        $order->setCreated($date);

        $order->setClosed(0);
        $order->setMessage($data['customer']['message'] ?? '');

        $items = [];

        $context = $this->contextFactory->create();
        $q = new FlowQuery([$context->getCurrentSiteNode()]);

        foreach ($data['cart'] as $item) {

            $dishQ = $q->find('#' . $item['id'])->get(0);

            if (!$dishQ) continue;

            $rawPrice = $dishQ->getProperty('price');
            $price = strip_tags($rawPrice);
            $price = str_replace(['€', ' '], '', $price);
            $price = str_replace(',', '.', $price);

            $items[] = [
                'id' => $item['id'],
                'name' => $dishQ->getProperty('name'),
                'price' => (float)$price,
                'qty' => (int)$item['qty']
            ];
        }

        $order->setItems($items);

        $this->orderRepository->add($order);
        $this->persistenceManager->persistAll();

        return json_encode(['status' => 'ok']);
    }

}

?>