<?php
/** Test program for really simple login, registration and password verification.
  * Just POC for chef testing. Do not rely on it.
  * Has tons of bugs.
  * Author: Priit Pääsukene
  **/
        # Config include
        #TODO: pre-evaluate variables expected from config.php
        include 'config.php';

        # State machine state declaration
        define("DEFAULT_STATE",-1);
        define("REGISTERED",1);
        define("LOGGED_IN",2);
        define("REGISTER_FAILED",3);
        define("LOGIN_FAILED",4);

        $state=DEFAULT_STATE;

        # Basic error handling and  memcache connection
        # variables should come from config.php
        try {
                $memcache = new Memcache;
                $memcache->connect($memcache_server,$memcache_port);
        }  catch ( Exception $ex ) {
                echo "error connecting to memcache server";
                die();
        }

        #Login logic part
        #basic auth and verify functionality. Populate $state variable

        #populate local variables.
        #TODO: replace preg_match with proper username and password policy verify function
        if ( isset($_POST['email']) && preg_match('/^[[:alnum:]]+$/',$_POST['email']) ){
            $username=$_POST['email'];
        }
        if ( isset($_POST['password']) && preg_match('/^[[:alnum:]]+$/',$_POST['password']) ){
            $password=$_POST['password'];
        }

        #switch form action
        switch ( $_POST['action'] ) {
            case "Login":
                if ( isset($username) && isset($password) ) {
                    $password_hash=$memcache->get('user_'.$username);
                    if ( $password_hash && password_verify($password,$password_hash) )
                        $state=LOGGED_IN;
                } else
                    $state=LOGIN_FAILED;
                break;
            case "Register":
                if ( !$memcache->get('user_'.$username) && isset($password) ) {
                    $password_hash=password_hash($password,PASSWORD_BCRYPT);
                    $memcache->set('user_'.$username,$password_hash);
                    $state=REGISTERED;
                } else 
                    $state=REGISTER_FAILED;
                break;
        }

#main app section
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>Test app</title>
<body>
<?php if ($state==LOGGED_IN) { 
?>
    <h2>Hello World!</h2>
    <a href="/">Logout</a>
    </p>
<?php
} else {
    if ($state==REGISTERED) echo "<h2>Account registered. please repeat your password to log in.</h2>";
    if ($state==REGISTER_FAILED) echo "<h2>Account registration failed. please try again.</h2>";
    if ($state==LOGIN_FAILED) echo "<h2>Login failed. please try again.</h2>";
?>
<b>for testing purposes use only alphanumeric passwords.</b><br>
<form action="/" method="POST" >
e-mail/username: <input type="text" name="email"/><br/>
Password: <input type="password" name="password"/><br/>
<input type="submit" submit name="action" value="Register"/><input type="submit" name="action" value="Login"/> 
</form>
<?php
}
?>
</body>
</html>
