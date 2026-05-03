<?php

namespace App\Libraries;

/**
 * ProductSorter
 *
 * Reusable sorting/filtering logic for product arrays.
 * Mirrors the frontend sortProducts() function so both sides behave identically.
 */
class ProductSorter
{
    /**
     * Sort and optionally filter a flat array of product rows.
     *
     * @param array  $products        Raw rows from DB (associative arrays)
     * @param bool   $filterZeroPrice Remove products where price_regular = 0
     * @param string $sortDirection   'newest-first' | 'oldest-first' (by updated_at)
     *
     * @return array Sorted/filtered copy — original is not mutated
     */
    public static function sort(
        array  $products,
        bool   $filterZeroPrice = true,
        string $sortDirection   = 'newest-first'
    ): array {

        
        // Step 1: filter zero-price products
        if ($filterZeroPrice) {
            $products = array_filter(
                $products,
                fn($p) => !empty($p['price_regular']) && (float) $p['price_regular'] !== 0.0
            );
        }

        // Step 2: sort
        usort($products, function ($a, $b) use ($sortDirection) {

            // In-stock first
            $aIn = ($a['stock_status'] ?? '') === 'instock';
            $bIn = ($b['stock_status'] ?? '') === 'instock';

            if ($aIn && !$bIn) return -1;
            if (!$aIn && $bIn) return 1;

            // Same stock status — sort by updated_at
            $aTime = strtotime($a['updated_at'] ?? '') ?: 0;
            $bTime = strtotime($b['updated_at'] ?? '') ?: 0;

            return $sortDirection === 'newest-first'
                ? $bTime - $aTime
                : $aTime - $bTime;
        });

        return array_values($products); // re-index after filter
    }
}
