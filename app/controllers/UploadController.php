<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;

class UploadController extends Controller
{
	
	function process_file ($file)
	{
		function error ($error)
		{
			die($error);
		}
		
		$tmp_name = $file->getTempName();
		
		if (!$tmp_name)
		{
			error("Cannot upload file!");
			return false;
		}
		
		$file_name = $file->getName();
		$file_size = $file->getSize(); // file size in bytes
		$file_type = $file->getRealType();
		$file_extension = $file->getExtension();
		$max_file_size = 2 * 1048576; // in bytes
		
		$allowed_extensions = ["jpg", "jpeg", "png", "gif", "bmp"];
		
		if (!in_array($file_extension, $allowed_extensions))
		{
			error("Unknown file extension ($file_extension)! Allowed file types: ".join(", ", $allowed_extensions));
		}
		
		if ($file_size > $max_file_size)
		{
			error("File size ($file_size) exceeded the maximum of $max_file_size");
		}
		
		$identify_output = exec("identify $tmp_name");
		if (!$identify_output)
		{
			error("Cannot identify image type!");
		}

		$thumb_path = tempnam(sys_get_temp_dir(), "thumb");
		exec("convert -thumbnail 125x125 $tmp_name $thumb_path");
		
		$curl_output = exec("curl --upload-file $tmp_name https://transfer.sh/image");
		$file_url = $curl_output;
		
		$curl_output = exec("curl --upload-file $thumb_path https://transfer.sh/image");
		$thumb_url = $curl_output;
		
		/*echo $file_url."<br>";
		echo $thumb_url."<br><br>";
		echo "<img src='$thumb_url'>";*/
	}

	public function uploadAction()
	{
		if ($this->request->hasFiles())
		{
			$files = $this->request->getUploadedFiles();
			$file = $files[0];
			
			$this->process_file($file);
		}
	}
	
	public function indexAction()
	{
		//phpinfo();
		?>
		<form enctype="multipart/form-data" action="/phalcon/upload/upload" method="POST">
    <!--<input type="hidden" name="MAX_FILE_SIZE" value="30000" />-->
    Отправить этот файл: <input name="userfile" type="file" />
    <input type="submit" value="Send File" />
		</form>
		<?php
	}

}
