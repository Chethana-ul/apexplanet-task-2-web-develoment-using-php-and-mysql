<?php
session_start();

// initializing variables
$id = "";
$username = "";
$errors = array(); 

// connect to the database
$db = mysqli_connect('localhost', 'root', 'Cc!@1122', 'blog');

// REGISTER USER
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['reg_user'])) {

  // receive all input values from the form 
  $id = (int) $_POST['id']; // For integer input, cast it
  $username = mysqli_real_escape_string($db, $_POST['username']);
  $password_1 = mysqli_real_escape_string($db, $_POST['password_1']);
  $password_2 = mysqli_real_escape_string($db, $_POST['password_2']);

  // form validation: ensure that the form is correctly filled ...
  // by adding (array_push()) corresponding error unto $errors array
  if (empty($id)) { array_push($errors, "id is required"); }
  if (empty($username)) { array_push($errors, " username is required"); }
  if (empty($password_1)) { array_push($errors, "Password is required"); }
  if ($password_1 != $password_2) {
	array_push($errors, "The two passwords do not match");
  }

  // first check the database to make sure 
  // a user does not already exist with the same username and/or email
  $user_check_query = "SELECT * FROM users WHERE id='$id' OR username='$username' LIMIT 1";
  $result = mysqli_query($db, $user_check_query);
  $user = mysqli_fetch_assoc($result);
  
  if ($user) { // if user exists
    if ($user['id'] === $id) {
      array_push($errors, "id already exists");
    }

    if ($user['username'] === $username) {
      array_push($errors, "username already exists");
    }
  }

  // Finally, register user if there are no errors in the form
  if (count($errors) == 0) {
  	$password = md5($password_1);//encrypt the password before saving in the database

  	$query = "INSERT INTO users (id, username, password) 
  			  VALUES('$id', '$username', '$password')";
  	mysqli_query($db, $query);
  	$_SESSION['id'] = $id;
  	$_SESSION['success'] = "You are now logged in";
  	header('location: index.php');
  }
}

// LOGIN USER
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login_user'])) {

  $username = mysqli_real_escape_string($db, $_POST['username']);
  $password = mysqli_real_escape_string($db, $_POST['password']);

  if (empty($username)) {
  	array_push($errors, "Username is required");
  }
  if (empty($password)) {
  	array_push($errors, "Password is required");
  }

  if (count($errors) == 0) {
  	$password = md5($password);
  	$query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
  	$results = mysqli_query($db, $query);
  	if (mysqli_num_rows($results) == 1) {
  	  $_SESSION['username'] = $username;
  	  $_SESSION['success'] = "You are now logged in";
  	  header('location: index.php');
  	}else {
  		array_push($errors, "Wrong username/password combination");
  	}
  }
}

?>
