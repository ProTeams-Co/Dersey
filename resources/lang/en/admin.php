<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Panel
    |--------------------------------------------------------------------------
    |
    | The admin panel UI itself is Arabic-only and never renders these EN
    | strings - this file exists solely to satisfy TranslationParityChecker
    | (every ar key needs an en counterpart, per CLAUDE.md §12) and to keep
    | the source strings reviewable in English.
    |
    */

    'auth' => [
        'title' => 'Sign In',
        'email' => 'Email',
        'password' => 'Password',
        'remember_me' => 'Remember me',
        'login_button' => 'Sign in',
        'forgot_password' => 'Forgot your password?',
        'failed' => 'These credentials do not match our records.',
        'suspended' => 'This account is suspended. Contact a system administrator.',
        'throttle' => 'Too many login attempts. Try again in :seconds seconds.',
        'logout' => 'Sign out',

        'forgot_password_title' => 'Reset Password',
        'forgot_password_intro' => 'Enter your email and we will send you a password reset link.',
        'send_reset_link' => 'Send reset link',
        'reset_link_sent' => 'A password reset link has been sent to your email if it is registered with us.',
        'back_to_login' => 'Back to sign in',

        'reset_password_title' => 'Reset Password',
        'new_password' => 'New password',
        'confirm_password' => 'Confirm password',
        'reset_button' => 'Reset password',
        'password_reset' => 'Your password has been reset successfully - sign in with your new password.',
        'invalid_token' => 'This password reset link is invalid or has expired.',
        'invalid_user' => 'No account is registered with that email.',
        'reset_throttled' => 'Please wait before requesting another reset link.',
        'reset_failed' => 'Unable to reset the password. Please try again.',

        'reset_mail_subject' => 'Password Reset - Dersey',
        'reset_mail_greeting' => 'Hello :name,',
        'reset_mail_line' => 'We received a request to reset the password for your Dersey admin account.',
        'reset_mail_action' => 'Reset Password',
        'reset_mail_expire' => 'This link expires in :minutes minutes. If you did not request a password reset, no further action is required.',
    ],

    'dashboard' => [
        'title' => 'Dashboard',
        'stat_orders_today' => "Today's Orders",
        'stat_revenue_today' => "Today's Revenue",
        'stat_low_stock' => 'Low Stock Products',
        'stat_pending_reviews' => 'Pending Reviews',
        'recent_orders' => 'Recent Orders',
        'low_stock_alerts' => 'Low Stock Alerts',
        'sales_chart_title' => 'Sales - Last 7 Days',
        'no_recent_orders' => 'No orders yet.',
        'no_low_stock' => 'No products running low.',
        'view_all' => 'View all',
        'demo_data_notice' => 'Chart data is temporary placeholder data.',
    ],

    'admins' => [
        'title' => 'Admin Users',
        'column_name' => 'Name',
        'column_email' => 'Email',
        'column_status' => 'Status',
        'column_last_login' => 'Last Login',
        'column_created' => 'Created',
        'never_logged_in' => 'Never logged in',
    ],

    'menu' => [
        'dashboard' => 'Dashboard',
        'catalog' => 'Catalog',
        'products' => 'Products',
        'categories' => 'Categories',
        'inventory' => 'Inventory',
        'orders' => 'Orders',
        'customers' => 'Customers',
        'addresses' => 'Addresses',
        'discounts' => 'Discounts',
        'settings' => 'Settings',
        'administration' => 'Administration',
        'admins' => 'Admin Users',
        'roles' => 'Roles & Permissions',
        'coming_soon' => 'Coming soon',
    ],

    'form' => [
        'language_tabs' => 'Content languages',
        'language_incomplete' => 'This language is missing required fields',
        'add_row' => 'Add row',
    ],

    'crud' => [
        'created' => 'Created successfully.',
        'updated' => 'Updated successfully.',
        'deleted' => 'Deleted successfully.',
        'bulk_deleted' => ':count items deleted successfully.',
    ],

    'table' => [
        'search_placeholder' => 'Search...',
        'no_results_title' => 'No results',
        'no_results_description' => 'Try changing your search term or filters.',
        'showing' => 'Showing :from-:to of :total',
        'filters' => 'Filters',
        'apply_filters' => 'Apply',
        'clear_filters' => 'Clear filters',
        'select_all' => 'Select all',
        'selected_count' => ':count selected',
        'bulk_actions' => 'Bulk actions',
        'export' => 'Export CSV',
        'export_queued' => 'This export is large - you will get a download link shortly.',
        'confirm_bulk_action' => 'Are you sure you want to run this action on the selected items?',
        'actions' => 'Actions',
    ],

    'layout' => [
        'collapse_sidebar' => 'Collapse sidebar',
        'expand_sidebar' => 'Expand sidebar',
        'open_menu' => 'Open menu',
        'close_menu' => 'Close menu',
        'search_placeholder' => 'Quick search...',
        'notifications' => 'Notifications',
        'no_notifications' => 'No new notifications.',
        'account' => 'Account',
        'profile' => 'Profile',
        'skip_to_content' => 'Skip to content',
        'breadcrumb_home' => 'Home',
    ],

];
