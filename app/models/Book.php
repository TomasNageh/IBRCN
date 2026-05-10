<?php

/**
 * FILE: Book.php
 * PURPOSE: PDO-backed catalog/inventory queries for storefront listings, search, and recommendation helpers feeding homepage rails.
 * USED BY: Reader storefront controllers, cart/order flows, Strategy recommendation implementations (`TopPicksStrategy`, `HistoryBasedStrategy`).
 * DESIGN PATTERN: None — persistence gateway invoked via shared `DB::getInstance()` connections injected per instance.
 */

// Represents bookstore titles/stock listings and formats numeric prices consistently for reader-visible templates (model layer).
class Book
{
    private PDO $db;

    /**
     * Stores the active PDO handle shared across models constructed during a single request lifecycle.
     *
     * @param PDO $databaseConnection PDO configured by bootstrap singleton wiring (`DB::getInstance()`).
     * @return void
     */
    public function __construct(PDO $databaseConnection)
    {
        $this->db = $databaseConnection;
    }

    /**
     * Exposes the PDO connection backing this model so coordinated services (RecommendationEngine) can open sibling models safely.
     *
     * @return PDO Same connection injected during construction (no new sockets opened).
     */
    public function getDatabaseConnection(): PDO
    {
        return $this->db;
    }

    /**
     * Loads homepage featured titles exactly as before refactor (SQL untouched) including derived `price_*` presentation keys.
     *
     * @return list<array<string, mixed>> Rows grouped catalog listings ready for reader templates (≤10 titles).
     */
    public function getFeaturedBooks(): array
    {
        // Store amounts are numeric; display currency is EGP (NFR clarity / project locale).
        $sql = "SELECT b.book_id, b.title, b.author, b.isbn, b.cover_image, MIN(i.price) AS price
                FROM books b
                JOIN store_inventory i ON b.book_id = i.book_id
            WHERE (i.quantity - COALESCE(i.hold_quantity, 0)) > 0
                GROUP BY b.book_id, b.title, b.author, b.isbn, b.cover_image
                LIMIT 10";

        $statement = $this->db->query($sql);
        $rows = $statement->fetchAll();

        return $this->attachListingPricePresentationFields($rows);
    }

    /**
     * Personalized rails powered strictly by prior pickup orders for readers who opted into history-driven suggestions via schema toggle.
     *
     * @param int $readerUserId Authenticated reader foreign key referencing `orders.reader_id`.
     * @return list<array<string, mixed>> Matches featured listing shape; empty array triggers Strategy fallback to top picks elsewhere.
     */
    public function getRecommendationsFromPurchaseHistory(int $readerUserId): array
    {
        if ($readerUserId <= 0) {
            return array();
        }

        $sql = "SELECT b.book_id, b.title, b.author, b.isbn, b.cover_image, MIN(i.price) AS price
                FROM books b
                JOIN store_inventory i ON b.book_id = i.book_id
                INNER JOIN order_items oi ON oi.book_id = b.book_id
                INNER JOIN orders o ON o.order_id = oi.order_id AND o.reader_id = :reader_id
                WHERE (i.quantity - COALESCE(i.hold_quantity, 0)) > 0
                GROUP BY b.book_id, b.title, b.author, b.isbn, b.cover_image
                ORDER BY MAX(o.created_at) DESC
                LIMIT 10";

        $statement = $this->db->prepare($sql);
        $statement->execute(array('reader_id' => $readerUserId));
        $rows = $statement->fetchAll();

        return $this->attachListingPricePresentationFields($rows);
    }

    /**
     * Mutates catalog rows in-place style identical to legacy loops by adding `price_value` + formatted EGP strings for templates.
     *
     * @param array<int, array<string, mixed>> $listingRows Raw SQL rows containing numeric `price` column values.
     * @return list<array<string, mixed>> Same rows enriched with presentation helpers consumed by storefront UI partials.
     */
    private function attachListingPricePresentationFields(array $listingRows): array
    {
        foreach ($listingRows as &$listingRow) {
            $priceValue = (float) $listingRow['price'];
            $listingRow['price_value'] = $priceValue;
            $listingRow['price_egp_formatted'] = 'EGP ' . number_format($priceValue, 2);
        }
        unset($listingRow);

        return $listingRows;
    }

    /**
     * Performs LIKE-powered catalog discovery constrained to in-stock inventory rows visible across approved storefront feeds.
     *
     * @param string $query Free-text needle submitted via reader search forms (titles/authors/ISBN tokens).
     * @param int    $limit Maximum rows capped between internal min/max guardrails for performance parity with legacy behavior.
     * @return list<array<string, mixed>> Matching catalog hits carrying formatted currency helpers identical to featured listings.
     */
    public function searchListedBooks(string $query, int $limit = 40): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $like = '%' . $query . '%';
        $limit = max(1, min(80, $limit));

        $sql = "SELECT b.book_id, b.title, b.author, b.cover_image, b.isbn, MIN(i.price) AS price
                FROM books b
                INNER JOIN store_inventory i ON b.book_id = i.book_id
                                WHERE (i.quantity - COALESCE(i.hold_quantity, 0)) > 0
                AND (
                    b.title LIKE :like1 OR b.author LIKE :like2 OR b.isbn LIKE :like3
                )
                GROUP BY b.book_id, b.title, b.author, b.cover_image, b.isbn
                ORDER BY b.title ASC
                LIMIT " . (int) $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':like1', $like, PDO::PARAM_STR);
        $stmt->bindValue(':like2', $like, PDO::PARAM_STR);
        $stmt->bindValue(':like3', $like, PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $priceValue = (float) $row['price'];
            $row['price_value'] = $priceValue;
            $row['price_egp_formatted'] = 'EGP ' . number_format($priceValue, 2);
        }
        unset($row);

