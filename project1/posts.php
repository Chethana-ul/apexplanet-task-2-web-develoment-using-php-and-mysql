<?php
// posts.php - Enhanced posts management with search and pagination
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
$posts_per_page = 5;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $posts_per_page;

// Handle search
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = mysqli_real_escape_string($db, $_GET['search']);
}

// Handle post creation
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_post'])) {
    $title = mysqli_real_escape_string($db, $_POST['title']);
    $content = mysqli_real_escape_string($db, $_POST['content']);
    $author = $_SESSION['username'];
    
    if (!empty($title) && !empty($content)) {
        $query = "INSERT INTO posts (title, content,created_at) VALUES ('$title', '$content', NOW())";
        if (mysqli_query($db, $query)) {
            $_SESSION['success'] = "Post created successfully!";
            header('location: posts.php');
            exit();
        }
    }
}

// Handle post deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $post_id = (int)$_GET['delete'];
    $query = "DELETE FROM posts WHERE id = $post_id AND author = '{$_SESSION['username']}'";
    if (mysqli_query($db, $query)) {
        $_SESSION['success'] = "Post deleted successfully!";
        header('location: posts.php');
        exit();
    }
}

// Build search query
$where_clause = "";
if (!empty($search_query)) {
    $where_clause = "WHERE title LIKE '%$search_query%' OR content LIKE '%$search_query%'";
}

// Get total posts count for pagination
$count_query = "SELECT COUNT(*) as total FROM posts $where_clause";
$count_result = mysqli_query($db, $count_query);
$total_posts = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_posts / $posts_per_page);

// Get posts with search and pagination
$posts_query = "SELECT * FROM posts $where_clause ORDER BY created_at DESC LIMIT $posts_per_page OFFSET $offset";
$posts_result = mysqli_query($db, $posts_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts Management</title>
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
        
        .post-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            padding: 25px;
            border: 1px solid #e9ecef;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .post-title {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .post-meta {
            color: #6c757d;
            font-size: 0.9em;
            margin-bottom: 15px;
        }
        
        .post-content {
            color: #495057;
            line-height: 1.6;
            margin-bottom: 15px;
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
        
        .btn-danger-custom {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
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
        
        .create-post-form {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
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
                                <i class="fas fa-blog"></i> Posts Management
                            </span>
                            <div class="navbar-nav ms-auto">
                                <a class="nav-link" href="index.php">
                                    <i class="fas fa-home"></i> Home
                                </a>
                                <a class="nav-link" href="user_list.php">
                                    <i class="fas fa-users"></i> Users
                                </a>
                                <a class="nav-link text-danger" href="index.php?logout=1">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        </div>
                    </nav>

                    <!-- Header Section -->
                    <div class="header-section">
                        <h1><i class="fas fa-newspaper"></i> Posts Management System</h1>
                        <p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!</p>
                    </div>

                    <!-- Success Message -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-custom alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <!-- Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="stats-card">
                                <i class="fas fa-file-alt fa-2x text-primary mb-2"></i>
                                <h4><?php echo $total_posts; ?></h4>
                                <p class="text-muted">Total Posts</p>
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

                    <!-- Create Post Form -->
                    <div class="create-post-form">
                        <h3><i class="fas fa-plus-circle"></i> Create New Post</h3>
                        <form method="post" action="posts.php">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Post Title</label>
                                        <input type="text" class="form-control" id="title" name="title" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Author</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="content" class="form-label">Content</label>
                                <textarea class="form-control" id="content" name="content" rows="4" required></textarea>
                            </div>
                            <button type="submit" name="create_post" class="btn btn-custom">
                                <i class="fas fa-plus"></i> Create Post
                            </button>
                        </form>
                    </div>

                    <!-- Search Section -->
                    <div class="search-section">
                        <h3><i class="fas fa-search"></i> Search Posts</h3>
                        <form method="get" action="posts.php" class="row g-3">
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search by title or content..." 
                                       value="<?php echo htmlspecialchars($search_query); ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-custom w-100">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <?php if (!empty($search_query)): ?>
                                    <a href="posts.php" class="btn btn-outline-secondary w-100 mt-2">
                                        <i class="fas fa-times"></i> Clear Search
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                        
                        <?php if (!empty($search_query)): ?>
                            <div class="mt-3">
                                <span class="badge bg-info">
                                    Found <?php echo $total_posts; ?> result(s) for "<?php echo htmlspecialchars($search_query); ?>"
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Posts Display -->
                    <div class="posts-section">
                        <h3><i class="fas fa-list"></i> Posts 
                            <span class="badge bg-secondary"><?php echo $total_posts; ?></span>
                        </h3>
                        
                        <?php if (mysqli_num_rows($posts_result) > 0): ?>
                            <?php while ($post = mysqli_fetch_assoc($posts_result)): ?>
                                <div class="post-card">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h4 class="post-title">
                                                <i class="fas fa-file-alt text-primary"></i>
                                                <?php echo htmlspecialchars($post['title']); ?>
                                            </h4>
                                            <div class="post-meta">
                                                <i class="fas fa-user"></i> By <?php echo htmlspecialchars($post['id']); ?>
                                                <i class="fas fa-calendar ms-3"></i> <?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?>
                                            </div>
                                            <div class="post-content">
                                                <?php echo nl2br(htmlspecialchars(substr($post['content'], 0, 200))); ?>
                                                <?php if (strlen($post['content']) > 200): ?>
                                                    <span class="text-muted">...</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="ms-3">
                                            <?php if ($post['id'] === $_SESSION['username']): ?>
                                                <a href="posts.php?delete=<?php echo $post['id']; ?>" 
                                                   class="btn btn-danger-custom btn-sm"
                                                   onclick="return confirm('Are you sure you want to delete this post?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="post-card text-center">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h4>No posts found</h4>
                                <p class="text-muted">
                                    <?php if (!empty($search_query)): ?>
                                        No posts match your search criteria. Try different keywords.
                                    <?php else: ?>
                                        Be the first to create a post!
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>
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
                                Showing <?php echo (($current_page - 1) * $posts_per_page) + 1; ?> to 
                                <?php echo min($current_page * $posts_per_page, $total_posts); ?> of 
                                <?php echo $total_posts; ?> posts
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