<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: login.php');
    exit();
}

// Database connection
$db = mysqli_connect('localhost', 'root', '', 'blog');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Initialize variables
$search_query = '';
$users_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $users_per_page;

// Handle search
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = mysqli_real_escape_string($db, $_GET['search']);
}

// Build search query
$where_clause = "";
if (!empty($search_query)) {
    $where_clause = "WHERE username LIKE '%$search_query%'";
}

// Get total users count for pagination
$count_query = "SELECT COUNT(*) as total FROM users $where_clause";
$count_result = mysqli_query($db, $count_query);
$total_users = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_users / $users_per_page);

// Get users with search and pagination
$users_query = "SELECT id, username  FROM users $where_clause ORDER BY username ASC LIMIT $users_per_page OFFSET $offset";
$users_result = mysqli_query($db, $users_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User List</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Arial', sans-serif;
        }
        
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            margin: 20px 0;
            padding: 30px;
        }
        
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .search-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }
        
        .user-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            padding: 25px;
            border: 1px solid #e9ecef;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #667eea;
        }
        
        .user-info h5 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .user-info p {
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .btn-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            transition: transform 0.3s ease;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        
        .pagination .page-link {
            color: #667eea;
            border: 1px solid #dee2e6;
            padding: 10px 15px;
            margin: 0 2px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .pagination .page-link:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        .pagination .active .page-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .navbar-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            margin-bottom: 20px;
            padding: 15px;
        }
        
        .alert-custom {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
        }
        
        .user-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .default-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="main-container">
                    
                    <!-- Navigation -->
                    <nav class="navbar navbar-expand-lg navbar-custom">
                        <div class="container-fluid">
                            <span class="navbar-brand">
                                <i class="fas fa-users"></i> User Management
                            </span>
                            <div class="navbar-nav ms-auto">
                                <a class="nav-link" href="index.php">
                                    <i class="fas fa-home"></i> Home
                                </a>
                                <a class="nav-link" href="posts.php">
                                    <i class="fas fa-blog"></i> Posts
                                </a>
                                <a class="nav-link text-danger" href="index.php?logout=1">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        </div>
                    </nav>

                    <!-- Header Section -->
                    <div class="header-section">
                        <h1><i class="fas fa-users"></i> User Directory</h1>
                        <p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!</p>
                    </div>

                    <!-- Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="stats-card">
                                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                <h4><?php echo $total_users; ?></h4>
                                <p class="text-muted">Total Users</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <i class="fas fa-search fa-2x text-success mb-2"></i>
                                <h4><?php echo !empty($search_query) ? 'Active' : 'Ready'; ?></h4>
                                <p class="text-muted">Search Status</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <i class="fas fa-list fa-2x text-info mb-2"></i>
                                <h4><?php echo $total_pages; ?></h4>
                                <p class="text-muted">Total Pages</p>
                            </div>
                        </div>
                    </div>

                    <!-- Search Section -->
                    <div class="search-section">
                        <h3><i class="fas fa-search"></i> Search Users</h3>
                        <form method="get" action="user_list.php" class="row g-3">
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search by username or email..." 
                                       value="<?php echo htmlspecialchars($search_query); ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-custom w-100">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <?php if (!empty($search_query)): ?>
                                    <a href="user_list.php" class="btn btn-outline-secondary w-100 mt-2">
                                        <i class="fas fa-times"></i> Clear Search
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                        
                        <?php if (!empty($search_query)): ?>
                            <div class="mt-3">
                                <span class="badge bg-info">
                                    Found <?php echo $total_users; ?> result(s) for "<?php echo htmlspecialchars($search_query); ?>"
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Users Display - Table View -->
                    <div class="user-table">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Avatar</th>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($users_result) > 0): ?>
                                    <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($user['img']) && file_exists($user['img'])): ?>
                                                    <img src="<?php echo htmlspecialchars($user['img']); ?>" 
                                                         alt="Avatar" class="user-avatar">
                                                <?php else: ?>
                                                    <div class="default-avatar">
                                                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($user['id']); ?></span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                            </td>
                                            <td>
                                                <?php if (!empty($user['email'])): ?>
                                                    <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>">
                                                        <?php echo htmlspecialchars($user['email']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">No email</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($user['created_at'])): ?>
                                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Unknown</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <h4>No users found</h4>
                                            <p class="text-muted">
                                                <?php if (!empty($search_query)): ?>
                                                    No users match your search criteria.
                                                <?php else: ?>
                                                    No users registered yet.
                                                <?php endif; ?>
                                            </p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination-container">
                            <nav>
                                <ul class="pagination">
                                    <!-- Previous Page -->
                                    <?php if ($current_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>">
                                                <i class="fas fa-chevron-left"></i> Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <!-- Page Numbers -->
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <!-- Next Page -->
                                    <?php if ($current_page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>">
                                                Next <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                        
                        <!-- Pagination Info -->
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                Showing <?php echo (($current_page - 1) * $users_per_page) + 1; ?> to 
                                <?php echo min($current_page * $users_per_page, $total_users); ?> of 
                                <?php echo $total_users; ?> users
                            </small>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
mysqli_close($db);
?>