<?php $servername = "devweb2020.cis.strath.ac.uk";
$username = "xeb18139";
$password = "REDACTED";
$dbname = $username;
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error){
die("Connection failed");
}

$sql = "SELECT * FROM `Framing`";
$result = $conn->query($sql);
echo "<table><tr><th>ID</th><th>Width</th><th>Height</th><th>Units</th><th>Postage</th><th>Email</th><th>Price</th><th>Delivery Cost</th><th>Total</th></tr>\n";
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            echo "<tr><td>".$row["id"]."</td><td>".$row["width"]."</td><td>".$row["height"]."</td><td>".$row["unit"]."</td><td>".$row["postage"]."</td><td>".$row["email"]."</td><td>".$row["price"]."</td><td>".$row["deliveryCost"]."</td><td>".$row["total"]."</td></tr>\n";

        }
    }
    else{
        die ("No Matches");
    }
    $conn->close();
    echo "</table>\n";