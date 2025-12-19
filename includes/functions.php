// ... inside includes/functions.php (Append this block) ...

/**
 * Helper function to generate a table row for a company.
 */
if (!function_exists('generate_company_row')) {
    function generate_company_row($name, $industry, $owner, $deals, $activity) {
        return '
            <tr class="hover:bg-gray-50 transition duration-150">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600 hover:text-blue-800 cursor-pointer">' . htmlspecialchars($name) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($industry) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($owner) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-semibold">' . htmlspecialchars($deals) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($activity) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <a href="#" class="text-blue-600 hover:text-blue-900 ml-2">View</a>
                </td>
            </tr>
        ';
    }
}