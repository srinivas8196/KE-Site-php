# Plan: Dynamic Destinations & Resorts Pages

**Overall Goal:** Create a user flow where users can browse destinations, filter/search them, select one, and then see a list of resorts specific to that destination (including a short description), finally clicking through to the individual resort's page.

**Phase 1: Destinations Listing Page (`our-destinations.php`)**

1.  **Data Fetching (PHP):**
    *   Include `db.php`.
    *   Fetch all destinations from the `destinations` table: `id`, `destination_name`, `banner_image`.
    *   SQL: `SELECT id, destination_name, banner_image FROM destinations ORDER BY destination_name ASC`
2.  **HTML Structure:**
    *   Add an input field for searching (`id="destinationSearch"`).
    *   Loop through fetched destinations.
    *   For each destination, create a "card" element:
        *   Display `banner_image` (path: `assets/destinations/`).
        *   Display `destination_name`.
        *   Wrap the card in an anchor tag (`<a>`) linking to `resorts-by-destination.php?dest_id={id}`.
3.  **Styling (CSS):**
    *   Apply modern and creative styling to cards and layout (e.g., in `assets/css/custom.css`).
4.  **Client-Side Filtering/Search (JavaScript):**
    *   Implement live search functionality (e.g., in `assets/js/custom.js`) to filter cards based on `destination_name`.

**Phase 2: Resorts by Destination Page (New File: `resorts-by-destination.php`)**

1.  **Data Fetching (PHP):**
    *   Include `kheader.php` and `db.php`.
    *   Get `dest_id` from URL query parameter (`$_GET['dest_id']`). Validate it.
    *   Fetch the destination name: `SELECT destination_name FROM destinations WHERE id = ?`.
    *   Fetch active resorts for this destination: `resort_name`, `resort_slug`, `file_path`, `banner_image`, `resort_description`.
    *   SQL: `SELECT resort_name, resort_slug, file_path, banner_image, resort_description FROM resorts WHERE destination_id = ? AND is_active = 1 ORDER BY resort_name ASC`.
2.  **HTML Structure:**
    *   Display the fetched `destination_name` as a heading.
    *   Create a container for resort cards.
    *   Loop through fetched resorts.
    *   For each resort, create a "card" element:
        *   Display `banner_image` thumbnail (path: `assets/resorts/{resort_slug}/{banner_image}`).
        *   Display `resort_name`.
        *   Display a short version of `resort_description` (potentially truncated).
        *   Wrap the card in an anchor tag (`<a>`) linking to the resort's `file_path`.
3.  **Styling (CSS):**
    *   Apply CSS consistent with the destinations page.
4.  **Footer:**
    *   Include `kfooter.php`.

**Mermaid Diagram of the Flow:**

```mermaid
graph TD
    A[User visits our-destinations.php] --> B{Fetch All Destinations};
    B --> C[Display Destination Cards (Name, Banner)];
    C --> D{User Filters/Searches (JS)};
    D --> E[Show Matching Destination Cards];
    E --> F{User Clicks a Destination Card};
    F -- Passes dest_id --> G[Navigate to resorts-by-destination.php];
    G --> H{Fetch Destination Name (using dest_id)};
    G --> I{Fetch Active Resorts for dest_id (incl. description)};
    H --> J[Display Destination Name Header];
    I --> K[Display Resort Cards (Name, Thumbnail, Short Description)];
    K --> L{User Clicks a Resort Card};
    L -- Uses file_path --> M[Navigate to specific resort PHP page (e.g., karma-kandara.php)];
    M --> N[Display Full Resort Details];

    subgraph "our-destinations.php"
        B
        C
        D
        E
        F
    end

    subgraph "resorts-by-destination.php (New)"
        G
        H
        I
        J
        K
        L
    end

    subgraph "Individual Resort Page (*.php - Existing/Generated)"
        M
        N
    end

    style F fill:#ccf,stroke:#333,stroke-width:2px;
    style L fill:#ccf,stroke:#333,stroke-width:2px;