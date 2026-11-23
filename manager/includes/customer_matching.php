<?php
// iss/manager/includes/customer_matching.php
// Functions for fuzzy matching customer names and phone numbers.

/**
 * Normalizes a phone number by removing common non-digit characters and country codes.
 *
 * @param string $phone The raw phone number string.
 * @return string The normalized phone number string.
 */
function normalizePhoneNumber(string $phone): string {
    // Remove common separators and spaces
    $normalized = preg_replace('/[\s\-()+]/', '', $phone);
    // Remove potential country codes (simple examples, might need refinement)
    if (strpos($normalized, '216') === 0 && strlen($normalized) > 8) { // Tunisian code +216
        $normalized = substr($normalized, 3);
    } elseif (strpos($normalized, '00216') === 0 && strlen($normalized) > 8) { // Tunisian code 00216
         $normalized = substr($normalized, 5);
    }
    // Trim leading zeros if any remain after country code removal (common for local numbers)
    $normalized = ltrim($normalized, '0');
    return $normalized;
}

/**
 * Compares two names using similar_text and returns true if similarity percentage meets threshold.
 * Case-insensitive comparison.
 *
 * @param string $name1 First name.
 * @param string $name2 Second name.
 * @param int $threshold Similarity percentage threshold (0-100). Default is 75.
 * @return bool True if names are considered similar, false otherwise.
 */
function compareNames(string $name1, string $name2, int $threshold = 75): bool {
    if (empty($name1) || empty($name2)) {
        return false;
    }
    $name1Lower = strtolower(trim($name1));
    $name2Lower = strtolower(trim($name2));

    if ($name1Lower === $name2Lower) {
        return true; // Exact match
    }

    similar_text($name1Lower, $name2Lower, $percent);
    return $percent >= $threshold;
}

/**
 * Compares two normalized phone numbers using Levenshtein distance.
 *
 * @param string $phone1 First normalized phone number.
 * @param string $phone2 Second normalized phone number.
 * @param int $maxDistance Maximum allowed Levenshtein distance. Default is 2.
 * @return bool True if phone numbers are considered similar, false otherwise.
 */
function comparePhoneNumbers(string $phone1, string $phone2, int $maxDistance = 2): bool {
    if (empty($phone1) || empty($phone2)) {
        return false;
    }
    // Ensure we are comparing normalized numbers
    $normPhone1 = normalizePhoneNumber($phone1);
    $normPhone2 = normalizePhoneNumber($phone2);

     if ($normPhone1 === $normPhone2) {
        return true; // Exact match after normalization
    }

    // Use Levenshtein distance for similarity (small distance means similar)
    $distance = levenshtein($normPhone1, $normPhone2);
    return $distance <= $maxDistance;
}


/**
 * Groups transactions by likely customer based on fuzzy name and phone matching.
 *
 * @param array $transactions An array of transaction records, each containing at least
 * 'id', 'customer_name', 'customer_phone', 'total_amount', 'created_at'.
 * @return array An array of customer groups. Each group has 'representative_name',
 * 'representative_phone', 'transactions' (array of original transaction records),
 * 'total_purchases', 'total_spent', 'first_purchase_date', 'last_purchase_date'.
 */
function groupCustomers(array $transactions): array {
    $customerGroups = [];

    foreach ($transactions as $transaction) {
        $foundGroup = false;
        $currentName = $transaction['customer_name'];
        $currentPhone = $transaction['customer_phone'];

        // Skip transactions with invalid/placeholder names/phones
        if (empty($currentName) || empty($currentPhone) || $currentName === 'Card Payment at Machine') {
            continue;
        }

        // Normalize current phone for comparison
        $normalizedCurrentPhone = normalizePhoneNumber($currentPhone);

        // Try to find a matching existing group
        foreach ($customerGroups as $groupIndex => $group) {
            $groupName = $group['representative_name'];
            $groupPhone = $group['representative_phone']; // Use the stored representative phone for comparison
            $normalizedGroupPhone = normalizePhoneNumber($groupPhone); // Normalize group phone

            // Matching criteria: Similar name AND similar phone number
            if (compareNames($currentName, $groupName) && comparePhoneNumbers($normalizedCurrentPhone, $normalizedGroupPhone)) {
                // Add transaction to this group
                $customerGroups[$groupIndex]['transactions'][] = $transaction;
                $foundGroup = true;
                break; // Stop searching once a group is found
            }
        }

        // If no matching group was found, create a new one
        if (!$foundGroup) {
            $customerGroups[] = [
                'representative_name' => $currentName,
                'representative_phone' => $currentPhone, // Store the original phone as representative
                'transactions' => [$transaction], // Start with the current transaction
            ];
        }
    }

    // Calculate statistics for each group
    foreach ($customerGroups as $groupIndex => $group) {
        $totalSpent = 0;
        $firstPurchaseDate = null;
        $lastPurchaseDate = null;

        foreach ($group['transactions'] as $tx) {
            $totalSpent += (float)$tx['total_amount'];
            $txDate = strtotime($tx['created_at']);

            if ($firstPurchaseDate === null || $txDate < $firstPurchaseDate) {
                $firstPurchaseDate = $txDate;
            }
            if ($lastPurchaseDate === null || $txDate > $lastPurchaseDate) {
                $lastPurchaseDate = $txDate;
            }
        }

        $customerGroups[$groupIndex]['total_purchases'] = count($group['transactions']);
        $customerGroups[$groupIndex]['total_spent'] = $totalSpent;
        $customerGroups[$groupIndex]['first_purchase_date'] = $firstPurchaseDate ? date('Y-m-d H:i', $firstPurchaseDate) : 'N/A';
        $customerGroups[$groupIndex]['last_purchase_date'] = $lastPurchaseDate ? date('Y-m-d H:i', $lastPurchaseDate) : 'N/A';
    }

    // Sort groups by total spent (descending) or number of purchases
     usort($customerGroups, function($a, $b) {
        // Primary sort: number of purchases (desc)
        if ($b['total_purchases'] != $a['total_purchases']) {
            return $b['total_purchases'] <=> $a['total_purchases'];
        }
        // Secondary sort: total spent (desc)
        return $b['total_spent'] <=> $a['total_spent'];
    });


    return $customerGroups;
}

?>
