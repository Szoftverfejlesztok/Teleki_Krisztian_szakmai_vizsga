<?php
session_start();
require_once("log.php");
class UserException extends Exception{}

if(empty($_SESSION["user"])){
    header("location:login.php");
    exit();
}

if(isset($_POST['submitBekuld'])){
    $link = trim($_POST["link"]);
    $eloado = trim($_POST['eloado']);
    $cim = trim($_POST['cim']);
    try{
        if(empty($link) || empty($eloado) || empty($cim)){
            throw new UserException("Minden mezőt ki kell tölteni");
        }
        require_once("dbconn.php");
        if(empty($dbconn)){
            throw new UserException("Adatbázis kapcsolódási hiba");
        }

        if (strpos($link, "youtube.com/watch?v=") !== false) {
            $link2 = substr($link, strpos($link, "youtube.com/watch?v=") + strlen("youtube.com/watch?v="));
        } elseif (strpos($link, "youtu.be/") !== false) {
            $id_start = strpos($link, "youtu.be/") + strlen("youtu.be/");
            $id_end = strpos($link, "?si=");
            if ($id_end !== false) {
                $link2 = substr($link, $id_start, $id_end - $id_start);
            } else {
                $link2 = substr($link, $id_start);
            }
        }
        $linkid = $link2;
        $link_check = $dbconn->prepare("SELECT link FROM linkek WHERE link LIKE :link");
        $link_check->bindValue("link", '%' . $linkid . '%');
        $link_check->execute();
        
            if ($link_check->rowCount() != 0) {
                throw new UserException("Ezt a zene már be lett küldve");
            }

            $ell = '/^https:\/\/www\.youtube\.com\//';
            $ell2 = '/^https:\/\/youtu\.be\//';
            if (!preg_match($ell,$link) && !preg_match($ell2,$link)) {
                throw new UserException("Kérlek érvényes youtube linket küldj be");
            }

            $sql = "INSERT INTO linkek(felhasznalonev,eloado,cim,link)
                VALUES(:felhasznalonev,:eloado,:cim,:link)";

            $querynewLink = $dbconn->prepare($sql);
            $querynewLink->bindValue("felhasznalonev",$_SESSION['user']['felhasznalonev'],PDO::PARAM_STR);
            $querynewLink->bindValue("eloado",$eloado,PDO::PARAM_STR);
            $querynewLink->bindValue("cim",$cim,PDO::PARAM_STR);
            $querynewLink->bindValue("link",$link,PDO::PARAM_STR);
            $querynewLink->execute();
            echo "<script>alert('A zene sikeresen beküldve');</script>";
    }catch(UserException $e){
        $error = "Beküldési hiba: ".$e->getMessage();
    }

} //submit vége




//Zenék ki írása listába**************************************************************************

require_once("dbconn.php");
$sqlZenek = "SELECT link,felhasznalonev,zeneid,vote FROM linkek ORDER BY vote DESC";
$zenek = $dbconn->query($sqlZenek);
$album = array();
if ($zenek->rowCount() > 0) {
    while($row = $zenek->fetch(PDO::FETCH_ASSOC)) {
        $link = $row["link"];
        $felhasznalonev = $row["felhasznalonev"];
        $zeneid = $row["zeneid"];
        $vote = $row["vote"];
        if (strpos($link, "youtube.com/watch?v=") !== false) {
            $link = substr($link, strpos($link, "youtube.com/watch?v=") + strlen("youtube.com/watch?v="));
        } elseif (strpos($link, "youtu.be/") !== false) {
            $id_start = strpos($link, "youtu.be/") + strlen("youtu.be/");
            $id_end = strpos($link, "?si=");
            if ($id_end !== false) {
                $link = substr($link, $id_start, $id_end - $id_start);
            } else {
                $link = substr($link, $id_start);
            }
        }
        $album[] = array("link" => $link, "nev" => $felhasznalonev, "zeneid" => $zeneid, "vote" => $vote);
    }
}



