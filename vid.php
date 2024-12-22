<?php
// db.php - Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "video_streaming";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// User Registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    
    $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";
    if ($conn->query($sql) === TRUE) {
        echo "Registration successful.";
    } else {
        echo "Error: " . $conn->error;
    }
}

// User Login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    session_start();
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: #upload"); // Redirect to upload section
        } else {
            echo "Invalid credentials.";
        }
    } else {
        echo "No user found.";
    }
}

// Video Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_video'])) {
    session_start();
    $user_id = $_SESSION['user_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $privacy = $_POST['privacy'];

    // Upload video
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["video"]["name"]);
    move_uploaded_file($_FILES["video"]["tmp_name"], $target_file);
    
    // Save video info to database
    $sql = "INSERT INTO videos (user_id, title, description, privacy, file_path) 
            VALUES ('$user_id', '$title', '$description', '$privacy', '$target_file')";
    if ($conn->query($sql) === TRUE) {
        echo "Video uploaded successfully.";
    } else {
        echo "Error: " . $conn->error;
    }
}

// View Video and Track Views
if (isset($_GET['video_id'])) {
    $video_id = $_GET['video_id'];
    $sql = "SELECT * FROM videos WHERE id = $video_id";
    $result = $conn->query($sql);
    $video = $result->fetch_assoc();

    // Increment view count
    $conn->query("UPDATE videos SET views = views + 1 WHERE id = $video_id");

    if ($video['privacy'] == 'private') {
        echo "This video is private.";
    } else {
        echo "<video width='600' controls>
                <source src='" . $video['file_path'] . "' type='video/mp4'>
                Your browser does not support the video tag.
              </video>";
        echo "<p>Views: " . $video['views'] . "</p>";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Streaming</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Video Streaming App</h1>
        <nav>
            <a href="#register-form">Register</a>
            <a href="#login-form">Login</a>
            <a href="#upload">Upload Video</a>
        </nav>
    </header>

    <!-- Register Form -->
    <section id="register-form">
        <h2>Register</h2>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="register">Register</button>
        </form>
    </section>

    <!-- Login Form -->
    <section id="login-form">
        <h2>Login</h2>
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>
    </section>

    <!-- Video Upload Form -->
    <section id="upload">
        <h2>Upload Video</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="title" placeholder="Video Title" required>
            <textarea name="description" placeholder="Video Description"></textarea>
            <select name="privacy" required>
                <option value="public">Public</option>
                <option value="private">Private</option>
            </select>
            <input type="file" name="video" accept="video/*" required>
            <button type="submit" name="upload_video">Upload Video</button>
        </form>
    </section>

    <!-- Display Videos -->
    <section id="videos">
        <h2>Public Videos</h2>
        <div class="videos-container">
            <?php
            $sql = "SELECT * FROM videos WHERE privacy = 'public'";
            $result = $conn->query($sql);
            while ($video = $result->fetch_assoc()) {
                echo "<div class='video'>
                        <h3>" . $video['title'] . "</h3>
                        <video width='300' controls>
                            <source src='" . $video['file_path'] . "' type='video/mp4'>
                            Your browser does not support the video tag.
                        </video>
                        <a href='?video_id=" . $video['id'] . "'>Watch Video</a>
                        <p>Views: " . $video['views'] . "</p>
                      </div>";
            }
            ?>
        </div>
    </section>

</body>
</html>

<!-- CSS (styles.css) -->
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        background-color: #f4f4f4;
    }
    header {
        background-color: #333;
        color: white;
        padding: 1rem;
        text-align: center;
    }
    nav a {
        color: white;
        margin: 0 10px;
        text-decoration: none;
    }
    nav a:hover {
        text-decoration: underline;
    }
    section {
        padding: 20px;
        margin: 20px;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    .videos-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }
    .video {
        width: 300px;
        background-color: #fff;
        padding: 10px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    video {
        width: 100%;
        border-radius: 8px;
    }
    form input, form textarea, form select, form button {
        width: 100%;
        padding: 10px;
        margin: 10px 0;
        border-radius: 5px;
        border: 1px solid #ccc;
    }
    button {
        background-color: #333;
        color: white;
        border: none;
    }
    button:hover {
        background-color: #555;
    }
</style>