        return $rows;
    }

    /**
     * Retrieves stable bibliographic metadata for a single catalog identifier without joining inventory pricing tables.
     *
     * @param int $bookId Primary key `books.book_id` supplied by routing/query strings across storefront flows.
     * @return array<string, mixed>|null Associative row or null when identifier unknown/disabled.
     */
    public function getBookById(int $bookId): ?array
    {
        if ($bookId <= 0) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT book_id, isbn, title, author, cover_image
            FROM books
            WHERE book_id = :id
            LIMIT 1"
        );
        $stmt->execute(array('id' => $bookId));
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Normalizes ISBN-ish identifiers (strip punctuation, uppercase checksum) before attempting tolerant equality matching.
     *
     * @param string $isbn Reader-entered ISBN text potentially containing dashes/spaces unlike canonical DB storage.
     * @return array<string, mixed>|null Matching catalog row when exactly one tolerant hit resolves; otherwise null.
     */
    public function findBookByIsbn(string $isbn): ?array
    {
        $raw = trim($isbn);
        if ($raw === '') {
            return null;
        }

        $norm = strtoupper(preg_replace('/[^0-9X]/', '', $raw));
        if ($norm === '') {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT book_id, isbn, title, author, cover_image
            FROM books
            WHERE isbn = :raw
                OR REPLACE(REPLACE(UPPER(isbn), '-', ''), ' ', '') = :norm
            LIMIT 1"
        );
        $stmt->execute(array('raw' => $raw, 'norm' => $norm));
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Approved stores offering this book with available quantity (excluding active holds subtracted implicitly via quantity).
     *
     * @param int        $bookId     Catalog identifier tying inventory rows to storefront availability widgets.
     * @param float|null $readerLat  Optional signed latitude enabling Haversine sorting when reader shares approximate geo consent.
     * @param float|null $readerLng  Optional signed longitude complementing `$readerLat` for proximity-first sorting passes.
     * @return list<array<string,mixed>> Inventory-aware rows describing price/quantity plus optional distance metrics for UI badges.
     */
    public function findStoresWithStockForBook(int $bookId, ?float $readerLat = null, ?float $readerLng = null): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.store_id, s.name, s.address, s.region, s.latitude, s.longitude,
                    i.inventory_id, i.`condition`, i.price, i.quantity, i.hold_quantity
            FROM store_inventory i
            INNER JOIN stores s ON s.store_id = i.store_id
            WHERE i.book_id = :book_id
                            AND (i.quantity - COALESCE(i.hold_quantity, 0)) > 0
            AND s.status = 'Approved'"
        );
        $stmt->execute(array('book_id' => $bookId));
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['price_value'] = (float) $row['price'];
            $row['price_egp_formatted'] = 'EGP ' . number_format((float) $row['price'], 2);
            $avail = max(0, (int) $row['quantity'] - (int) $row['hold_quantity']);
            $row['available_to_buy'] = $avail;
            $row['distance_km'] = null;
            $lat = $row['latitude'] !== null ? (float) $row['latitude'] : null;
            $lng = $row['longitude'] !== null ? (float) $row['longitude'] : null;
            if ($readerLat !== null && $readerLng !== null && $lat !== null && $lng !== null) {
                $row['distance_km'] = self::haversineKm($readerLat, $readerLng, $lat, $lng);
            }
        }
        unset($row);

        if ($readerLat !== null && $readerLng !== null) {
            usort(
                $rows,
                static function (array $a, array $b): int {
                    $da = $a['distance_km'];
                    $db = $b['distance_km'];
                    if ($da === null && $db === null) {
                        return strcmp((string) $a['name'], (string) $b['name']);
                    }
                    if ($da === null) {
                        return 1;
                    }
                    if ($db === null) {
                        return -1;
                    }
                    return $da <=> $db;
                }
            );
        } else {
            usort(
                $rows,
                static function (array $a, array $b): int {
                    $p = ($a['price_value'] ?? 0) <=> ($b['price_value'] ?? 0);
                    return $p !== 0 ? $p : strcmp((string) $a['name'], (string) $b['name']);
                }
            );
        }

        return $rows;
    }

    /**
     * Computes approximate great-circle distance between two WGS84 coordinate pairs expressed in decimal degrees.
     *
     * @param float $lat1 First point latitude (degrees).
     * @param float $lon1 First point longitude (degrees).
     * @param float $lat2 Second point latitude (degrees).
     * @param float $lon2 Second point longitude (degrees).
     * @return float Distance in kilometers used only for relative sorting (not legal surveying precision).
     */
    private static function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371.0;
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $dphi = deg2rad($lat2 - $lat1);
        $dlambda = deg2rad($lon2 - $lon1);
        $a = sin($dphi / 2) * sin($dphi / 2)
            + cos($phi1) * cos($phi2) * sin($dlambda / 2) * sin($dlambda / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $r * $c;
    }
}