require_once("dbconn.php");
$sqlF = "SELECT felhasznalonev,teljesnev,email FROM regisztracio";
$felhasznalok = $dbconn->query($sqlF);
$users = array();
if($felhasznalok->rowCount() > 0){
    while($row = $felhasznalok->fetch(PDO::FETCH_ASSOC)){
        $fFelhasznalonev = $row["felhasznalonev"];
        $fTeljesnev = $row["teljesnev"];
        $fEmail = $row["email"];
        $users[] = array("felnev" => $fFelhasznalonev, "telnev" => $fTeljesnev, "email" => $fEmail);
    }
}



//Admin
require_once("dbconn.php");
$sqlAdmin = "SELECT admin FROM regisztracio WHERE id=:id";
$stmA = $dbconn->prepare($sqlAdmin);
$stmA->bindValue("id",$_SESSION["user"]["id"]);
$stmA->execute();
$admin = $stmA->fetch(PDO::FETCH_ASSOC);


//Törlés részleg
if(isset($_POST["torol"])){
    $zid = $_POST["torol"];
    require_once("dbconn.php");

    $sqlDeleteMusic = "DELETE FROM linkek WHERE zeneid = :id";
    $stmtDeleteMusic = $dbconn->prepare($sqlDeleteMusic);
    $stmtDeleteMusic->bindValue("id",$zid,PDO::PARAM_STR);
    $stmtDeleteMusic->execute();
    
    $sqlDeleteVotes = "DELETE FROM szavazatok WHERE zid = :id";
    $stmtDeleteVotes = $dbconn->prepare($sqlDeleteVotes);
    $stmtDeleteVotes->bindValue("id",$zid,PDO::PARAM_STR);
    $stmtDeleteVotes->execute();
    header("location:index.php");
}
if(isset($_POST["Ftorol"])){
    $nev = $_POST["Ftorol"];
    require_once("dbconn.php");

    $sqlDeleteMusic = "DELETE FROM regisztracio WHERE felhasznalonev = :nev";
    $stmtDeleteMusic = $dbconn->prepare($sqlDeleteMusic);
    $stmtDeleteMusic->bindValue("nev",$nev,PDO::PARAM_STR);
    $stmtDeleteMusic->execute();
    header("location:index.php");
}


