<?php
// includes/topbar.php
// Renders the top navigation bar shown above the main content.
// Expects $pageTitle to be set by the page that includes this file.
?>
<header class="topbar">
    <!-- Button to toggle the sidebar open/closed on mobile -->
    <button class="topbar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
        <i class="bi bi-list"></i>
    </button>

    <!-- Current page title in the center/left of the topbar -->
    <div class="topbar-title"><?php echo e(isset($pageTitle) ? $pageTitle : APP_NAME); ?></div>

    <!-- Right side: greeting and quick logout -->
    <div class="topbar-actions ms-auto d-flex align-items-center gap-3">
        <!-- Show a greeting with the user's name (hidden on small screens) -->
        <span class="topbar-greeting d-none d-md-inline">
            Hello, <strong><?php echo e(isset(current_user()['name']) ? current_user()['name'] : ''); ?></strong>
        </span>

        <!-- Quick logout button -->
        <a href="<?php echo BASE_URL; ?>/logout.php" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-power"></i>
        </a>
    </div>
</header>
