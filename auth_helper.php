<?php
/**
 * Role-based Authentication Helper Functions
 */

/**
 * Check if user has permission to access a specific feature
 * 
 * @param string $required_role Minimum role required (super_admin, admin, campaign_manager)
 * @return bool True if user has permission, false otherwise
 */
function hasPermission($required_role) {
    if (!isset($_SESSION["user_id"])) {
        return false;
    }
    
    $user_role = $_SESSION["user_type"] ?? "";
    
    // Role hierarchy
    switch ($required_role) {
        case "campaign_manager":
            return in_array($user_role, ["super_admin", "admin", "campaign_manager"]);
            
        case "admin":
            return in_array($user_role, ["super_admin", "admin"]);
            
        case "super_admin":
            return $user_role === "super_admin";
            
        default:
            return false;
    }
}

/**
 * Redirect user if they don't have the required permission
 * 
 * @param string $required_role Minimum role required
 * @param string $redirect_url URL to redirect to if permission is denied
 */
function requirePermission($required_role, $redirect_url = "login.php") {
    if (!hasPermission($required_role)) {
        $_SESSION["error_message"] = "You don't have permission to access this page.";
        header("Location: " . $redirect_url);
        exit;
    }
}

/**
 * Check if functionality should be displayed based on user role
 * 
 * @param string $required_role Minimum role required
 * @return bool True if functionality should be shown, false otherwise
 */
function showForRole($required_role) {
    return hasPermission($required_role);
}