//Szavazas
if(isset($_POST["submit"])){
    $voteid = explode(";",$_POST["submit"]);
    require_once("dbconn.php");



    $query = "SELECT * FROM szavazatok WHERE fid = :fid AND zid = :zid";
    $stmt = $dbconn->prepare($query);
    $stmt->bindValue("fid",$_SESSION["user"]["id"]);
    $stmt->bindValue("zid",$voteid[0]);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        $insert_query = "INSERT INTO szavazatok (zid, fid) VALUES (:zid, :fid)";
        $insert_stmt = $dbconn->prepare($insert_query);
        $insert_stmt->bindValue("fid", $_SESSION["user"]["id"]);
        $insert_stmt->bindValue("zid", $voteid[0]);
        $insert_stmt->execute();
        $szavazat = array("zid" => $voteid[0], "fid" => $_SESSION["user"]["id"], "vote" => "null");
    } else {
        $szavazat = array("zid" => $result['zid'], "fid" => $result['fid'], "vote" => $result['vote']);
    }

    if($voteid[1] == "+" && $szavazat['vote'] == "le"){
        foreach($album as $elem){
            if($elem["zeneid"] == $voteid[0]){
                $vote = $elem["vote"] + 2;
            } 
        }
       
        $insert_query = "UPDATE szavazatok SET vote=:vote WHERE zid=:zid AND fid=:fid";
        $insert_stmt = $dbconn->prepare($insert_query);
        $insert_stmt->bindValue("vote","fel",PDO::PARAM_STR);
        $insert_stmt->bindValue("fid",$_SESSION["user"]["id"],PDO::PARAM_STR);
        $insert_stmt->bindValue("zid",$voteid[0],PDO::PARAM_STR);
        $insert_stmt->execute();

        $sql = "UPDATE linkek SET vote =:vote WHERE zeneid=:zeneid";
        $queryUpdate = $dbconn->prepare($sql);
        $queryUpdate->bindValue("vote",$vote,PDO::PARAM_STR);
        $queryUpdate->bindValue("zeneid",$voteid[0],PDO::PARAM_STR);
        $queryUpdate->execute();
        header("location:index.php");

    }else if($voteid[1] == "+" && $szavazat['vote'] == "null"){
        foreach($album as $elem){
            if($elem["zeneid"] == $voteid[0]){
                $vote = $elem["vote"] + 1;
            } 
        }

        $insert_query = "UPDATE szavazatok SET vote=:vote WHERE zid=:zid AND fid=:fid";
        $insert_stmt = $dbconn->prepare($insert_query);
        $insert_stmt->bindValue("vote","fel",PDO::PARAM_STR);
        $insert_stmt->bindValue("fid",$_SESSION["user"]["id"],PDO::PARAM_STR);
        $insert_stmt->bindValue("zid",$voteid[0],PDO::PARAM_STR);
        $insert_stmt->execute();

        $sql = "UPDATE linkek SET vote =:vote WHERE zeneid=:zeneid";
        $queryUpdate = $dbconn->prepare($sql);
        $queryUpdate->bindValue("vote",$vote,PDO::PARAM_STR);
        $queryUpdate->bindValue("zeneid",$voteid[0],PDO::PARAM_STR);
        $queryUpdate->execute();
        header("location:index.php");

    }

    if($voteid[1] == "-" && $szavazat['vote'] == "fel"){
        foreach($album as $elem){
            if($elem["zeneid"] == $voteid[0]){
                $vote = $elem["vote"] - 2;
            } 
        }

        $insert_query = "UPDATE szavazatok SET vote=:vote WHERE zid=:zid AND fid=:fid";
        $insert_stmt = $dbconn->prepare($insert_query);
        $insert_stmt->bindValue("vote","le",PDO::PARAM_STR);
        $insert_stmt->bindValue("fid",$_SESSION["user"]["id"],PDO::PARAM_STR);
        $insert_stmt->bindValue("zid",$voteid[0],PDO::PARAM_STR);
        $insert_stmt->execute();

        $sql = "UPDATE linkek SET vote =:vote WHERE zeneid=:zeneid";
        $queryUpdate = $dbconn->prepare($sql);
        $queryUpdate->bindValue("vote",$vote,PDO::PARAM_STR);
        $queryUpdate->bindValue("zeneid",$voteid[0],PDO::PARAM_STR);
        $queryUpdate->execute();
        header("location:index.php");


    }else if($voteid[1] == "-" && $szavazat['vote'] == "null"){
        foreach($album as $elem){
            if($elem["zeneid"] == $voteid[0]){
                $vote = $elem["vote"] - 1;
            } 
        }

        $insert_query = "UPDATE szavazatok SET vote=:vote WHERE zid=:zid AND fid=:fid";
        $insert_stmt = $dbconn->prepare($insert_query);
        $insert_stmt->bindValue("vote","le",PDO::PARAM_STR);
        $insert_stmt->bindValue("fid",$_SESSION["user"]["id"],PDO::PARAM_STR);
        $insert_stmt->bindValue("zid",$voteid[0],PDO::PARAM_STR);
        $insert_stmt->execute();

        $sql = "UPDATE linkek SET vote =:vote WHERE zeneid=:zeneid";
        $queryUpdate = $dbconn->prepare($sql);
        $queryUpdate->bindValue("vote",$vote,PDO::PARAM_STR);
        $queryUpdate->bindValue("zeneid",$voteid[0],PDO::PARAM_STR);
        $queryUpdate->execute();
        header("location:index.php");

    }

}




