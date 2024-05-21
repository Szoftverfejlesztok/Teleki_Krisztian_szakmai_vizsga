<?php
$error = "";
$msg = "";
session_start();
require_once("log.php");
class UserException extends Exception{}

if(isset($_GET["s"])){
    $msg="Sikeres regisztráció!";
}

if(isset($_GET["logout"])){
    unset($_SESSION["user"]);
    setcookie("userId","",time()-1);
    unset($_COOKIE["userId"]);
}

if(!empty($_COOKIE["userId"])){
    try{
        require_once("dbconn.php");
        if(empty($dbconn)){
            throw new UserException("Adatbázis kapcsolódási hiba");
        }
        $sqlCookie = "SELECT id, felhasznalonev, jelszo, email FROM regisztracio WHERE id =:userId";
        $queryCookie = $dbconn->prepare($sqlCookie);
        $queryCookie->execute(array("userId"=>$_COOKIE["userId"]));
        if ($queryCookie->rowCount() == 0){
            throw new UserException("Nem létező felhasználó");
        }
        $user = $queryCookie->fetch(PDO::FETCH_ASSOC);
        $_SESSION["user"] = array("felhasznalonev"=>$user["felhasznalonev"]);

    }catch(UserException $e){
        $error = "Tárolt felhasználói hiba:".$e->getMessage();
    }
}

if(!empty($_SESSION["user"])){
    header("location:index.php");
    exit();
}

if(isset($_POST["submitLogin"])){
    $loginName = trim($_POST["username"]);
    $pw = trim($_POST["password"]);
    try{
        if(empty($loginName) || empty($pw)){
            throw new UserException("Felhasználó és jelszó is kötelező");
        }
        require_once("dbconn.php");
        if(empty($dbconn)){
            throw new UserException("Adatbázis kapcsolódási hiba");
        }
        $sqlLogin = "SELECT id, felhasznalonev, jelszo FROM regisztracio WHERE felhasznalonev =:felhasznalonev";
        $queryLogin = $dbconn->prepare($sqlLogin);
        $queryLogin->bindValue("felhasznalonev",$loginName);
        $queryLogin->execute();
        if($queryLogin->rowCount()==0){
            throw new UserException("Hibás felhasználónév");
        }
        
        $user = $queryLogin->fetch(PDO::FETCH_ASSOC);
        if(!password_verify($pw,$user["jelszo"])){
            throw new UserException("Hibás jelszó");
        }

        $_SESSION["user"] = array("felhasznalonev"=>$user["felhasznalonev"],"id"=>$user["id"]);
        if(isset($_POST["marad"])){
            setcookie("userId",$user["id"],time()+60*60*24);
        }
        header("location:index.php");
        exit();
    }catch(UserException $e){
        $error = "Bejelentkezési hiba: ".$e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <link rel="icon" type="image/x-icon" href="pic/ico.png">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="login.css">
    <title>SZSZCRadio</title>
</head>
<body>
<?php
try {
   require_once("dbconn.php");
    $dbconn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}catch(PDOException $e){
    echo "<p>Adatbázis kapcsolódási hiba: ".$e->getMessage()."</p>";
    die();
}
?>
<form action="<?= $_SERVER["PHP_SELF"]?>" method="post">
        <div class="container">
            <h1>Bejelentkezés</h1>
            <div class="inputBox">
                <input type="text" name="username" required>
                <div class="placeholder">Név</div>
            </div>
            <div class="inputBox">
                <input type="password" name="password" required>
                <div class="placeholder">Jelszó</div>
            </div>
            <button type="submit" name="submitLogin">Bejelentkezés</button>
            <div>
            <label for="marad">Bejelentkezve maradok</label>
            <input type="checkbox" name="marad" id="marad">
            </div>
            <p>Még nincs fiókod? <a href="register.php">Regisztrálj!</a></p>
        </div>
    </form>
    <?php
    if(!empty($msg)){
        echo "<script>alert('$msg');</script>";
    }
    
    if(!empty($error)){
        echo "<script>alert('$error');</script>";
    }
    ?>
</body>
</html>