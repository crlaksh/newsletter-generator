<!DOCTYPE html>
<html>
<head>
    <title>Generate Newsletter</title>
</head>
<body>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="file" name="newsletter_data" />
        <input type="submit" name="submit" value="Generate Newsletter">
    </form>
</body>
</html>
<?php

$target_dir = "data/Newsletter_DataFile/";
$target_file = $target_dir . basename($_FILES["newsletter_data"]["name"]);
$uploadOk = 1;
$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
// Check if image file is a actual image or fake image
if(isset($_POST["submit"])) {
    $check = getimagesize($_FILES["newsletter_data"]["tmp_name"]);
    // Check file size
    if ($_FILES["newsletter_data"]["size"] > 500000) {
        echo "Sorry, your file is too large.<br/>";
        $uploadOk = 0;
    }
    // Allow certain file formats
    if($imageFileType != "xls") {
        echo "Sorry, only xls files are allowed.<br/>";
        $uploadOk = 0;
    }
    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.<br/>";
    // if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($_FILES["newsletter_data"]["tmp_name"], $target_file)) {
            echo "The file ". basename( $_FILES["newsletter_data"]["name"]). " has been uploaded.";
            exec("cd /home/isha/Tools/Newsletter/;php cli.php --data=\"" . $target_dir . $_FILES["newsletter_data"]["name"] . "\"");
        } else {
            echo "Sorry, there was an error uploading your file.<br/>";
        }
    }
}

?>