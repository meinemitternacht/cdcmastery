<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 5/20/15
 * Time: 5:55 PM
 */

if(!empty($_POST['samplePassword'])){
    $complexity = $cdcMastery->checkPasswordComplexity($_POST['samplePassword'],"UserHandle1","UserEmail1");

    if(!is_array($complexity)){
        echo "Good to go.";
    }
    else{
        foreach($complexity as $errorMessage){
            echo $errorMessage."<br>";
        }
    }
}
?>

<form action="/dev/testPasswordComplexity" method="POST">
    <label for="samplePassword">Sample Password to Check</label>
    <input type="text" name="samplePassword">
    <br>
    <input type="submit" value="Check">
</form>