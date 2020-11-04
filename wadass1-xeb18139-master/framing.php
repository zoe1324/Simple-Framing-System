<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Frame Price Estimator</title>
</head>
<body>
<h1> Frame Price Estimator </h1>

<?php
$servername = "devweb2020.cis.strath.ac.uk";
$username = "xeb18139";
$password = "REDACTED";
$dbname = $username;
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error){
    die("Connection failed");
}

$width = strip_tags(isset($_POST["width"])? $_POST["width"] : "");
$height = strip_tags(isset($_POST["height"])? $_POST["height"] : "");
$units = isset($_POST["units"])? $_POST["units"] : "";
$postage = isset($_POST["postage"])? $_POST["postage"] : "";
$email = strip_tags(isset($_POST["email"])? $_POST["email"] : "");
$optIn = isset($_POST["saveCalc"])? $_POST["saveCalc"] : "";
$error = "";

?>
<form action="framing.php" method=post>
    Frame of size:
    <input type="text" name="width" value="<?php echo $width ?>">
    x
    <input type="text" name="height" value="<?php echo $height ?>">
    <select name="units">
        <option value="" selected disabled>Select
        <option <?php if ($units === "mm"){?> selected <?php } ?>value="mm">mm</option>
        <option <?php if ($units === "cm"){?> selected <?php } ?>value="cm">cm</option>
        <option <?php if ($units === "inch"){?> selected <?php } ?>value="inch">inch</option>
    </select><br><br>
    Postage:
    <input type="radio" id="standard" name="postage" value="standard" <?php if ($postage === "standard"){ ?> checked="checked"<?php } ?>>
    <label for="standard">Standard</label>
    <input type="radio" id="rapid" name="postage" value="rapid" <?php if ($postage === "rapid"){ ?> checked="checked"<?php } ?>>
    <label for="rapid">Rapid</label><br>
    <br>
    Enter Email For a Quote:
    <input type="text" name="email" value="<?php echo $email ?>"><br><br>
    <input type="checkbox" name="saveCalc" id="saveCalc" <?php if ($optIn === "saveCalc"){?> checked <?php } ?> value="saveCalc"
    <label for="saveCalc">Receive mail and future information about my framing calculation</label><br><br>
    <input type="submit"/><br><br>
</form>
<?php


if($width != "" && $height != "" && $units != "" && $postage != ""){//if all fields are filled
    $error = testMeasurement($error, $units, $width, "Width");
    $error = testMeasurement($error, $units, $height, "Height");

    if($email != ""){
        $error = testEmail($error, $email);
    }
    elseif($optIn != ""){
        $error = emptyErrorCheck($error,$email, "An Email");
    }

    if($error === ""){
        $originalWidth = $width;
        $originalHeight = $height;
        $width = convertToMetres($units, $width);
        $height = convertToMetres($units, $height);
        $l = max($width, $height);
        $area = $width * $height;
        $price = number_format((float)(($area * $area) + (90 * $area) + 5),2,'.',',');
        $deliveryCost = number_format((float)postagePrice($l, $postage),2,'.',',');
        $total = $deliveryCost + $price;
        $total = number_format((float)$total,2,'.',',');

        if ($optIn === "saveCalc" && $email != ""){
            mail($email, "My Framing Calc. Quote", "Your frame will cost £" . $price . " plus " . $postage . " postage of £" . $deliveryCost . " giving a total of £" .$total);

            $sql = "INSERT INTO `xeb18139`.`Framing`(`id`,`width`,`height`,`unit`,`postage`,`email`, `price`, `deliveryCost`,`total`)VALUES"."(NULL, '$originalWidth','$originalHeight','$units','$postage','$email','$price','$deliveryCost','$total');";

            if ($conn->query($sql) === TRUE){
                echo "inserted new entry with id ".$conn->insert_id."<br>";
            }
            else{
                die ("Error: ".$sql."<br>".$conn->error);
            }

            $conn->close();
        }
        echo "Your frame will cost £" . $price . " plus " . $postage . " postage of £" . $deliveryCost . " giving a total of £" .$total;
    }
    else{
        echo $error;
    }
}
elseif($width != "" || $height != "" || $units != "" || $postage != "" || $optIn != ""){//Some fields are filled
    $error = "";
    $error = emptyErrorCheck($error,$width,"Width");
    $error = emptyErrorCheck($error,$height,"Height");
    $error = emptyErrorCheck($error,$units, "Unit of measurement");
    $error = emptyErrorCheck($error,$postage, "Postage");
    if($optIn != ""){
        $error = emptyErrorCheck($error,$email, "An Email");
    }
    echo $error;
}
function emptyErrorCheck($error, $input, $field){
    if($input === ""){
        $error = $error."*".$field." is required<br>";
    }
    return $error;
}
function testEmail($error, $email){
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = $error."*Invalid email format";
    }
    return $error;
}
function convertToMetres($units, $input){
    switch($units) {
        case "mm":
            return $input / 1000;
        case "cm":
            return $input / 100;
        case "inch":
            return $input / 39.37;
    }
}
function testMeasurement($error, $units, $input, $field){
    if(!is_numeric($input)){
        $error = $error."*".$field." must be a number<br>";
    }
    else{
        $length = convertToMetres($units, $input);
        if($length === 0){
            $length = 0.1;
        }
        switch ($length){
            case $length < 0.2:
                switch($units){
                    case "mm":
                        $error = $error."*".$field." must be at least 200mm<br>";
                        break;
                    case "cm":
                        $error = $error."*".$field." must be at least 20cm<br>";
                        break;
                    case "inch":
                        $error = $error."*".$field." must be at least 7.874 inches<br>";
                }
                break;
            case $length > 2.0:
                switch($units){
                    case "mm":
                        $error = $error."*".$field." must be no more than 2000mm<br>";
                        break;
                    case "cm":
                        $error = $error."*".$field." must be no more than 200cm<br>";
                        break;
                    case "inch":
                        $error = $error."*".$field." must be no more than 78.74 inches<br>";
                }
                break;
        }
    }
    return $error;
}
function postagePrice($l, $postage)
{
    if ($postage == "rapid") {
        return round(4 * $l + 8, 2);
    }
    else {
        return round(3 * $l + 4, 2);
    }
}
?>
</body>
</html>