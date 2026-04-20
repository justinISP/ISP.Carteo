<?php

namespace ISP\Carteo\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\View\FusionView;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\ContentRepository\Domain\Model\Node;
use ISP\Carteo\Domain\Model\Order;
use ISP\Carteo\Domain\Repository\OrderRepository;
use Neos\Flow\Mvc\View\JsonView;

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

    /**
     * Meta verifies webhook
     */
    public function verifyAction(): void
    {
        $verifyToken = '98177c25dff95b149249329148e028457ff7bc1aceb96fe6287b62d24e75a95f'; // selbst gewählt, gleicher Wert wie in Meta

        $mode      = $this->request->getArgument('hub_mode');
        $token     = $this->request->getArgument('hub_verify_token');
        $challenge = $this->request->getArgument('hub_challenge');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            $this->response->setStatusCode(200);
            $this->response->setContent($challenge);
        } else {
            $this->response->setStatusCode(403);
        }

        throw new \Neos\Flow\Mvc\Exception\StopActionException();
    }

    /**
     * Meta sending WhatsApp orders
     */
    public function receiveAction(): void
    {
        $rawBody = file_get_contents('php://input');
        $this->verifyMetaSignature($rawBody);

        $order = $this->parseWhatsAppOrder($rawBody);

        if (empty($order) || $this->orderRepository->findOneByMessageId($order['msgId'])) {
            $this->response->setStatusCode(200);
            $this->view->assign('value', ['status' => 'ok']);
            return;
        }
		
        $newOrder = new Order();
        $newOrder->setCustomerName($order['name']);
        $newOrder->setCustomerPhone($order['phone']);
        $newOrder->setPickupTime($order['pickupTime']);
        $newOrder->setMessage($order['message']);
        $newOrder->setCreated($order['receiveDate']);
        $newOrder->setClosed(0);
		$newOrder->setMessageId($order['msgId']);

		$context = $this->contextFactory->create();
        $q = new FlowQuery([$context->getCurrentSiteNode()]);
        foreach ($order['cart'] as $item) {

            $dishQ = $q->find("[instanceof ISP.Carteo:Menu.Dish][name*=~'" . $item['name'] . "']")->get(0);

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

        $newOrder->setItems($items);

        $this->orderRepository->add($newOrder);
        $this->persistenceManager->persistAll();

        $this->response->setStatusCode(200);
        $this->view->assign('value', ['status' => 'ok']);
    }

    private function parseWhatsAppOrder(string $rawBody): array
    {
        $payload = json_decode($rawBody, true);
        $msg     = $payload['entry'][0]['changes'][0]['value']['messages'][0] ?? null;

        if (!$msg || !str_starts_with($msg['text']['body'] ?? '', 'Neue Bestellung:')) {
            return [];
        }

        $text   = $msg['text']['body'];
        $result = [
			'msgId'       => $msg['id'],
            'phone'       => $msg['from'],
            'receiveDate' => (new \DateTime())->setTimestamp((int)$msg['timestamp']),
            'name'        => '',
            'pickupTime'  => '',
            'message'     => '',
            'cart'        => [],
        ];

        foreach (explode("\n", trim($text)) as $line) {
            $line = trim($line);
            if (empty($line))                             continue;
            if (str_starts_with($line, 'Name:'))          $result['name']       = trim(substr($line, 5));
            elseif (str_starts_with($line, 'Abholzeit:')) $result['pickupTime'] = trim(substr($line, 10));
            elseif (str_starts_with($line, 'Nachricht:')) $result['message']    = trim(substr($line, 10));
            elseif (preg_match('/^(\d+)x\s+(.+)$/', $line, $m)) $result['cart'][] = ['qty' => (int)$m[1], 'name' => trim($m[2])];
        }

        return $result;
    }

    private function verifyMetaSignature(string $rawBody): void
    {
        $appSecret = 'dcafbd71844d3b733d77e94cafbe1db8'; 
        $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $appSecret);

        if (!hash_equals($expected, $signature)) {
            $this->response->setStatusCode(403);
            throw new \Neos\Flow\Mvc\Exception\StopActionException();
        }
    }

}

?>
