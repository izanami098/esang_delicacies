<?php
/**
 * Customer Stats API - Profile Hash Authenticated
 */

require_once 'BaseAPI.php';

class StatsAPI extends BaseAPI {
    
    public function handleRequest() {
        try {
            $customerId = $this->customer['customerId'];
            
            // Get order statistics
            $orderStats = $this->getOrderStats($customerId);
            
            // Get notification statistics
            $notificationStats = $this->getNotificationStats($customerId);
            
            // Combine all stats
            $stats = array_merge($orderStats, $notificationStats);
            
            $this->sendSuccess($stats, 'Customer statistics retrieved successfully');
            
        } catch (Exception $e) {
            error_log("Stats API Error: " . $e->getMessage());
            $this->sendError('Failed to retrieve statistics');
        }
    }
    
    private function getOrderStats($customerId) {
        $stmt = $this->database->prepare("
            SELECT 
                COUNT(*) as totalOrders,
                COUNT(CASE WHEN status IN ('pending', 'confirmed', 'preparing') THEN 1 END) as pendingOrders,
                COALESCE(SUM(total_amount), 0) as totalSpent,
                MAX(created_at) as lastOrderDate
            FROM orders 
            WHERE customer_id = ?
        ");
        
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();
        
        return [
            'totalOrders' => (int)$stats['totalOrders'],
            'pendingOrders' => (int)$stats['pendingOrders'],
            'totalSpent' => number_format((float)$stats['totalSpent'], 2),
            'lastOrderDate' => $stats['lastOrderDate']
        ];
    }
    
    private function getNotificationStats($customerId) {
        $stmt = $this->database->prepare("
            SELECT 
                COUNT(*) as totalNotifications,
                COUNT(CASE WHEN is_read = 0 THEN 1 END) as unreadNotifications
            FROM customer_notifications 
            WHERE customer_id = ?
        ");
        
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();
        
        return [
            'totalNotifications' => (int)$stats['totalNotifications'],
            'unreadNotifications' => (int)$stats['unreadNotifications']
        ];
    }
}

// Initialize and handle request
$api = new StatsAPI();
$api->handleRequest();
?>