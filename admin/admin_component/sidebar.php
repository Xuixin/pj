<?php
// sidebar.php - Include this file in your pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Configuration - You can modify these variables or pass them from the including page
$site_name = isset($site_name) ? $site_name : "ระบบเก็บข้อมูลเครื่องเช่า บริษัท เอมิโปร จำกัด";
$current_page = isset($current_page) ? $current_page : basename($_SERVER['PHP_SELF']);
$user_name = isset($user_name) ? $user_name : "John Doe";
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : "";

$isAdmin = $user_role === 'admin';



// Sidebar menu items - Modify as needed
$sidebar_items = [
    [
        'label' => 'แดชบอร์ด',
        'url' => '/pj/admin/dashboard.php',
        'icon' => 'dashboard',
    ],
    [
        'label' => 'อุปกรณ์',
        'url' => '/pj/admin/device.php',
        'icon' => 'products',
    ],
    [
        'label' => 'พนักงาน',
        'url' => '/pj/admin/admin.php',
        'icon' => 'admin-icon',
    ],
    [
        'label' => 'ผู้เช่า',
        'url' => '/pj/admin/user.php',
        'icon' => 'user-icon',

    ],
    [
        'label' => 'การเช่า',
        'url' => '/pj/admin/rent.php',
        'icon' => 'rent',
    ]


];

if ($user_role !== 'admin') {
    $sidebar_items = array_values(array_filter($sidebar_items, function ($item) {
        return !in_array($item['label'], ['แดชบอร์ด', 'พนักงาน'], true);
    }));
}


// Function to check if current page is active
function isActive($url, $current_page)
{
    if ($url === '/' && ($current_page === 'index.php' || $current_page === '/')) {
        return true;
    }
    return $current_page === basename($url);
}

// Function to check if parent has active child
function hasActiveChild($children, $current_page)
{
    foreach ($children as $child) {
        if (isActive($child['url'], $current_page)) {
            return true;
        }
    }
    return false;
}

// Function to get SVG icon
function getSvgIcon($icon)
{
    $icons = [
        'dashboard' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path></svg>',
        'admin-icon' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a3 3 0 11-6 0 3 3 0 016 0zM13 9a3 3 0 11-6 0 3 3 0 016 0zM6 13a5 5 0 00-5 5v1h10v-1a5 5 0 00-5-5zM14 14a4 4 0 014 4v1h-8v-1a4 4 0 014-4z"></path></svg>',
        'rent' => '<i class="fas fa-file-contract"></i>',
        'user-icon' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path></svg>',
        'products' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 640 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M128 32C92.7 32 64 60.7 64 96l0 256 64 0 0-256 384 0 0 256 64 0 0-256c0-35.3-28.7-64-64-64L128 32zM19.2 384C8.6 384 0 392.6 0 403.2C0 445.6 34.4 480 76.8 480l486.4 0c42.4 0 76.8-34.4 76.8-76.8c0-10.6-8.6-19.2-19.2-19.2L19.2 384z"/></svg>',
        'orders' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path></svg>',
        'analytics' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path></svg>',
        'settings' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path></svg>'
    ];

    return isset($icons[$icon]) ? $icons[$icon] : '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>';
}

// Function to render sidebar item
function renderSidebarItem($item, $current_page)
{
    $item_id = strtolower(str_replace(' ', '-', $item['label']));

    if (isset($item['children'])) {
        // Dropdown item
        $has_active = hasActiveChild($item['children'], $current_page);
        $active_class = $has_active ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white';

        echo '<div class="space-y-1">';
        echo '<button type="button" class="' . $active_class . ' group w-full flex items-center pl-2 pr-1 py-2 text-left text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" onclick="toggleSidebarDropdown(\'' . $item_id . '\')">';
        echo getSvgIcon($item['icon']);
        echo '<span class="ml-3 flex-1">' . htmlspecialchars($item['label']) . '</span>';
        echo '<svg class="ml-3 h-5 w-5 transform transition-colors duration-150 ease-in-out group-hover:text-gray-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 111.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>';
        echo '</button>';

        echo '<div class="space-y-1" id="' . $item_id . '" style="display: ' . ($has_active ? 'block' : 'none') . ';">';
        foreach ($item['children'] as $child) {
            $child_active = isActive($child['url'], $current_page);
            $child_class = $child_active ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white';

            echo '<a href="' . htmlspecialchars($child['url']) . '" class="' . $child_class . ' group w-full flex items-center pl-11 pr-2 py-2 text-sm font-medium rounded-md">';
            echo htmlspecialchars($child['label']);
            echo '</a>';
        }
        echo '</div>';
        echo '</div>';
    } else {
        // Regular item
        $active_class = isActive($item['url'], $current_page) ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white';

        echo '<a href="' . htmlspecialchars($item['url']) . '" class="' . $active_class . ' group flex items-center px-2 py-2 text-sm font-medium rounded-md">';
        echo getSvgIcon($item['icon']);
        echo '<span class="ml-3">' . htmlspecialchars($item['label']) . '</span>';

        if (isset($item['badge'])) {
            echo '<span class="ml-auto inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">';
            echo htmlspecialchars($item['badge']);
            echo '</span>';
        }

        echo '</a>';
    }
}
?>

