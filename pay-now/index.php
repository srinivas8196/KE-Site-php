<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Add cache control headers to prevent caching
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    // Include database connection
    require_once '../db.php';
    $pdo = require '../db.php';

    // Define base URL
    $base_url = '/KE-Site-php';

    // Function to get all destinations with their active resorts - ONLY for mega menu display
    function getDestinationsForMenu() {
        global $pdo;
        
        $sql = "SELECT d.id as dest_id, d.destination_name, 
                       r.id as resort_id, r.resort_name, r.resort_slug, r.is_active, r.is_partner
                FROM destinations d
                LEFT JOIN resorts r ON d.id = r.destination_id
                WHERE r.id IS NOT NULL
                AND r.is_active = 1
                ORDER BY d.destination_name, r.resort_name";
        
        try {
            $stmt = $pdo->query($sql);
            $menuDestinations = [];
            
            while($row = $stmt->fetch()) {
                $destId = $row['dest_id'];
                
                if (!isset($menuDestinations[$destId])) {
                    $menuDestinations[$destId] = [
                        'id' => $destId,
                        'name' => $row['destination_name'],
                        'resorts' => []
                    ];
                }
                
                $menuDestinations[$destId]['resorts'][] = [
                    'id' => $row['resort_id'],
                    'name' => $row['resort_name'],
                    'slug' => $row['resort_slug'],
                    'is_partner' => $row['is_partner']
                ];
            }
            
            // Only return destinations that have active resorts
            $menuDestinations = array_filter($menuDestinations, function($destination) {
                return !empty($destination['resorts']);
            });
            
            return array_values($menuDestinations);
        } catch (PDOException $e) {
            error_log("Database query failed: " . $e->getMessage());
            return [];
        }
    }

    // Get current page URL to identify active resorts
    function getCurrentMenuSlug() {
        $uri = $_SERVER['REQUEST_URI'];
        $path = parse_url($uri, PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));
        $lastSegment = end($segments);
        
        if ($lastSegment === 'resorts.php' && isset($_GET['slug'])) {
            return $_GET['slug'];
        }
        
        if (preg_match('/^([a-z0-9-]+)\.php$/', $lastSegment, $matches)) {
            return $matches[1];
        }
        
        return $lastSegment;
    }

    // Initialize variables for the mega menu
    $menuCurrentSlug = getCurrentMenuSlug();
    $menuDestinations = getDestinationsForMenu();
    ?>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--<link rel="shortcut icon" href="https://www.karmaexperience.com/wp-content/themes/experience/images/karmaexperience-com.ico">-->
    <title>Secure Your Payment - Karma Experience</title>

    <link rel="shortcut icon" href="upload/TG-Thumb.png" />
    <title>Secure Your Payment - Karma Experience</title>
    <link rel='stylesheet' href='css/settings.css' type='text/css' media='all' />
    <link rel='stylesheet' href='css/reset.css' type='text/css' media='all' />
    <link rel='stylesheet' href='css/wordpress.css' type='text/css' media='all' />
    <link rel='stylesheet' href='css/animation.css' type='text/css' media='all' />
    <link rel='stylesheet' href='css/magnific-popup.css' type='text/css' media='all' />
    <link rel='stylesheet' href='css/ui-custom.css' type='text/css' media='all' />
    <link rel='stylesheet' href='css/mediaelementplayer-legacy.min.css' type='text/css' media='all' />
    <link rel='stylesheet' href='css/flexslider.css' type='text/css' media='all' />
    <link rel='stylesheet' href='css/tooltipster.css' type='text/css' media='all' />
    <link rel='stylesheet' href='css/odometer-theme-minimal.css' type='text/css' media='all' />
    <link rel='stylesheet' href='css/hw-parallax.css' type='text/css' media='all' />
    <link rel='stylesheet' href='css/screen.css' type='text/css' media='all' />
    <!--<link rel='stylesheet' href='css/font-awesome.min.css' type='text/css' media='all' />-->
    <link rel='stylesheet' href='css/kirki-styles.css' type='text/css' media='all' />
    <link rel='stylesheet' href='css/grid.css' type='text/css' media='all' />
    <link rel='stylesheet' href='css/datepicker.css' type='text/css' media='all' />
    <link rel='stylesheet' href='css/style.css' type='text/css' media='all' />
    <link rel="stylesheet" href="assets/int-tel-input/css/intlTelInput.css">
    <link rel="stylesheet" href="../customasset/css/ui.css?dev2">

    <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Playfair+Display%3A400%2C700%2C400italic&#038;subset=latin%2Ccyrillic-ext%2Cgreek-ext%2Ccyrillic' type='text/css' media='all' />
    <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Lato%3A100%2C200%2C300%2C400%2C600%2C700%2C800%2C900%2C400italic&#038;subset=latin%2Ccyrillic-ext%2Cgreek-ext%2Ccyrillic' type='text/css' media='all' />
    <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Kristi%3A100%2C200%2C300%2C400%2C600%2C700%2C800%2C900%2C400italic&#038;subset=latin%2Ccyrillic-ext%2Cgreek-ext%2Ccyrillic' type='text/css' media='all' />
    <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Lato%7CKristi&#038;subset' type='text/css' media='all' />
    <link href="http://fonts.googleapis.com/css?family=Kristi:400%7CLato:300%2C400" rel="stylesheet" property="stylesheet" type="text/css" media="all">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        .tg-inline-select select[disabled] {
            padding-bottom: 20px;
        }

        /* Style for tab content */
        .tab-content {
            display: none;
        }

        /* Style for active tab */
        .active {
            display: block;
        }

        /* Style for the tab navigation */
        .tab-navigation {
            margin-bottom: 10px;
        }

        .dropdown {
            display: inline-block;
        }

        .search-container {
            display: inline-block;
            margin-left: 10px;
        }

        .search-input {
            padding: 5px;
        }

        .custom-select {
            position: relative;
            display: inline-block;
        }

        #locality-select {
            padding: 10px;
            /* Adjust padding as needed */
            width: 200px;
            /* Adjust width as needed */
        }

        .select-icon {
            position: absolute;
            top: 50%;
            right: 10px;
            /* Adjust the right offset as needed */
            transform: translateY(-50%);
            pointer-events: none;
        }

        .intl-tel-input {
            padding-left: 80px;
            position: relative;
            display: inline-block;
        }
    </style>

    <style>
        .navbar-dark .navbar-nav a.nav-link {
            color: #1f2124;
        }

        .navbar-dark .navbar-nav a.nav-link:hover {
            color: #8E7855;
        }

        .navbar-dark .navbar-nav .nav-item .nav-link {
            /* content: "|"; */
            margin-right: 20px;
        }

        .navbar-dark .navbar-nav a.nav-link:active {
            color: #8E7855;
        }


        .dropdown-menu {
            width: fit-content;
            left: 0%;
            transform: translateX(-53%);
            position: absolute;
            top: 100%;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16), 0 3px 6px rgba(0, 0, 0, 0.23);
            border: none;
            border-radius: 10px;
            padding: 0.7em;
            z-index: 1050;
        }

        .paynow-mob {
            display: none;
        }

        .paynow-desk {
            display: inline;
        }

        button {
            background-color: #8B734B;
            font-family: "Roboto", Sans-serif;
            font-weight: 600;
            border-radius: 25px 25px 25px 25px;
            display: inline-block;
            line-height: 1;
            padding: 12px 24px;
        }

        button a {
            text-decoration: none;
            color: #fff;
        }

        #copyright{
            font-size: 16px;
        }


        @media (max-width: 768px) {
            .dropdown-menu {
                width: auto;
                left: 0%;
                transform: translateX(0%);
            }

            .paynow-mob {
                display: inline;
            }

            .paynow-desk {
                display: none;
            }

            button {
                background-color: #8B734B;
                font-family: "Roboto", Sans-serif;
                font-weight: 600;
                border-radius: 25px 25px 25px 25px;
                display: inline-block;
                line-height: 1;
                padding: 8px 16px;
            }

            button a {
                text-decoration: none;
                color: #fff;
            }

            .navbar-dark .navbar-nav .nav-item:not(:first-child) .nav-link::before {
                content: " ";
                margin-right: 0px;
            }
        }

        @media only screen and (min-width: 992px) {
            .dropdown:hover .dropdown-menu {
                display: flex;
                margin-top: 5px;
            }

            .dropdown-menu.show {
                display: flex;
            }
        }

        .navbar-nav {
            margin-right: 50px;
            font-family: "Roboto", Sans-serif;
            font-weight: 600;
            font-size: 18px;
        }

        .dropdown-menu ul {
            list-style: none;
            padding: 0;
            width: 25%;
        }

        .dropdown-menu li .dropdown-item {
            color: gray;
            font-size: 0.9em;
            padding: 0.2em 1em;
        }

        .dropdown-menu li .dropdown-item:hover {
            background-color: #f1f1f100;
        }

        .dropdown-head {
            font-weight: bold;
            padding: 0.2em 0.8em;
            font-size: 0.8em;
            text-transform: uppercase;
            width: 250px;
            color: #8E7855 !important;
        }

        .dropdown-head:hover {
            background-color: #f1f1f100;
        }

        @media only screen and (max-width: 992px) {
            .dropdown-menu.show {
                flex-wrap: wrap;
                max-height: 350px;
                overflow-y: scroll;
            }

            .navbar-dark .navbar-nav a.nav-link::before {
                content: " ";
                margin-right: 0px;
            }
        }

        @media only screen and (min-width: 992px) and (max-width: 1140px) {
            .dropdown:hover .dropdown-menu {
                width: 40vw;
                flex-wrap: wrap;
            }
        }

        ul {
            padding-left: 0rem !important;
        }

        #footer a {
            text-decoration: none !important;
        }

        #footer_menu li a {
            text-decoration: none !important;
        }

        .time-remain {
            padding-left: 10%;
        }

        .navbar-brand {
            width: 150px !important;
        }

        @media only screen and (max-width: 768px) {
            .time-remain {
                padding-left: 0%;
            }

            .dropdown-menu ul {
                padding-left: 0rem !important;
            }

            ul {
                padding-left: 2rem !important;
            }

            .navbar-brand {
                width: 120px !important;
            }

            .navbar-nav {
                padding-left: 0px !important;
            }
        }
    </style>

    <!-- Add new mega menu styles -->
    <style>
        /* Enhanced Mega Menu Styles */
        .mega-menu {
            position: fixed;
            top: 80px;
            left: 0;
            width: 100%;
            background: #fff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            padding: 40px 0;
            transform: translateY(-10px);
        }

        .menu-item-has-children:hover .mega-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .mega-menu .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .mega-menu .destinations-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }

        .mega-menu .menu-column {
            flex: 1;
            min-width: 250px;
        }

        .mega-menu .menu-column:first-child {
            flex: 0 0 25%;
        }

        .mega-menu .destination-section {
            margin-bottom: 25px;
        }

        .mega-menu .destination-title {
            color: #B4975A;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(180, 151, 90, 0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .mega-menu .resort-count {
            color: #999;
            font-size: 12px;
            font-weight: normal;
            text-transform: none;
        }

        .mega-menu .resort-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .mega-menu .resort-link {
            color: #666;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.2s ease;
            display: block;
            padding: 4px 0;
            line-height: 1.4;
        }

        .mega-menu .resort-link:hover {
            color: #B4975A;
            padding-left: 8px;
        }

        .mega-menu .resort-link .location {
            color: #999;
            font-size: 11px;
            margin-left: 4px;
        }

        .mega-menu .resort-link .partner-label {
            font-size: 12px;
            color: #B4975A;
            font-style: italic;
            margin-left: 6px;
            font-weight: 600;
            display: inline-block;
            background-color: rgba(180, 151, 90, 0.15);
            padding: 2px 8px;
            border-radius: 3px;
            border: 1px solid rgba(180, 151, 90, 0.3);
            text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
        }

        /* Header Styles */
        .th-header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            transition: all 0.3s ease;
            padding: 1rem 0;
        }

        .th-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.3));
            z-index: -1;
            transition: all 0.3s ease;
        }

        .th-header.scrolled::before {
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.9), rgba(0, 0, 0, 0.85));
            backdrop-filter: blur(10px);
        }

        .menu-area {
            position: relative;
            width: 100%;
        }

        .menu-area .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .menu-area .row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: nowrap;
            margin: 0 -15px;
        }

        .col-auto {
            padding: 0 15px;
            display: flex;
            align-items: center;
        }

        /* Logo Styles */
        .header-logo {
            padding: 0 2rem;
            position: relative;
            z-index: 1;
        }

        .header-logo img {
            height: 50px;
            width: auto;
            transition: all 0.3s ease;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        }

        .th-header.scrolled .header-logo img {
            height: 40px;
        }

        /* Navigation Menu Styles */
        .main-menu {
            display: block;
            position: relative;
            z-index: 1;
        }

        .main-menu > ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            align-items: center;
            gap: 2rem;
        }

        .main-menu > ul > li {
            position: relative;
            padding: 0;
            margin: 0;
        }

        .main-menu > ul > li > a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            transition: color 0.3s ease;
            white-space: nowrap;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .main-menu > ul > li > a:hover {
            color: #B4975A;
        }

        /* Button Styles */
        .header-button {
            margin-left: 2rem;
            position: relative;
            z-index: 1;
        }

        .th-btn.style3 {
            background: #B4975A;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .th-btn.style3:hover {
            background: #96793D;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }

        /* Mobile Menu Toggle */
        .th-menu-toggle {
            display: none;
            background: transparent;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0.5rem;
            position: relative;
            z-index: 1;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        /* Responsive Styles */
        @media (max-width: 1400px) {
            .menu-area .container {
                max-width: 1200px;
            }
            
            .main-menu > ul {
                gap: 1.5rem;
            }
        }

        @media (max-width: 1200px) {
            .menu-area .container {
                max-width: 100%;
                padding: 0 30px;
            }
            
            .header-logo {
                padding: 0 1rem;
            }
            
            .main-menu > ul {
                gap: 1rem;
            }
            
            .header-button {
                margin-left: 1rem;
            }
        }

        @media (max-width: 991px) {
            .d-none.d-xl-block {
                display: none !important;
            }
            
            .th-menu-toggle {
                display: block;
            }
            
            .menu-area .row {
                justify-content: space-between;
            }

            .th-header::before {
                background: linear-gradient(to bottom, rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.5));
            }
        }
    </style>

    <!-- Enhanced Header and Navigation Styles -->
    <style>
        .th-header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            background: transparent;
            transition: all 0.3s ease;
            padding: 1rem 0;
        }

        .th-header.scrolled {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
        }

        .menu-area {
            width: 100%;
        }

        .menu-area .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .menu-area .row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: nowrap;
            margin: 0 -15px;
        }

        .col-auto {
            padding: 0 15px;
            display: flex;
            align-items: center;
        }

        /* Logo Styles */
        .header-logo {
            padding: 0 2rem;
        }

        .header-logo img {
            height: 50px;
            width: auto;
            transition: all 0.3s ease;
        }

        .th-header.scrolled .header-logo img {
            height: 40px;
        }

        /* Navigation Menu Styles */
        .main-menu {
            display: block;
        }

        .main-menu > ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            align-items: center;
            gap: 2rem;
        }

        .main-menu > ul > li {
            position: relative;
            padding: 0;
            margin: 0;
        }

        .main-menu > ul > li > a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            transition: color 0.3s ease;
            white-space: nowrap;
        }

        .main-menu > ul > li > a:hover {
            color: #B4975A;
        }

        /* Button Styles */
        .header-button {
            margin-left: 2rem;
        }

        .th-btn.style3 {
            background: #B4975A;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .th-btn.style3:hover {
            background: #96793D;
            transform: translateY(-2px);
        }

        /* Mobile Menu Toggle */
        .th-menu-toggle {
            display: none;
            background: transparent;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0.5rem;
        }

        /* Responsive Styles */
        @media (max-width: 1400px) {
            .menu-area .container {
                max-width: 1200px;
            }
            
            .main-menu > ul {
                gap: 1.5rem;
            }
        }

        @media (max-width: 1200px) {
            .menu-area .container {
                max-width: 100%;
                padding: 0 30px;
            }
            
            .header-logo {
                padding: 0 1rem;
            }
            
            .main-menu > ul {
                gap: 1rem;
            }
            
            .header-button {
                margin-left: 1rem;
            }
        }

        @media (max-width: 991px) {
            .d-none.d-xl-block {
                display: none !important;
            }
            
            .th-menu-toggle {
                display: block;
            }
            
            .menu-area .row {
                justify-content: space-between;
            }
        }
    </style>

    <!-- Enhanced Footer Styles -->
    <style>
    .footer-wrapper {
        background-color: #111111;
        color: #ffffff;
        padding: 80px 0 0;
        position: relative;
    }

    .footer-top {
        padding-bottom: 60px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Logo Styles */
    .footer-logo {
        margin-bottom: 30px;
    }

    .footer-logo img {
        max-width: 220px;
        height: auto;
    }

    /* Title Styles */
    .footer-title {
        color: #B4975A;
        font-size: 20px;
        font-weight: 500;
        margin-bottom: 25px;
        position: relative;
    }

    /* Links Styles */
    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-links li {
        margin-bottom: 15px;
    }

    .footer-links a {
        color: #ffffff;
        text-decoration: none;
        font-size: 15px;
        transition: color 0.3s ease;
        display: inline-block;
    }

    .footer-links a:hover {
        color: #B4975A;
    }

    /* Address Styles */
    .address-info {
        color: #ffffff;
    }

    .address-item {
        margin-bottom: 25px;
    }

    .address-item h4 {
        color: #ffffff;
        font-size: 15px;
        font-weight: 500;
        margin-bottom: 10px;
    }

    .address-item p {
        color: #ffffff;
        font-size: 15px;
        line-height: 1.6;
        margin: 0;
    }

    /* Footer Bottom */
    .footer-bottom {
        background-color: #111111;
        padding: 25px 0;
    }

    .copyright-text {
        color: #ffffff;
        font-size: 14px;
        margin: 0;
    }

    /* Payment Methods */
    .payment-methods {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 20px;
    }

    .payment-methods span {
        color: #ffffff;
        font-size: 14px;
    }

    .payment-icons {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .payment-icons img {
        height: 30px;
        width: auto;
    }

    /* Responsive Styles */
    @media (max-width: 991px) {
        .footer-wrapper {
            padding: 60px 0 0;
        }

        .footer-widget {
            margin-bottom: 40px;
        }

        .payment-methods {
            justify-content: center;
            margin-top: 20px;
        }

        .copyright-text {
            text-align: center;
        }
    }

    @media (max-width: 767px) {
        .footer-bottom .row {
            flex-direction: column;
            text-align: center;
        }

        .payment-methods {
            margin-top: 20px;
            flex-direction: column;
            gap: 15px;
        }

        .copyright-text {
            margin-bottom: 15px;
        }
    }
    </style>
</head>

<body>
    <!-- Header with Mega Menu -->
    <header class="th-header header-layout3">
        <div class="sticky-wrapper">
            <div class="menu-area">
                <div class="container">
                    <div class="row align-items-center justify-content-between">
                        <div class="col-auto">
                            <nav class="main-menu d-none d-xl-block">
                                <ul>
                                    <li><a href="/KE-Site-Php/index.php">Home</a></li>
                                    <li class="menu-item-has-children">
                                        <a href="#">Destinations</a>
                                        <div class="mega-menu">
                                            <div class="container">
                                                <div class="destinations-wrapper">
                                                    <?php
                                                    // Find India and separate it from other destinations
                                                    $indiaDestination = null;
                                                    $otherDestinations = [];
                                                    
                                                    foreach ($menuDestinations as $destination) {
                                                        if (strtoupper(trim($destination['name'])) === 'INDIA') {
                                                            $indiaDestination = $destination;
                                                        } else {
                                                            $otherDestinations[] = $destination;
                                                        }
                                                    }
                                                    
                                                    // If India is not found exactly, try a more flexible approach
                                                    if ($indiaDestination === null) {
                                                        foreach ($menuDestinations as $destination) {
                                                            if (stripos($destination['name'], 'INDIA') !== false) {
                                                                $indiaDestination = $destination;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    
                                                    // Sort other destinations by number of resorts
                                                    usort($otherDestinations, function($a, $b) {
                                                        return count($b['resorts']) - count($a['resorts']);
                                                    });
                                                    ?>
                                                    
                                                    <!-- First Column (India) -->
                                                    <div class="menu-column">
                                                        <?php if ($indiaDestination): 
                                                            $resortCount = count($indiaDestination['resorts']);
                                                        ?>
                                                            <div class="destination-section india">
                                                                <h3 class="destination-title">
                                                                    <?php echo htmlspecialchars($indiaDestination['name']); ?>
                                                                    <span class="resort-count">(<?php echo $resortCount; ?> <?php echo $resortCount === 1 ? 'Resort' : 'Resorts'; ?>)</span>
                                                                </h3>
                                                                <div class="resort-list">
                                                                    <?php foreach ($indiaDestination['resorts'] as $resort): 
                                                                        $resortName = $resort['name'];
                                                                        $isPartner = !empty($resort['is_partner']);
                                                                        
                                                                        // Extract location if exists
                                                                        $location = '';
                                                                        if (strpos($resortName, ',') !== false) {
                                                                            list($resortName, $location) = explode(',', $resortName, 2);
                                                                        }
                                                                    ?>
                                                                        <a href="<?php echo $base_url; ?>/<?php echo htmlspecialchars($resort['slug']); ?>.php" 
                                                                           class="resort-link <?php echo ($resort['slug'] === $menuCurrentSlug) ? 'active' : ''; ?>">
                                                                            <?php 
                                                                                echo htmlspecialchars(trim($resortName));
                                                                                if ($isPartner) {
                                                                                    echo '<span class="partner-label">(Partner Hotel)</span>';
                                                                                }
                                                                                if ($location) {
                                                                                    echo '<span class="location">' . htmlspecialchars(trim($location)) . '</span>';
                                                                                }
                                                                            ?>
                                                                        </a>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <?php
                                                    // Calculate destinations per column for other 3 columns
                                                    $customColumns = [[], [], []];
                                                    $colIndex = 0;
                                                    $colHeight = [0, 0, 0];
                                                    
                                                    foreach ($otherDestinations as $destination) {
                                                        $resortCount = count($destination['resorts']);
                                                        $weight = $resortCount * 0.5 + 1;
                                                        
                                                        // Find column with least height
                                                        $minHeight = min($colHeight);
                                                        $colIndex = array_search($minHeight, $colHeight);
                                                        
                                                        $customColumns[$colIndex][] = $destination;
                                                        $colHeight[$colIndex] += $weight;
                                                    }
                                                    
                                                    // Create other columns
                                                    for ($i = 0; $i < 3; $i++):
                                                        if (empty($customColumns[$i])) continue;
                                                    ?>
                                                        <div class="menu-column">
                                                            <?php foreach ($customColumns[$i] as $destination): 
                                                                $resortCount = count($destination['resorts']);
                                                            ?>
                                                                <div class="destination-section">
                                                                    <h3 class="destination-title">
                                                                        <?php echo htmlspecialchars($destination['name']); ?>
                                                                        <span class="resort-count">(<?php echo $resortCount; ?> <?php echo $resortCount === 1 ? 'Resort' : 'Resorts'; ?>)</span>
                                                                    </h3>
                                                                    <div class="resort-list">
                                                                        <?php foreach ($destination['resorts'] as $resort): 
                                                                            $resortName = $resort['name'];
                                                                            $isPartner = !empty($resort['is_partner']);
                                                                            
                                                                            $location = '';
                                                                            if (strpos($resortName, ',') !== false) {
                                                                                list($resortName, $location) = explode(',', $resortName, 2);
                                                                            }
                                                                        ?>
                                                                            <a href="<?php echo $base_url; ?>/<?php echo htmlspecialchars($resort['slug']); ?>.php" 
                                                                               class="resort-link <?php echo ($resort['slug'] === $menuCurrentSlug) ? 'active' : ''; ?>">
                                                                                <?php 
                                                                                    echo htmlspecialchars(trim($resortName));
                                                                                    if ($isPartner) {
                                                                                        echo '<span class="partner-label">(Partner Hotel)</span>';
                                                                                    }
                                                                                    if ($location) {
                                                                                        echo '<span class="location">' . htmlspecialchars(trim($location)) . '</span>';
                                                                                    }
                                                                                ?>
                                                                            </a>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                            </div>
                        </li>
                                    <li><a href="/KE-Site-Php/about.php">About Us</a></li>
                    </ul>
                            </nav>
                </div>
                        
                        <div class="col-auto">
                            <div class="header-logo">
                                <a href="index.php">
                                    <img src="../assets/images/logo/KE-white.png" alt="Karma Experience">
                                </a>
            </div>
                        </div>
                        
                        <div class="col-auto">
                            <nav class="main-menu d-none d-xl-block">
                                <ul>
                                    <li><a href="/KE-Site-Php/Blogs.php">Our Blogs</a></li>
                                    <li><a href="/KE-Site-Php/enquire-now.php">Enquire Now</a></li>
                                </ul>
        </nav>
                            <button type="button" class="th-menu-toggle d-block d-xl-none">
                                <i class="fas fa-bars"></i>
                            </button>
    </div>

                        <div class="col-auto d-none d-xl-block">
                            <div class="header-button">
                                <a href="/KE-Site-Php/pay-now.php" class="th-btn style3">Pay Now</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Begin template wrapper -->
    <div id="wrapper" class="hasbg">



        <div class="ppb_wrapper bg-white">
            <div class="one fullwidth">
                <div id="page_caption" class="hasbg parallax baseline ">
                    <div class="parallax_overlay_header"></div>
                    <div id="bg_regular" style="background-image:url(images/Pay-now.jpg);"></div>

                    <div class="page_title_wrapper baseline" data-stellar-ratio="1.3">
                        <div class="page_title_inner baseline">
                            <h1 class="withtopbar">
                                Pay Now
                            </h1>
                        </div>


                    </div>
                </div>
                <br><br>

                <div class="mb-12 p-6 mx-auto max-w-xl bg-white rounded-lg shadow-md">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

                        <!-- Points section -->
                        <div class="col-span-12">
                            <div class="text-gray-700 text-sm mb-4">
                                <p style="font-size: larger; font-weight:500;">Please use this page to make payments.
                                    Pay attention to the important things below:</p> <br>
                                <ol class="list-decimal pl-5">
                                    <li>Email address must be clear to receive the confirmation email from us</li>
                                    <li>Fill in the amount without periods or commas</li>
                                    <li>The payment methods that can be selected are credit and debit cards</li>
                                    <!-- <li>🥘 10% discount on Food & Beverage at Karma Group Resorts.</li>
                                    <li>💆 10% discount on wellness treatments at award-winning Karma Spa Resorts.</li>
                                    <li>🎫 Privileged access to Karma Club events including free entry and substantial discounts</li>
                                    <li>🛍 Special discounts on Karma Group's boutique merchandise collection.
                                    </li> -->
                                    <li>📰 Access to the Karma Community – the latest news, special offers and savings from across the Karmaverse.</li>
                                </ol>
                            </div>
                        </div>

                        <!-- Payment Form Text -->
                        <div class="col-span-12 lg:col-span-7">
                            <h2 class="text-lg font-bold mb-4">Payment Form</h2>

                        </div>

                        <!-- Timer section -->
                        <div class="col-span-12 lg:col-span-5 text-right time-remain">
                            <div class="bg-red-500 text-white font-bold p-2 rounded-lg">
                                Time Remaining <span id="time" class="font-bold"></span>
                            </div>
                        </div>

                        <!-- Your form fields go here with Tailwind CSS classes -->

                        <div class="col-span-12">
                            <select name="currency" id="currency" required class="w-full px-3 py-2 border rounded">
                                <option value="" disabled selected>Which currency would you like to pay?</option>
                                <option value="AUD">AUS Dollar</option>
                                <option value="EUR">EURO</option>
                                <option value="GBP">Great Britain Pound</option>
                                <option value="india">Indian Rupees </option>
                                <option value="IDR">Indonesian Rupiah</option>
                                <option value="USD">US Dollars</option>
                            </select>
                        </div>

                        <div class="col-span-12" id="otherCountriesForm" style="display: none;">
                            <form method="POST" name="otherCountryForm" id="otherCountryForm" action="process_payment.php">


                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                                    <div class="col-span-12 lg:col-span-6">
                                        <input type="text" id="first-name" name="first-name" placeholder="First name" required class="w-full px-3 py-2 border rounded">
                                    </div>

                                    <div class="col-span-12 lg:col-span-6">
                                        <input type="text" id="last-name" name="last-name" placeholder="Last name" required class="w-full px-3 py-2 border rounded">
                                    </div>

                                    <div class="col-span-12 lg:col-span-6">
                                        <input type="tel" name="phonefront" id="phone" placeholder="Phone" required class="w-full px-3 py-2 border rounded">
                                        <input type="hidden" id="phonevalue" name="SingleLine">
                                        <input type="hidden" name="PhoneFormatLsq2" id="PhoneFormatLsq2">

                                    </div>

                                    <div class="col-span-12 lg:col-span-6">
                                        <input type="email" id="E-mail" name="E-mail" placeholder="Email" required class="w-full px-3 py-2 border rounded">
                                    </div>

                                    <div class="col-span-12">
                                        <h2 class="text-lg font-bold mb-4">Payment Details</h2>
                                    </div>

                                    <!-- <div class="col-span-12 lg:col-span-6">
                                        <select name="selectcurrency" id="selectcurrency" required class="w-full px-3 py-2 border rounded">
                                            <option value="" disabled selected>Select currency</option>
                                            <option value="aud">AUD</option>
                                            <option value="eur">EURO</option>
                                            <option value="usd">USD</option>
                                            <option value="gbp">GBP</option>
                                        </select>
                                    </div> -->

                                    <div class="col-span-12 lg:col-span-6">
                                        <input type="number" name="amount" id="amount" placeholder="Amount" required class="w-full px-3 py-2 border rounded">

                                        <!-- <input type="hidden" id="paypal-transaction-id" name="paypal-transaction-id"> -->

                                    </div>

                                    <div class="col-span-12 flex items-center space-x-2 mb-4">
                                        <input type="checkbox" id="consent" name="consent" required class="h-5 w-5">
                                        <label for="consent" class="text-sm">
                                            By submitting my information, I acknowledge and accept all <a target="_blank" href="https://karmaexperience.com/terms-and-conditions-apply/">terms and conditions</a> .
                                        </label>
                                    </div>

                                    <div class="col-span-12">


                                        <button name="pay-now-btn" id="pay-now-btn" class="text-white px-4 py-2 rounded-full" style="background-color:#8d734b">Pay Now</button>

                                    </div>


                                </div>
                            </form>
                        </div>

                        <div class="col-span-12" id="indonesiaForm" style="display: none;">

                            <form method="POST" name="indonesiaForm" id="indonesiaForm" action="test-payment.php">


                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                                    <div class="col-span-12 lg:col-span-6">
                                        <input type="text" name="name_first" placeholder="First name" required class="w-full px-3 py-2 border rounded">
                                    </div>

                                    <div class="col-span-12 lg:col-span-6">
                                        <input type="text" name="name_last" placeholder="Last name" required class="w-full px-3 py-2 border rounded">
                                    </div>

                                    <div class="col-span-12 lg:col-span-6">
                                        <input type="tel" name="phonefront" id="phone2" placeholder="Phone" required class="w-full px-3 py-2 border rounded">
                                        <input type="hidden" id="phonevalue" name="SingleLine">

                                    </div>

                                    <div class="col-span-12 lg:col-span-6">
                                        <input type="email" name="email" placeholder="Email" required class="w-full px-3 py-2 border rounded">
                                    </div>

                                    <div class="col-span-12">
                                        <h2 class="text-lg font-bold mb-4">Payment Details</h2>
                                    </div>
                                    <!-- 
                                    <div class="col-span-12 lg:col-span-6">
                                        <select name="selectcurrency" id="selectcurrency" required class="w-full px-3 py-2 border rounded">
                                            <option value="" disabled selected>Select currency</option>
                                            <option value="idr">IDR</option>
                                        </select>
                                    </div> -->

                                    <div class="col-span-12">
                                        <input type="number" name="price" id="price" placeholder="Amount" required class="w-full px-3 py-2 border rounded">

                                        <input type="hidden" id="faspay_payment_id" name="faspay_payment_id" value="">


                                    </div>

                                    <div class="col-span-12">

                                        <button type="submit" name="submit" class="text-white px-4 py-2 rounded-full" style="background-color:#8d734b">Submit Now</button>
                                        <br>
                                        <div id="progress" class="text-center"></div>
                                        <div id="errorPlacement" class="text-center"></div>
                                    </div>
                                </div>
                            </form>
                        </div>


                        <!-- For India -->

                        <div class="col-span-12" id="indianForm" style="display: none;">
                            <!-- Form for Indian -->
                            <form method="POST" name="indiaForm" id="indiaForm" action="process-payment.php">
                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                                    <div class="col-span-12 lg:col-span-6">
                                        <input type="text" name="name_first" id="fname" placeholder="First name" required class="w-full px-3 py-2 border rounded">
                                    </div>

                                    <div class="col-span-12 lg:col-span-6">
                                        <input type="text" name="name_last" id="lname" placeholder="Last name" required class="w-full px-3 py-2 border rounded">
                                    </div>

                                    <div class="col-span-12 lg:col-span-6">
                                        <input type="tel" name="phonefront" id="phone3" placeholder="Phone" required class="w-full px-3 py-2 border rounded" min="10" max="10">
                                        <input type="hidden" id="phonevalue" name="SingleLine">
                                        <input type="hidden" name="PhoneFormatLsq" id="PhoneFormatLsq">
                                    </div>

                                    <div class="col-span-12 lg:col-span-6">
                                        <input type="email" name="E_mail" id="E_mail" placeholder="Email" required class="w-full px-3 py-2 border rounded">
                                    </div>

                                    <div class="col-span-12">
                                        <h2 class="text-lg font-bold mb-4">Payment Details</h2>
                                    </div>

                                    <div class="col-span-12">
                                        <input type="number" name="pricing" id="pricing" placeholder="Amount" required class="w-full px-3 py-2 border rounded">
                                        <input type="hidden" id="razorpay_payment_id" name="razorpay_payment_id" value="">
                                    </div>

                                    <div class="col-span-12 flex items-center space-x-2 mb-4">
                                        <input type="checkbox" id="consent" name="consent" required class="h-5 w-5">
                                        <label for="consent" class="text-sm">
                                            By submitting my information, I acknowledge and accept all <a target="_blank" href="https://karmaexperience.com/terms-and-conditions-apply/">terms and conditions</a>.
                                        </label>
                                    </div>

                                    <div class="col-span-12">
                                        <button name="pay-now-button" id="pay-now-button" class="text-white px-4 py-2 rounded-full" style="background-color:#8d734b">Pay Now</button>
                                        <br>
                                    </div>
                                </div>
                            </form>


                        </div>
                    </div>
                </div>



                <!-- Footer -->
                <footer class="footer-wrapper footer-layout1">
                    <div class="container">
                        <div class="footer-top">
                            <div class="row justify-content-between">
                                <div class="col-md-6 col-xl-3">
                                    <div class="footer-widget">
                                        <div class="footer-logo">
                                            <a href="index.php">
                                                <img src="../assets/images/logo/KE-white.png" alt="Karma Experience" class="img-fluid">
                                            </a>
                                </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-auto">
                                    <div class="footer-widget">
                                        <h3 class="footer-title">Quick Links</h3>
                                        <ul class="footer-links">
                                            <li><a href="/KE-Site-Php/index.php">Home</a></li>
                                            <li><a href="/KE-Site-Php/about.php">About us</a></li>
                                            <li><a href="/KE-Site-Php/our-destinations.php">Destinations</a></li>
                        </ul>
                    </div>
                                </div>
                                <div class="col-md-6 col-xl-auto">
                                    <div class="footer-widget">
                                        <h3 class="footer-title">Other Links</h3>
                                        <ul class="footer-links">
                                            <!-- <li><a href="gallery.php">Gallery</a></li> -->
                                            <li><a href="/KE-Site-Php/terms-and-conditions.php">Terms & conditions</a></li>
                                            <li><a href="/KE-Site-Php/privacy-policy.php">Privacy Policy</a></li>
                                            <li><a href="/KE-Site-Php/Blogs.php">Blogs</a></li>
                            </ul>
                        </div>
                        </div>
                                <div class="col-md-6 col-xl-auto">
                                    <div class="footer-widget">
                                        <h3 class="footer-title">Address</h3>
                                        <div class="address-info">
                                            <div class="address-item">
                                                <h4>Central Reservation</h4>
                                                <p>Bengaluru</p>
                    </div>
                                            <div class="address-item">
                                                <h4>Regional Offices</h4>
                                                <p>Philippines | United Kingdom | Germany | Bali | Goa</p>
                </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="footer-bottom">
                        <div class="container">
                            <div class="row justify-content-between align-items-center">
                                <div class="col-lg-6">
                                    <p class="copyright-text">© 2025 - PRESTIGE HOLIDAY RESORTS LLP - ALL RIGHTS RESERVED</p>
                                </div>
                                <div class="col-lg-6">
                                    <div class="payment-methods">
                                        <span>We Accept:</span>
                                        <div class="payment-icons">
                                            <img src="../assets/img/icon/visa.webp" alt="visa">
                                            <img src="../assets/img/icon/master-card.webp" alt="mastercard">
                                            <img src="../assets/img/icon/razorpay.png" alt="razorpay">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
            <div id="overlay_background"></div>
        </div>
    </div>
    </div>




    <!-- <div class="footer">
        <div class="container">
            <center>
                <div class="row">
                    <div class="col-lg-12 text-center">
                        <img src="https://karmaexperience.com/assets/images/logo-gold.png" class="img-fluid img-center">
                    </div>
                    <div class="col-lg-12">
                        <ul>
                            <li><a href="https://www.facebook.com/experiencekarma" target="_blank"><i class="fa fa-facebook"></i></a></li>
                            <li><a href="https://www.instagram.com/karmaexperience/?hl=en" target="_blank"><i class="fa fa-instagram"></i></a></li>
                        </ul>
                    </div>
                    <div class="col-lg-12">
                       <p class="text-center"><a href="https://www.karmaexperience.com/privacy-policy/" target="_blank">Privacy Policy</a> | <a href="https://www.karmaexperience.com/terms-and-conditions-apply/">Terms & Conditions</a> | Cancellation / Refund Policy: Offer is non transferable and non refundable.</p>
                    </div>
                    <br>
                    <p>&copy; <?php echo date('Y'); ?> PRESTIGE HOLIDAY RESORTS LLP - ALL RIGHTS RESERVED <br> Karma Experience - The Holiday Preview Divison Of Prestige Holiday Resorts LLP</p>

                </div>
            </center>
        </div>
    </div> -->


    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/bootstrap5.0/js/bootstrap.min.js"></script>
    <script src="assets/js/owl.carousel.min.js"></script>
    <script src="assets/jqueryui/jquery-ui.min.js"></script>
    <script src="assets/int-tel-input/js/intlTelInput.min.js"></script>
    <script src="assets/js/addfunc.js"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script src="https://checkout.flywire.com/flywire-payment.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <!-- <script src="../customasset/js/addscript.js"></script> -->

    <script>
        // Handle currency form switching
        document.getElementById('currency').addEventListener('change', function() {
            const selectedCurrency = this.value;

            // Hide all forms by default
            document.getElementById('indonesiaForm').style.display = 'none';
            document.getElementById('otherCountriesForm').style.display = 'none';
            document.getElementById('indianForm').style.display = 'none';

            // Show specific form based on selected currency
            if (selectedCurrency === 'indonesia') {
                document.getElementById('indonesiaForm').style.display = 'block';
            } else if (['USD', 'AUD', 'IDR', 'SGD', 'GBP', 'EUR'].includes(selectedCurrency)) {
                document.getElementById('otherCountriesForm').style.display = 'block';
            } else if (selectedCurrency === 'india') {
                document.getElementById('indianForm').style.display = 'block';
            }
        });

        document.getElementById('pay-now-btn').addEventListener('click', function(event) {
            event.preventDefault(); // Prevent the default form submission

            // Retrieve form values
            const firstName = document.getElementById('first-name').value;
            const lastName = document.getElementById('last-name').value;
            const phone = document.getElementById('PhoneFormatLsq2').value;
            const email = document.getElementById('E-mail').value;
            const amount = document.getElementById('amount').value;
            const currency = document.getElementById('currency') ? document.getElementById('currency').value : '';
            const consent = document.getElementById('consent').checked;

            // Validate required fields
            if (!firstName || !lastName || !phone || !email || !amount) {
                alert('Please fill in all required fields.');
                return;
            }

            // Validate consent checkbox
            if (!consent) {
                alert('You must acknowledge and accept the terms and conditions before proceeding.');
                return;
            }

            // Check if a valid currency is selected
            if (!currency) {
                alert('Please select a valid currency.');
                return;
            }

            // Determine the recipient code based on selected currency
            let recipientCode, callbackUrl;

            switch (currency) {
                case "AUD":
                    recipientCode = "OFT";
                    callbackUrl = "https://karmaexperience.com/pay-now/flywire-webhook-aud.php";
                    break;

                case "SGD":
                    recipientCode = "KWT";
                    callbackUrl = "https://karmaexperience.com/pay-now/flywire-webhook-sgd.php";
                    break;

                case "GBP":
                    recipientCode = "OQT";
                    callbackUrl = "https://karmaexperience.com/pay-now/flywire-webhook-gbp.php";
                    break;

                case "USD":
                    recipientCode = "KQT";
                    callbackUrl = "https://karmaexperience.com/pay-now/flywire-webhook-usd.php";
                    break;

                case "IDR":
                    recipientCode = "KXT";
                    callbackUrl = "https://karmaexperience.com/pay-now/flywire-webhook-idr.php";
                    break;

                case "EUR":
                    recipientCode = "KZZ";
                    callbackUrl = "https://karmaexperience.com/pay-now/flywire-webhook-euro.php";
                    break;

                default:
                    alert('Unsupported currency selected. Please select a valid currency.');
                    return;
            }

            // Generate a unique transaction ID
            const transactionId = `TXN${Date.now()}`;

            // Construct the return URL
            const returnUrl = `https://karmaexperience.com/pay-now/thank-you.php?status=pending&reference=${transactionId}&email=${email}&name=${firstName}%20${lastName}&amount=${amount}&currency=${currency}`;

            // Flywire payment configuration
            const config = {
                env: "prod",
                recipientCode: recipientCode,
                amount: parseFloat(amount),
                currency: currency.toUpperCase(),
                firstName: firstName,
                lastName: lastName,
                email: email,
                phone: phone,
                recipientFields: {
                    booking_reference: "KPREF1234",
                    web_source: "karmaexperience.com"
                },
                requestPayerInfo: true,
                requestRecipientInfo: false,
                returnUrl: returnUrl,
                callbackUrl: callbackUrl,
                callbackId: "KPREF1234",
                callbackVersion: "2",
                onInvalidInput: function(errors) {
                    errors.forEach(function(error) {
                        alert(error.msg);
                    });
                },
            };

            // Check if Flywire module is loaded
            if (typeof window.FlywirePayment === "undefined") {
                alert("Flywire payment module is not loaded. Please try again later.");
                return;
            }

            // Initiate Flywire modal
            const modal = window.FlywirePayment.initiate(config);
            if (!modal) {
                alert("Failed to initiate payment modal. Please try again.");
                return;
            }
            modal.render();
        });
    </script>



    <script>
        //window.addEventListener('load', (event) => {
        //    $("#myModal_1").modal("toggle");
        //})

        $("#lang").on('change', function() {
            var url = $(this).val();
            if (url != "") {
                parent.location = url;
                return false;
            }
        })
        setphoneByID("#phone", "us");
        $('.datepicker').datepicker({
            dateFormat: "dd-M-yy",
            changeYear: true,
            yearRange: "-100:+0"
        })

        setphoneByID("#phone2", "id");
        $('.datepicker').datepicker({
            dateFormat: "dd-M-yy",
            changeYear: true,
            yearRange: "-100:+0"
        })
        setphoneByID("#phone3", "in");
        $('.datepicker').datepicker({
            dateFormat: "dd-M-yy",
            changeYear: true,
            yearRange: "-100:+0"
        })
        $('.datefwd').datepicker({
            dateFormat: "dd-M-yy",
            changeYear: true,
            minDate: 0
        })


        $("select[name=number_children]").on('change', function() {
            var ar = [];
            $(".wrapumur").empty();

            var a = $(this).val();
            var ar = [];

            if (a != 0) {
                for (let i = 1; i <= a; i++) {
                    ar.push("<input type='number' name='umur" + i + "' placeholder='Children age " + i + "'>");
                }
                $(".wrapumur").append(ar);
            }

        });

        $('#formmain').on('submit', function(e) {
            $("#progress").addClass("text-success")("Process...");
            $("input[type=submit]").prop('disabled', true);

        })
    </script>

    <script>
        function disableButton() {
            $("form input, form select").prop("disabled", true);
        }

        function startTimer(duration, display) {
            var timer = duration,
                minutes, seconds;
            setInterval(function() {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);
                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                display.textContent = minutes + ":" + seconds;

                if (--timer < 0) {
                    display.textContent = "Form Expired!";
                    disableButton()
                }
            }, 1000);
        }

        window.onload = function() {
            var fiveMinutes = 900;
            display = document.querySelector('#time');
            startTimer(fiveMinutes, display);
        };
    </script>

    <script>
        $(document).ready(function() {
            $('#indiaForm').on('submit', function(event) {
                event.preventDefault(); // Prevent default form submission
                var form = this;

                // Fetch form inputs
                var enteredAmount = $('#pricing').val();
                var phone = $('#phone3').val();

                // Validate phone number
                if (!/^\d{10}$/.test(phone)) {
                    alert('Please enter a valid 10-digit phone number.');
                    return;
                }

                // Validate entered amount
                if (!enteredAmount || isNaN(enteredAmount) || enteredAmount <= 0) {
                    alert('Please enter a valid amount.');
                    return;
                }

                // Send data to the backend for order creation
                $.ajax({
                    url: 'process-payment.php',
                    method: 'POST',
                    data: $(form).serialize(), // Serialize the form data
                    success: function(response) {
                        if (response.error) {
                            alert(response.error); // Display error message from backend
                            return;
                        }

                        // Configure Razorpay payment options
                        var options = {
                            key: "rzp_live_D5lhKkL7KTKaGs", // Replace with your Razorpay key
                            order_id: response.orderId, // Razorpay order ID
                            amount: enteredAmount * 100, // Convert entered amount to paise
                            currency: "INR",
                            name: "Karma Experience",
                            description: "Payment for Karma Experience",
                            handler: function(paymentResponse) {
                                // Add Razorpay payment ID to the form and submit
                                $('#razorpay_payment_id').val(paymentResponse.razorpay_payment_id);
                                form.submit();
                            },
                            prefill: {
                                name: $('#fname').val() + " " + $('#lname').val(),
                                email: $('#E_mail').val(),
                                contact: phone
                            },
                            theme: {
                                color: "#8d734b" // Custom color matching the button
                            }
                        };

                        // Open Razorpay payment modal
                        var rzp = new Razorpay(options);
                        rzp.open();
                    },
                    error: function() {
                        alert('Unable to create order. Please try again.');
                    }
                });
            });
        });
    </script>


    <script src='js/plugins/custom_plugins.js'></script>
    <script src='js/plugins/custom.js'></script>
    <script src='js/plugins/core.min.js'></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Header scroll effect
            const header = document.querySelector('.th-header');
            const menuToggle = document.querySelector('.th-menu-toggle');
            const mobileMenu = document.querySelector('.th-mobile-menu');
            const mobileMenuClose = document.querySelector('.mobile-menu-close');
            const overlay = document.querySelector('.mobile-menu-overlay');
            const submenuToggles = document.querySelectorAll('.mobile-menu-toggle');
            const headerLogo = document.querySelector('.header-logo img');
            
            // Set initial logo
            headerLogo.setAttribute('src', '../assets/images/logo/KE-white.png');
            
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    header.classList.add('scrolled');
                    headerLogo.setAttribute('src', '../assets/images/logo/KE-white.png');
                } else {
                    header.classList.remove('scrolled');
                    headerLogo.setAttribute('src', '../assets/images/logo/KE-white.png');
                }
            });

            // Function to open mobile menu
            function openMobileMenu() {
                mobileMenu.classList.add('active');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            // Function to close mobile menu
            function closeMobileMenu() {
                mobileMenu.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            // Mobile menu toggle
            if (menuToggle) {
                menuToggle.addEventListener('click', openMobileMenu);
            }
            if (mobileMenuClose) {
                mobileMenuClose.addEventListener('click', closeMobileMenu);
            }
            if (overlay) {
                overlay.addEventListener('click', closeMobileMenu);
            }

            // Handle submenu toggles
            submenuToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const submenu = this.nextElementSibling;
                    const isActive = submenu.classList.contains('active');
                    
                    // Close all other submenus
                    document.querySelectorAll('.mobile-submenu').forEach(sub => {
                        if (sub !== submenu) {
                            sub.classList.remove('active');
                            sub.previousElementSibling.classList.remove('active');
                        }
                    });
                    
                    // Toggle clicked submenu
                    submenu.classList.toggle('active');
                    this.classList.toggle('active');
                });
            });

            // Prevent clicks inside mobile menu from closing it
            if (mobileMenu) {
                mobileMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        });
    </script>

</body>

</html>