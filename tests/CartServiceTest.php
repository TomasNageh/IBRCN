<?php

declare(strict_types=1);

namespace Ibrcn\Tests;

use CartService;
use PHPUnit\Framework\TestCase;
use PDO;

/**
 
 * 1) normalizeIncomingItemForAdd — checks the cart "add one book" data. Bad data → null. Good data → array.
 * 2) normalizeIncomingCart — checks a whole list from the browser. Wrong type → []. Good rows stay, bad rows skipped.
 *
 * Run from folder ibrcn:  php vendor/bin/phpunit
 * Test case table:        docs/test_case_sheet.csv
 */
final class CartServiceTest extends TestCase
{
    private CartService $cartService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartService = new CartService($this->createMock(PDO::class));
    }

    public function testNormalizeIncomingItemForAddReturnsNullWhenBookIdInvalid(): void
    {
        $this->assertNull($this->cartService->normalizeIncomingItemForAdd(array(
            'book_id' => 0,
            'name' => 'Title',
            'unitPrice' => 10.0,
            'image' => 'x.jpg',
        )));
    }

    public function testNormalizeIncomingItemForAddReturnsNullWhenTitleEmpty(): void
    {
        $this->assertNull($this->cartService->normalizeIncomingItemForAdd(array(
            'book_id' => 1,
            'name' => '   ',
            'unitPrice' => 10.0,
            'image' => 'x.jpg',
        )));
    }

    public function testNormalizeIncomingItemForAddReturnsNullWhenUnitPriceNonPositive(): void
    {
        $this->assertNull($this->cartService->normalizeIncomingItemForAdd(array(
            'book_id' => 1,
            'name' => 'Book',
            'unitPrice' => 0,
            'image' => 'x.jpg',
        )));
    }

    public function testNormalizeIncomingItemForAddReturnsNullWhenImageEmpty(): void
    {
        $this->assertNull($this->cartService->normalizeIncomingItemForAdd(array(
            'book_id' => 1,
            'name' => 'Book',
            'unitPrice' => 9.99,
            'image' => '',
        )));
    }

    public function testNormalizeIncomingItemForAddSuccessPathUsesNameAliasesAndDefaults(): void
    {
        $out = $this->cartService->normalizeIncomingItemForAdd(array(
            'book_id' => 42,
            'title' => '  Aliased Title  ',
            'quantity' => 0,
            'unitPrice' => 12.5,
            'image' => 'cover.png',
            'author' => 'A. Writer',
        ));

        $this->assertIsArray($out);
        $this->assertSame(42, $out['book_id']);
        $this->assertSame(1, $out['quantity']);
        $this->assertSame('Aliased Title', $out['title']);
        $this->assertSame('A. Writer', $out['author']);
        $this->assertSame(12.5, $out['unitPrice']);
        $this->assertStringContainsString('12.50', $out['price']);
    }

    public function testNormalizeIncomingCartReturnsEmptyForNonArray(): void
    {
        $this->assertSame(array(), $this->cartService->normalizeIncomingCart('not-array'));
        $this->assertSame(array(), $this->cartService->normalizeIncomingCart(null));
    }

    public function testNormalizeIncomingCartSkipsNonArrayItems(): void
    {
        $out = $this->cartService->normalizeIncomingCart(array(
            'x',
            array(
                'book_id' => 1,
                'title' => 'Ok',
                'image' => 'i.jpg',
                'unit_price' => 5.0,
            ),
        ));

        $this->assertCount(1, $out);
        $this->assertSame(1, $out[0]['book_id']);
    }

    public function testNormalizeIncomingCartSkipsInvalidBookIdOrMissingFields(): void
    {
        $out = $this->cartService->normalizeIncomingCart(array(
            array('book_id' => -1, 'title' => 'T', 'image' => 'i.jpg'),
            array('book_id' => 2, 'title' => '', 'image' => 'i.jpg'),
            array('book_id' => 3, 'title' => 'Ok', 'image' => ''),
        ));

        $this->assertSame(array(), $out);
    }

    public function testNormalizeIncomingCartAcceptsBookIdAliasAndClampsQuantity(): void
    {
        $out = $this->cartService->normalizeIncomingCart(array(array(
            'bookId' => 7,
            'name' => 'N',
            'image' => 'pic.jpg',
            'unitPrice' => 3.0,
            'quantity' => -5,
        )));

        $this->assertCount(1, $out);
        $this->assertSame(7, $out[0]['book_id']);
        $this->assertSame(1, $out[0]['quantity']);
        $this->assertSame('EGP 3.00', $out[0]['price']);
    }
}