<?php
if (!$_SESSION['admin_id']) {
    header('Location: login.php');
    exit;
}
?>
<!-- Sidebar -->
<div class="flex h-full  fixed  bg-gray-100">
    <!-- Sidebar -->
    <div class="hidden md:flex md:w-64 md:flex-col md:rounded-2xl lg: rounded-2xl">
        <div class="flex flex-col flex-grow pt-5 overflow-y-auto bg-gray-900  py-3">
            <!-- Logo/Brand -->
            <div class="flex items-center flex-shrink-0 px-4 mb-5">
                <h1 class="text-xl font-bold text-white"><?php echo htmlspecialchars($site_name); ?></h1>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-2 pb-4 space-y-1">
                <?php foreach ($sidebar_items as $item): ?>
                    <?php renderSidebarItem($item, $current_page); ?>
                <?php endforeach; ?>
            </nav>

            <div class="px-2 mt-auto">
                <?php
                renderSidebarItem([
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M497 273L329 441c-15 15-41 4.5-41-17v-96H152c-13.3 0-24-10.7-24-24v-96c0-13.3 10.7-24 24-24h136V88c0-21.4 25.9-32 41-17l168 168c9.3 9.4 9.3 24.6 0 34zM192 436v-40c0-6.6-5.4-12-12-12H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h84c6.6 0 12-5.4 12-12V76c0-6.6-5.4-12-12-12H96c-53 0-96 43-96 96v192c0 53 43 96 96 96h84c6.6 0 12-5.4 12-12z"/></svg>',
                    'label' => 'ออกจากระบบ (' . htmlspecialchars($_SESSION['admin_name']) . ')',
                    'url' => 'logout.php'
                ], $current_page);
                ?>
            </div>


        </div>
    </div>

    <!-- Mobile sidebar -->
    <div class="md:hidden">
        <div class="fixed inset-0 z-40 flex" id="mobile-sidebar" style="display: none;">
            <div class="fixed inset-0 bg-gray-600 bg-opacity-75" onclick="toggleMobileSidebar()"></div>
            <div class="relative flex flex-col flex-1 w-full max-w-xs bg-gray-900">
                <div class="absolute top-0 right-0 p-1 -mr-12">
                    <button class="flex items-center justify-center w-10 h-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" onclick="toggleMobileSidebar()">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="flex-1 h-0 pt-5 pb-4 overflow-y-auto">
                    <div class="flex items-center flex-shrink-0 px-4 mb-5">
                        <h1 class="text-xl font-bold text-white"><?php echo htmlspecialchars($site_name); ?></h1>
                    </div>

                    <nav class="px-2 space-y-1">
                        <?php foreach ($sidebar_items as $item): ?>
                            <?php renderSidebarItem($item, $current_page); ?>
                        <?php endforeach; ?>
                    </nav>
                </div>


            </div>
        </div>
    </div>

    <!-- Mobile header -->
    <div class="md:hidden bg-white shadow-sm border-b border-gray-200 px-4 py-3 flex items-center justify-between">
        <h1 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($site_name); ?></h1>
        <button class="text-gray-500 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500" onclick="toggleMobileSidebar()">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
    </div>


</div>

<script>
    // Sidebar dropdown functionality
    function toggleSidebarDropdown(itemId) {
        const dropdown = document.getElementById(itemId);
        const button = dropdown.previousElementSibling;
        const icon = button.querySelector('svg:last-child');

        if (dropdown.style.display === 'none' || dropdown.style.display === '') {
            dropdown.style.display = 'block';
            icon.style.transform = 'rotate(90deg)';
        } else {
            dropdown.style.display = 'none';
            icon.style.transform = 'rotate(0deg)';
        }
    }

    // Mobile sidebar toggle
    function toggleMobileSidebar() {
        const sidebar = document.getElementById('mobile-sidebar');
        if (sidebar.style.display === 'none' || sidebar.style.display === '') {
            sidebar.style.display = 'flex';
        } else {
            sidebar.style.display = 'none';
        }
    }

    // User menu toggle
    function toggleUserMenu() {
        const menu = document.getElementById('user-menu');
        menu.classList.toggle('hidden');
    }

    // Close user menu when clicking outside
    document.addEventListener('click', function(event) {
        const userMenu = document.getElementById('user-menu');
        const userButton = userMenu.previousElementSibling.querySelector('button');

        if (!userButton.contains(event.target) && !userMenu.contains(event.target)) {
            userMenu.classList.add('hidden');
        }
    });
</script>