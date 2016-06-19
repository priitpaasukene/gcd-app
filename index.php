<?php
/** Testprogramm, mis genereerib kliendi id, salvestab räsi memcached serverisse
  * ja tagastab, mitu korda antud klient on lehekülge külastanud.
  * Autor: Priit Pääsukene
  **/
        GLOBAL $salt,$client_id,$client_times_visited,$token;
        include 'config.php';

        define("DEFAULT_STATE",-1);
        define("REGISTERED",1);
        define("LOGGED_IN",2);
        define("REGISTER_FAILED",3);
        define("LOGIN_FAILED",4);

        $state=DEFAULT_STATE;

        /** generate_new_client()  - genereerib uue kliendi parameetrid
          *
          **/

        function generate_new_client() {
                GLOBAL $client_id,$client_times_visited,$token;
                $client_id=rand();
                $client_times_visited=0;
                $token=sha1($client_id.$salt);
//              echo "generated: $client_id $token";
        }


        try {
                $memcache = new Memcache;
                $memcache->connect($memcache_server,$memcache_port);
        }  catch ( Exception $ex ) {
                echo "error connecting to memcache server";
                die();
        }

        if ( isset($_GET['token']) && preg_match('/^[[:alnum:]]+$/',$_GET['token']) ){
                $token=$_GET['token'];
                $client_id = $memcache->get($token."_id"); 
                $client_times_visited = $memcache->get($token."_times_visited");

                if ( !$client_id ) {
                        generate_new_client();
                }
        } else { // Klienti ei tuvastatud, genereerime uue kliendi id.
                generate_new_client();
        }

        // kliendi andmed genereeritud/loetud, suurendan külastuse 
        // lugejat ja salvestan väärtused memcached baasi.

        $client_times_visited++;
        $memcache->set($token."_id",$client_id);
        $memcache->set($token."_times_visited",$client_times_visited);

        #Login logic part
        #TODO: replace preg_match with proper verify function
        if ( isset($_GET['email']) && preg_match('/^[[:alnum:]]+$/',$_GET['email']) ){
            $username=$_GET['email'];
        }
        if ( isset($_GET['password']) && preg_match('/^[[:alnum:]]+$/',$_GET['password']) ){
            $password=$_GET['password'];
        }

        switch ( $_GET['action'] ) {
            case "Login":
                if ( isset($username) && isset($password) ) {
                    $password_hash=$memcache->get('user_'.$username);
                    if ( $password_hash && password_verify($password,$password_hash) )
                        $state=LOGGED_IN;
                } else
                    $state==LOGIN_FAILED;
                break;
            case "Register":
                if ( !$memcache->get('user_'.$username) && isset($password) ) {
                    $password_hash=password_hash($password,PASSWORD_BCRYPT);
                    $memcache->set('user_'.$username,$password_hash);
                    $state==REGISTERED;
                } else 
                    $state==REGISTER_FAILED;
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
    <p>
    You are client nr. <?php echo $client_id; ?> and you have visited
    this page <?php echo $client_times_visited; ?> times.
    </p>
    <p>
    <a href="/?token=<?php echo $token; ?>">Continue</a>
    <a href="/?token=<?php echo $token; ?>"></a>
    </p>
<?php
} else {
    if ($state==REGISTERED) echo "<h2>Account registered. please repeat your password to log in.</h2>";
    if ($state==REGISTER_FAILED) echo "<h2>Account registration failed. please try again.</h2>";
?>
<b>for testing purposes use only alphanumeric passwords.</b><br>
<form action="/" method="GET" >
e-mail/username: <input type="text" name="email"/><br/>
Password: <input type="text" name="password"/><br/>
<input type="submit" submit name="action" value="Register"/><input type="submit" name="action" value="Login"/> 
</form>
<?php
}
?>
</body>
</html>
