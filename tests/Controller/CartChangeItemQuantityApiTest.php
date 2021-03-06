<?php

declare(strict_types=1);

namespace Tests\Sylius\ShopApiPlugin\Controller;

use League\Tactician\CommandBus;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\ShopApiPlugin\Command\PickupCart;
use Sylius\ShopApiPlugin\Command\PutSimpleItemToCart;
use Symfony\Component\HttpFoundation\Response;

final class CartChangeItemQuantityApiTest extends JsonApiTestCase
{
    private static $acceptAndContentTypeHeader = ['CONTENT_TYPE' => 'application/json', 'ACCEPT' => 'application/json'];

    /**
     * @test
     */
    public function it_does_not_allow_to_change_quantity_if_cart_does_not_exists()
    {
        $this->loadFixturesFromFiles(['shop.yml']);

        $data =
<<<EOT
        {
            "quantity": 5
        }
EOT;
        $this->client->request('PUT', '/shop-api/carts/SDAOSLEFNWU35H3QLI5325/items/1', [], [], static::$acceptAndContentTypeHeader, $data);
        $response = $this->client->getResponse();

        $this->assertResponse($response, 'cart/validation_cart_and_cart_item_not_exist_response', Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     */
    public function it_changes_item_quantity()
    {
        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var CommandBus $bus */
        $bus = $this->get('tactician.commandbus');
        $bus->handle(new PickupCart($token, 'WEB_GB'));
        $bus->handle(new PutSimpleItemToCart($token, 'LOGAN_MUG_CODE', 3));

        $data =
<<<EOT
        {
            "quantity": 5
        }
EOT;
        $this->client->request('PUT', '/shop-api/carts/SDAOSLEFNWU35H3QLI5325/items/' . $this->getFirstOrderItemId($token), [], [], static::$acceptAndContentTypeHeader, $data);
        $response = $this->client->getResponse();

        $this->assertResponse($response, 'cart/filled_cart_with_simple_product_summary_response', Response::HTTP_OK);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_set_quantity_lower_than_one()
    {
        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var CommandBus $bus */
        $bus = $this->get('tactician.commandbus');
        $bus->handle(new PickupCart($token, 'WEB_GB'));
        $bus->handle(new PutSimpleItemToCart($token, 'LOGAN_MUG_CODE', 3));

        $data =
<<<EOT
        {
            "quantity": 0
        }
EOT;
        $this->client->request('PUT', '/shop-api/carts/SDAOSLEFNWU35H3QLI5325/items/' . $this->getFirstOrderItemId($token), [], [], static::$acceptAndContentTypeHeader, $data);
        $response = $this->client->getResponse();

        $this->assertResponse($response, 'cart/validation_quantity_lower_than_one_response', Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_change_quantity_without_quantity_defined()
    {
        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var CommandBus $bus */
        $bus = $this->get('tactician.commandbus');
        $bus->handle(new PickupCart($token, 'WEB_GB'));
        $bus->handle(new PutSimpleItemToCart($token, 'LOGAN_MUG_CODE', 3));

        $this->client->request('PUT', '/shop-api/carts/SDAOSLEFNWU35H3QLI5325/items/' . $this->getFirstOrderItemId($token), [], [], static::$acceptAndContentTypeHeader);
        $response = $this->client->getResponse();

        $this->assertResponse($response, 'cart/validation_quantity_lower_than_one_response', Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_change_quantity_if_cart_item_does_not_exists()
    {
        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var CommandBus $bus */
        $bus = $this->get('tactician.commandbus');
        $bus->handle(new PickupCart($token, 'WEB_GB'));

        $data =
<<<EOT
        {
            "quantity": 5
        }
EOT;
        $this->client->request('PUT', '/shop-api/carts/SDAOSLEFNWU35H3QLI5325/items/420', [], [], static::$acceptAndContentTypeHeader, $data);
        $response = $this->client->getResponse();

        $this->assertResponse($response, 'cart/validation_cart_item_not_exists_response', Response::HTTP_BAD_REQUEST);
    }

    private function getFirstOrderItemId(string $orderToken): string
    {
        /** @var OrderRepositoryInterface $orderRepository */
        $orderRepository = $this->get('sylius.repository.order');

        $order = $orderRepository->findOneBy(['tokenValue' => $orderToken]);

        /** @var OrderItemInterface $orderItem */
        $orderItem = $order->getItems()->first();

        return (string) $orderItem->getId();
    }
}
