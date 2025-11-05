<?php

namespace App\Services\Concerns;

use Illuminate\Support\Facades\Log;

/**
 * FirebaseIndexManager Trait
 * 
 * Provides methods for managing Firebase RTDB indexes.
 * Indexes are used for efficient querying in Firebase Realtime Database.
 * 
 * Example structure:
 * /indexes/business/123/calls/456 => { call_data }
 * /indexes/waiter/789/calls/456 => { call_data }
 * 
 * @package App\Services\Concerns
 */
trait FirebaseIndexManager
{
    /**
     * Update an index entry in Firebase
     * 
     * @param string $indexPath Base path for the index (e.g., '/indexes/waiter/123')
     * @param string $itemKey Key for the item (e.g., 'call_456')
     * @param array $itemData Data to store
     * @return bool Success status
     */
    protected function updateIndex(string $indexPath, string $itemKey, array $itemData): bool
    {
        if (!method_exists($this, 'writeToFirebase')) {
            Log::error('FirebaseIndexManager requires FirebaseHttpClient trait');
            return false;
        }

        $fullPath = $indexPath . '/' . $itemKey;
        
        return $this->writeToFirebase($fullPath, $itemData);
    }

    /**
     * Remove an item from an index
     * 
     * @param string $indexPath Base path for the index
     * @param string $itemKey Key for the item to remove
     * @return bool Success status
     */
    protected function removeFromIndex(string $indexPath, string $itemKey): bool
    {
        if (!method_exists($this, 'deleteFromFirebase')) {
            Log::error('FirebaseIndexManager requires FirebaseHttpClient trait');
            return false;
        }

        $fullPath = $indexPath . '/' . $itemKey;
        
        return $this->deleteFromFirebase($fullPath);
    }

    /**
     * Get all items from an index
     * 
     * @param string $indexPath Base path for the index
     * @return array Items in the index (empty array if none)
     */
    protected function getIndexItems(string $indexPath): array
    {
        if (!method_exists($this, 'readFromFirebase')) {
            Log::error('FirebaseIndexManager requires FirebaseHttpClient trait');
            return [];
        }

        $data = $this->readFromFirebase($indexPath);
        
        return is_array($data) ? $data : [];
    }

    /**
     * Clear an entire index (remove all items)
     * 
     * @param string $indexPath Base path for the index
     * @return bool Success status
     */
    protected function clearIndex(string $indexPath): bool
    {
        if (!method_exists($this, 'deleteFromFirebase')) {
            Log::error('FirebaseIndexManager requires FirebaseHttpClient trait');
            return false;
        }

        return $this->deleteFromFirebase($indexPath);
    }

    /**
     * Count items in an index
     * 
     * @param string $indexPath Base path for the index
     * @return int Number of items
     */
    protected function countIndexItems(string $indexPath): int
    {
        $items = $this->getIndexItems($indexPath);
        return count($items);
    }

    /**
     * Check if an item exists in an index
     * 
     * @param string $indexPath Base path for the index
     * @param string $itemKey Key for the item
     * @return bool True if exists
     */
    protected function indexHasItem(string $indexPath, string $itemKey): bool
    {
        $items = $this->getIndexItems($indexPath);
        return isset($items[$itemKey]);
    }
}
