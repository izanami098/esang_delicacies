<?php
session_start();
require_once 'config/database.php';

// Simple authentication check (you should implement proper admin authentication)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // For demo purposes, let's set admin session
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = 'admin';
}

// Fetch feedback statistics
try {
    $stats_query = "
        SELECT 
            AVG(delivery_rating) as avg_delivery,
            AVG(taste_rating) as avg_taste,
            AVG(food_quality_rating) as avg_food_quality,
            AVG(service_rating) as avg_service,
            AVG(overall_rating) as avg_overall,
            COUNT(*) as total_reviews,
            COUNT(CASE WHEN overall_rating >= 4 THEN 1 END) as good_reviews,
            COUNT(CASE WHEN overall_rating < 3 THEN 1 END) as poor_reviews
        FROM feedback 
        WHERE status = 'active'
    ";
    
    $stats_stmt = $pdo->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fetch recent feedback
    $recent_query = "
        SELECT 
            f.*,
            c.name as customer_name,
            o.order_date,
            o.total_amount
        FROM feedback f
        JOIN customer c ON f.customer_id = c.customerId
        JOIN orders o ON f.order_id = o.order_id
        WHERE f.status = 'active'
        ORDER BY f.created_at DESC
        LIMIT 10
    ";
    
    $recent_stmt = $pdo->prepare($recent_query);
    $recent_stmt->execute();
    $recent_feedback = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Dashboard - Esang Delicacies Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, #ffc107, #ff6b35);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 16px;
        }
        
        .main-content {
            padding: 30px 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-left: 4px solid;
        }
        
        .stat-card.delivery { border-left-color: #007bff; }
        .stat-card.taste { border-left-color: #28a745; }
        .stat-card.quality { border-left-color: #ffc107; }
        .stat-card.service { border-left-color: #dc3545; }
        .stat-card.overall { border-left-color: #6f42c1; }
        .stat-card.summary { border-left-color: #fd7e14; }
        
        .stat-card h3 {
            color: #6c757d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-card .subtext {
            font-size: 14px;
            color: #6c757d;
        }
        
        .recent-feedback {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .section-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .section-header h2 {
            color: #495057;
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .section-header p {
            color: #6c757d;
            font-size: 14px;
        }
        
        .feedback-item {
            padding: 25px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .feedback-item:last-child {
            border-bottom: none;
        }
        
        .feedback-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 15px;
            gap: 15px;
        }
        
        .customer-info {
            flex: 1;
        }
        
        .customer-info h4 {
            color: #2c3e50;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .order-info {
            color: #6c757d;
            font-size: 14px;
        }
        
        .rating-summary {
            text-align: right;
            min-width: 120px;
        }
        
        .overall-rating {
            font-size: 24px;
            font-weight: bold;
            color: #ffc107;
            margin-bottom: 5px;
        }
        
        .date {
            font-size: 12px;
            color: #6c757d;
        }
        
        .ratings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .rating-item {
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .rating-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .rating-value {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stars {
            color: #ffc107;
            margin-left: 5px;
        }
        
        .comment {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border-left: 3px solid #007bff;
        }
        
        .comment-text {
            color: #495057;
            font-style: italic;
            line-height: 1.5;
        }
        
        .no-data {
            text-align: center;
            padding: 50px;
            color: #6c757d;
        }
        
        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .feedback-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .rating-summary {
                text-align: left;
            }
            
            .ratings-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><i class="fas fa-star"></i> Feedback Dashboard</h1>
            <p>Customer feedback and ratings overview for Esang Delicacies</p>
        </div>
    </div>
    
    <div class="main-content">
        <div class="container">
            <?php if (isset($error)): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($stats) && $stats['total_reviews'] > 0): ?>
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card delivery">
                        <h3>Delivery Rating</h3>
                        <div class="value"><?php echo number_format($stats['avg_delivery'], 1); ?> <span class="stars">★</span></div>
                        <div class="subtext">Average delivery satisfaction</div>
                    </div>
                    
                    <div class="stat-card taste">
                        <h3>Taste Rating</h3>
                        <div class="value"><?php echo number_format($stats['avg_taste'], 1); ?> <span class="stars">★</span></div>
                        <div class="subtext">Food taste satisfaction</div>
                    </div>
                    
                    <div class="stat-card quality">
                        <h3>Food Quality</h3>
                        <div class="value"><?php echo number_format($stats['avg_food_quality'], 1); ?> <span class="stars">★</span></div>
                        <div class="subtext">Food quality rating</div>
                    </div>
                    
                    <div class="stat-card service">
                        <h3>Service Rating</h3>
                        <div class="value"><?php echo number_format($stats['avg_service'], 1); ?> <span class="stars">★</span></div>
                        <div class="subtext">Overall service quality</div>
                    </div>
                    
                    <div class="stat-card overall">
                        <h3>Overall Rating</h3>
                        <div class="value"><?php echo number_format($stats['avg_overall'], 1); ?> <span class="stars">★</span></div>
                        <div class="subtext">Combined rating average</div>
                    </div>
                    
                    <div class="stat-card summary">
                        <h3>Review Summary</h3>
                        <div class="value"><?php echo $stats['total_reviews']; ?></div>
                        <div class="subtext">
                            <?php echo $stats['good_reviews']; ?> positive, 
                            <?php echo $stats['poor_reviews']; ?> need attention
                        </div>
                    </div>
                </div>
                
                <!-- Recent Feedback -->
                <div class="recent-feedback">
                    <div class="section-header">
                        <h2>Recent Customer Feedback</h2>
                        <p>Latest reviews and ratings from customers</p>
                    </div>
                    
                    <?php if (!empty($recent_feedback)): ?>
                        <?php foreach ($recent_feedback as $feedback): ?>
                            <div class="feedback-item">
                                <div class="feedback-header">
                                    <div class="customer-info">
                                        <h4><?php echo htmlspecialchars($feedback['customer_name']); ?></h4>
                                        <div class="order-info">
                                            Order #<?php echo $feedback['order_id']; ?> • 
                                            ₱<?php echo number_format($feedback['total_amount'], 2); ?> • 
                                            <?php echo date('M j, Y', strtotime($feedback['order_date'])); ?>
                                        </div>
                                    </div>
                                    <div class="rating-summary">
                                        <div class="overall-rating">
                                            <?php echo $feedback['overall_rating']; ?> <span class="stars">★</span>
                                        </div>
                                        <div class="date"><?php echo date('M j, Y g:i A', strtotime($feedback['created_at'])); ?></div>
                                    </div>
                                </div>
                                
                                <div class="ratings-grid">
                                    <div class="rating-item">
                                        <div class="rating-label">Delivery</div>
                                        <div class="rating-value"><?php echo $feedback['delivery_rating']; ?> <span class="stars">★</span></div>
                                    </div>
                                    <div class="rating-item">
                                        <div class="rating-label">Taste</div>
                                        <div class="rating-value"><?php echo $feedback['taste_rating']; ?> <span class="stars">★</span></div>
                                    </div>
                                    <div class="rating-item">
                                        <div class="rating-label">Quality</div>
                                        <div class="rating-value"><?php echo $feedback['food_quality_rating']; ?> <span class="stars">★</span></div>
                                    </div>
                                    <div class="rating-item">
                                        <div class="rating-label">Service</div>
                                        <div class="rating-value"><?php echo $feedback['service_rating']; ?> <span class="stars">★</span></div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($feedback['comment'])): ?>
                                    <div class="comment">
                                        <div class="comment-text">
                                            "<?php echo htmlspecialchars($feedback['comment']); ?>"
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-comments"></i>
                            <h3>No Recent Feedback</h3>
                            <p>No feedback data available at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-star"></i>
                    <h2>No Feedback Data</h2>
                    <p>No customer feedback has been submitted yet. Once customers start rating delivered orders, their feedback will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Auto-refresh the page every 30 seconds to show new feedback
        setTimeout(() => {
            location.reload();
        }, 30000);
        
        console.log('Feedback Dashboard loaded successfully');
    </script>
</body>
</html>