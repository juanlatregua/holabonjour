<?php
if($_POST)
{
	//Enter your email destionation address below. Do not delete double quotes!
	$to_email       = "info@holabonjour.es";

	//Do not edit anything below this line!
	$from_email 	= filter_var($_POST["fr"], FILTER_SANITIZE_EMAIL);
	$thanks       = filter_var($_POST["thanks"], FILTER_SANITIZE_STRING);
	$fail       = filter_var($_POST["fail"], FILTER_SANITIZE_STRING);


    //check if its an ajax request, exit if not
    if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
        $output = json_encode(array( //create JSON data
            'type'=>'error',
            'text' => 'Sorry Request must be Ajax POST'
        ));
        die($output); //exit script outputting json data
    }

    //Sanitize input data using PHP filter_var().
    //$user_name      = filter_var($_POST["user_name"], FILTER_SANITIZE_STRING);
	$user_name        = $_POST['user_name'];
    $user_email     = filter_var($_POST["user_email"], FILTER_SANITIZE_EMAIL);
    $phone_number   = filter_var($_POST["phone_number"], FILTER_SANITIZE_NUMBER_INT);
    //$subject        = filter_var($_POST["subject"], FILTER_SANITIZE_STRING);
	$subject        = $_POST['subject'];
    //$message        = filter_var($_POST["msg"], FILTER_SANITIZE_STRING);
	$message        = $_POST['msg'];
	$attachments = $_FILES['file_attach'];
	$file_count = count($attachments['name']);

    //additional php validation
    if(strlen($user_name)<3){ // If length is less than 3 it will output JSON error.
        $output = json_encode(array('type'=>'error', 'text' => 'Name is too short or empty!'));
        die($output);
    }
    if(!filter_var($user_email, FILTER_VALIDATE_EMAIL)){ //email validation
        $output = json_encode(array('type'=>'error', 'text' => 'Please enter a valid email!'));
        die($output);
    }
    if(strlen($subject)<3){ //check emtpy subject
        $output = json_encode(array('type'=>'error', 'text' => 'Subject is required'));
        die($output);
    }
    //email body
    $message_body .= "--------------------------------------------------------------------------------\n\nNAME: ".$user_name."\n\n\n";
    $message_body .= "--------------------------------------------------------------------------------\n\nEMAIL: ".$user_email."\n\n\n";
    if (!empty($phone_number)) $message_body .= "--------------------------------------------------------------------------------\n\nPHONE: ".$phone_number."\n\n\n";
	if (!empty($message)) $message_body .= "--------------------------------------------------------------------------------\n\nMESSAGE: ".$message."\n\n\n";

	if($file_count > 0){
		$boundary = md5("sanwebe");

		//header
		$headers = "MIME-Version: 1.0\r\n";
		$headers .= "From:".$from_email."\r\n";
		$headers .= "Reply-To: ".$user_email."" . "\r\n";
		$headers .= "Content-Type: multipart/mixed; boundary = $boundary\r\n\r\n";

		//plain text
		$body = "--$boundary\r\n";
		$body .= "Content-Type: text/plain; charset=ISO-8859-1\r\n";
		$body .= "Content-Transfer-Encoding: base64\r\n\r\n";
		$body .= chunk_split(base64_encode(utf8_decode($message_body)));

		//attachments
        for ($x = 0; $x < $file_count; $x++){
            if(!empty($attachments['name'][$x])){

                if($attachments['error'][$x]>0) //exit script and output error if we encounter any
                {
                    $mymsg = array(
                    1=>"The uploaded file exceeds the upload_max_filesize directive in php.ini",
                    2=>"The uploaded file exceeds the MAX_FILE_SIZE var",
                    3=>"The uploaded file was only partially uploaded",
                    4=>"No file was uploaded",
                    6=>"Missing a temporary folder" );
                    die($mymsg[$attachments['error'][$x]]);
                }
                //get file info
                $file_name = $attachments['name'][$x];
                $file_size = $attachments['size'][$x];
                $file_type = $attachments['type'][$x];
                //read file
                $handle = fopen($attachments['tmp_name'][$x], "r");
                $content = fread($handle, $file_size);
                fclose($handle);
                $encoded_content = chunk_split(base64_encode($content)); //split into smaller chunks (RFC 2045)

						//attachment
						$body .= "--$boundary\r\n";
						//$body .= "Content-Type: application/zip; name=\"".$file_name."\"\r\n";
						$body .= "Content-Type: application/octet-stream; name=\"".$file_name."\"\r\n";
						$body .= "Content-Transfer-Encoding: base64\r\n";
						$body .= "Content-Disposition: attachment; filename=\"".$file_name."\"\r\n\r\n";

				$body .= $encoded_content;
					}
				}
				$body .= "--".$uid."--";
	}else{
		////send plain email otherwise
		$headers = "From:".$from_email."\r\n".
		'Reply-To: '.$user_email.'' . "\n" .
		'X-Mailer: PHP/' . phpversion();
		$body = $message_body;
	}
	$send_mail = mail($to_email, $subject, $body, $headers);
    if(!$send_mail)
    {
        //If mail couldn't be sent output error. Check your PHP email configuration (if it ever happens)
        $output = json_encode(array('type'=>'error', 'text' => $fail));
        die($output);
    }else{
        $output = json_encode(array('type'=>'message', 'text' => $thanks));
        die($output);
    }
}
?>