?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <link rel="icon" type="image/x-icon" href="pic/ico.png">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="index.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <title>SZSZCRadio</title>
</head>
<body>
    <div class="container">
        <header>
            <div>
                <center>
                <a href="login.php?logout" class="pic"><img src="pic/off.png" class="icon"></a>
                </center>
                <?php echo "<p class='nev'>".$_SESSION["user"]["felhasznalonev"]."</p>"; ?>
            </div>
        </header>


        <form action="<?= $_SERVER["PHP_SELF"]?>" method="post" class="bekuldes">
            <div>
                <div class="inputBox">
                    <input class="input-2" type="text" name="eloado" placeholder="Előadó" required>
                    <input class="input-2" type="text" name="cim" placeholder="Cím" required>
                </div>
                <div class="inputBox">
                    <input class="input-1" type="text" name="link" placeholder="A zene youtube linkje" required>
                </div>
                <center>
                <button type="submit" name="submitBekuld">Beküldés</button>
                </center>
            </div>
        </form>
        <form action="<?= $_SERVER["PHP_SELF"]?>" method="post" class="zenek">
            <div class="center">
                    <?php
                        if($admin['admin'] == 1){
                            echo "<table>";
                            echo "<tr>
                                    <th>Zene:</th>
                                    <th>Beküldő felhasználó neve:</th>
                                    <th>Értékelés:</th>
                                  </tr>";
                            for($i = 0 ; $i < count($album); $i++) {
                                echo "<tr>";
                                echo "<td class='link'>";
                                echo '<iframe width="224" height="126" src="https://www.youtube.com/embed/'.$album[$i]["link"].'?si=MwaB4fZhVyV017de" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>';
                                echo "</td>";
                                echo "<td>".$album[$i]["nev"]."</td>";
                                echo "<td>";
                                echo '<button class="votebt" type="submit" name="submit"value="' . $album[$i]["zeneid"] . ';-""><img src="pic/downvote.png" width="16"></button>';
                                echo $album[$i]["vote"];
                                echo '<button class="votebt" type="submit" name="submit"value="' . $album[$i]["zeneid"] . ';+""><img src="pic/upvote.png" width="16"></button>';
                                echo "</td>";
                                echo "<td>";
                                echo '<button class="votebt" type="submit" name="torol"value="' . $album[$i]["zeneid"] . '"><img src="pic/torol.png" width="16"></button>';
                                echo "</td>";
                                echo "</tr>";
                            }
                            echo "</table>";

                            echo '<table class="users">';
                            echo "<tr>
                                    <th>Felhasznalók</th>
                                    <th>Teljes név</th>
                                    <th>E-mail</th>
                                  </tr>";
                            for($i = 0; $i < count($users); $i++){
                                echo "<tr>";
                                echo "<td>";
                                echo $users[$i]["felnev"];
                                echo "</td>";
                                echo "<td>";
                                echo $users[$i]["telnev"];
                                echo "</td>";
                                echo "<td>";
                                echo $users[$i]["email"];
                                echo "</td>";
                                echo "<td>";
                                echo '<button class="votebt" type="submit" name="Ftorol"value="' . $users[$i]["felnev"] . '"><img src="pic/torol.png" width="16"></button>';
                                echo "</td>";
                                echo "</tr>";
                            }
                            
                            echo "</table>";

                        }
                    ?>
                    <?php
                        if($admin['admin'] == 0){
                            echo "<table>";
                            echo "<tr>
                                    <th>Zene:</th>
                                    <th>Beküldő felhasználó neve:</th>
                                    <th>Értékelés:</th>
                                  </tr>";
                            for($i = 0 ; $i < count($album); $i++) {
                                echo "<tr>";
                                echo "<td class='link'>";
                                echo '<iframe width="224" height="126" src="https://www.youtube.com/embed/'.$album[$i]["link"].'?si=MwaB4fZhVyV017de" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>';
                                echo "</td>";
                                echo "<td>".$album[$i]["nev"]."</td>";
                                echo "<td>";
                                echo '<button class="votebt" type="submit" name="submit"value="' . $album[$i]["zeneid"] . ';-""><img src="pic/downvote.png" width="16"></button>';
                                echo $album[$i]["vote"];
                                echo '<button class="votebt" type="submit" name="submit"value="' . $album[$i]["zeneid"] . ';+""><img src="pic/upvote.png" width="16"></button>';
                                echo "</td>";
                                echo "</tr>";
                            }
                            echo "</table>";
                        }
                    ?>             
            </div>
        </form>
    </div>
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