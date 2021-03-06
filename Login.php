<?php

$connect = new PDO("mysql:host=localhost;dbname=shopping_cart_db", "root", "root");

// Initialize the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    //var_dump ("aya");
}

if(isset($_SESSION["id_user"]))
{
    header("location: CheckOut.php");
}

// Check if the user is already logged in, if yes then redirect him to welcome page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] == true){
    header("location: CheckOut.php");
    //exit;
}

// Include config file
require_once "config.php";

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Check if username is empty
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }

    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }

    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        // Prepare a select statement
        $sql = "SELECT user_id, user_username, user_password FROM users WHERE user_username = ?";

        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);

            // Set parameters
            $param_username = $username;

            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);

                // Check if username exists, if yes then verify password
                if(mysqli_stmt_num_rows($stmt) == 1){
                    echo"User found";
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            echo "Password found";
                            // Password is correct, so start a new session
                            session_start();

                            // Store data in session variables
                            $_SESSION["logged_in"] = true;
                            $_SESSION["id_user"] = $id;
                            $_SESSION["username"] = $username;




                            //Storing user info in the Cookie table
                            if(isset($_COOKIE['shopping_cart']))
                            {
                                $cookie_data = stripslashes($_COOKIE['shopping_cart']);
		                        $cart_data = json_decode($cookie_data, true);
		                        $cookie_id = $cart_data['item_Cookie_id'];
                                $id = (int)$_SESSION["id_user"];
                                var_dump($cart_data);


                                $query = "SELECT cookie_id from cookie WHERE id_user = '$id' ";
                                //var_dump($query);
                                $statement = $connect->prepare($query);

                                $statement->execute();
                                $result = $statement->fetchAll();
                                //var_dump($result);

                                if(!empty($result))
                                {
                                    $_SESSION['cookie_id'] = $result[0]['cookie_id'];
                                    $cookie_id = $_SESSION['cookie_id'];
                                    $query = "UPDATE cookie SET logged_In = 1 WHERE cookie_id = '$cookie_id' ";
                                    //var_dump($query);
                                    $connect->exec($query);
                                }
                                else
                                {
                                    $_SESSION['cookie_id'] = $cookie_id;
                                    $query = "UPDATE cookie SET id_user = $id , logged_In = 1 WHERE cookie_id = '$cookie_id' ";
                                    /*$query = "INSERT INTO `cookie`(`cookie_id`, `cookie_value`, `id_user`, `logged_In`)
                                                VALUES ('$cookie_id','$cookie_value', $id , 1 )";*/
                                    //var_dump($query);
                                    $connect->exec($query);
                                }



                                //creating and updating the cookie value if already in the basket
                                $id = (int)$_SESSION["id_user"];
                                $query = $connect->prepare( "SELECT cookie_value FROM cookie Where id_user = $id ");
                               // var_dump($query);
                                $query->execute();
                                $result = $query->fetch(PDO::FETCH_ASSOC);
                                //var_dump($result['cookie_value']);
                                $new_cart_data = json_decode($result['cookie_value'], true);
                               // var_dump($new_cart_data['item_Cookie_id']);
                                var_dump($new_cart_data);
                                $new_cart_data[] = $cart_data;
                                var_dump($new_cart_data);
                                $item_data = json_encode($new_cart_data);
                               // var_dump($item_data);
                                $expiry = time() + (86400 * 30);
                                setcookie('shopping_cart', $item_data, $expiry);
                                //var_dump($_COOKIE['shopping_cart']);

                                 // Redirect user to Checkout page
                                //header("location: CheckOut.php");
                            }
                            else
                            {
                                //creating and updating the cookie value if already in the basket
                                $id = (int)$_SESSION["id_user"];
                                $query = $connect->prepare( "SELECT cookie_value FROM cookie Where id_user = $id ");
                                var_dump($query);
                                $query->execute();
                                $result = $query->fetch(PDO::FETCH_ASSOC);
                                var_dump($result['cookie_id']);
                                $_SESSION['cookie_id'] = $result['cookie_id'];
                                $new_cart_data = json_decode($result['cookie_value'], true);
                                var_dump($new_cart_data['item_Cookie_id']);

                                $item_data = json_encode($new_cart_data);
                                var_dump($item_data);
                                $expiry = time() + (86400 * 30);
                                setcookie('shopping_cart', $item_data, $expiry);
                                //var_dump($_COOKIE['shopping_cart']);

                                // Redirect user to Home page
                                header("location: index.php");
                            }
                        } else{
                            // Display an error message if password is not valid
                            $password_err = "The password you entered was not valid.";
                        }
                    }
                } else{
                    // Display an error message if username doesn't exist
                    $username_err = "No account found with that username.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
        }else{
            echo "No statement can be prepared";

        // Close statement
        mysqli_stmt_close($stmt);
    }

    // Close connection
    mysqli_close($link);
}else{
echo "Username and password not validated";}}

include "views/includes/header.php";
?>



	 <div class="container" style="max-width: 900px;">
         <div class="main">

                <section class="signup">
                    <!-- <img src="images/signup-bg.jpg" alt=""> -->
                    <div class="container">
                        <div class="signup-content">



                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" id="signup-form" class="signup-form">
                                <h2 class="form-title">Login Here</h2>
                                <div class="form-group <?php echo (!empty($username_err)) ? 'has-error' : ''; ?>"">
                                    <input type="text" class="form-input" name="username" id="username" placeholder="Username" value="<?php echo $username; ?>"/>
                                <span class="help-block"><?php echo $username_err; ?></span>
                                </div>
                                <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                                    <input type="text" class="form-input" name="password" id="password" placeholder="Password"/>
                                    <span toggle="#password" class="zmdi zmdi-eye field-icon toggle-password"></span>
                                    <span class="help-block"><?php echo $password_err; ?></span>
                                </div>
                                <div class="form-group">
                                    <input type="submit" name="submit" id="submit" class="form-submit" value="Sign In"/>
                                </div>
                            </form>
                            <p class="loginhere">
                                Dont have an account ? Create Now <a href="Register.php?" class="loginhere-link">Sign Up here</a>
                            </p>
                        </div>
                    </div>
                </section>

            </div>
	</div>
    <script src="js/password_toggle.js"></script>
	</body>
</html>