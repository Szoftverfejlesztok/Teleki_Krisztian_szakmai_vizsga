<?php
$error = "";
$msg = "";
session_start();
require_once("log.php");
class UserException extends Exception{}





if (isset($_POST["submitRegister"])){
    $felhasznalonev = trim($_POST["felhasznalonev"]);
    $teljesnev = trim($_POST["vezeteknev"]." ".trim($_POST["keresztnev"]));
    $email = trim($_POST["email"]);
    $jelszo = trim($_POST["jelszo"]);

    try{
        if(empty($felhasznalonev) || empty($teljesnev) || empty($jelszo) || empty($email)){
            throw new UserException("Minden adat kötelező");
        }
        require_once("dbconn.php");
        
            $stmt_check = $dbconn->prepare("SELECT felhasznalonev FROM regisztracio WHERE felhasznalonev = :felhasznalonev");
            $stmt_check->bindValue("felhasznalonev", $felhasznalonev);
            $stmt_check->execute();
        
            if ($stmt_check->rowCount() != 0) {
                throw new UserException("Felhasználónév már foglalt");
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new UserException("Rossz email formátum");
                exit();
            }
            

            $stmt_check_email = $dbconn->prepare("SELECT email FROM regisztracio WHERE email = :email");
            $stmt_check_email->bindValue("email", $email);
            $stmt_check_email->execute();

            if($stmt_check_email->rowCount() != 0){
                throw new UserException("Ezzel az email címmel már regisztráltak");
            }

       

        $pwhash = password_hash($jelszo, PASSWORD_DEFAULT);
         $sql = "INSERT INTO regisztracio(felhasznalonev,teljesnev,email,jelszo)
                VALUES(:felhasznalonev,:teljesnev,:email,:jelszo)";

        $querynewUser = $dbconn->prepare($sql);
        $querynewUser->bindValue("felhasznalonev",$felhasznalonev,PDO::PARAM_STR);
        $querynewUser->bindValue("teljesnev",$teljesnev,PDO::PARAM_STR);
        $querynewUser->bindValue("jelszo",$pwhash,PDO::PARAM_STR);
        $querynewUser->bindValue("email",$email,PDO::PARAM_STR);
        $querynewUser->execute();
        header("location:login.php?s");
        exit();
    }catch(UserException $e){
        $error = "Sikertelen regisztráció: ".$e->getMessage();
    }catch(PDOException $e){
        $error = "Adatbázis mentési hiba: ".$e->getMessage();
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
    <form action="<?=$_SERVER["PHP_SELF"]?>" method="post">
		<div class="container">
			<h1>Regisztráció</h1>
			<div class="inputBox">
				<input type="text" required name="felhasznalonev">
				<div class="placeholder" >Felhasználónév</div>
			</div>


            <div class="inputBox" style="display: flex;">
                <div class="inputBox" style="margin: 2px;">
                    <input type="text" required name="vezeteknev">
                    <div class="placeholder">Vezetéknév</div>
                </div>
                <div class="inputBox" style="margin: 2px;">
                    <input type="text" required name="keresztnev">
                    <div class="placeholder">Keresztnév</div>
                </div>
            </div>


			<div class="inputBox">
				<input type="text" required name="email">
				<div class="placeholder">E-mail</div>
			</div>
			<div class="inputBox">
				<input type="password" required name="jelszo">
				<div class="placeholder">Jelszó</div>
			</div>
			<button type="submit" name="submitRegister">Regisztráció</button>
			<p>Van már fiókod? <a href="login.php">Jelentkezz be!!</a></p>
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