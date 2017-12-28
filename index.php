<?php 
//header('Content-type: application/json');
$folderName = uniqid();
$zipFileName = $folderName . '.zip';

echo json_encode($_POST);
return;
if(!isset($_POST['zipContent'])){
	http_response_code(400);
	return;
}

$zipStr = $_POST['zipContent'];

if(!$zipStr){
	http_response_code(400);
	return;
}



$basedata =  base64_decode($zipStr);
$f = fopen ($zipFileName, "a+");
fwrite($f, $basedata);
fclose($f);

$zip = new ZipArchive(); 
  
if ($zip->open($zipFileName, ZipArchive::CREATE)!== TRUE) {
	exit("Impossible d'ouvrir le fichier <$zipFileName>\n");
}

   
$zip->extractTo($folderName);
$zip->close();


if(isset($_POST['hierarchy']) && $_POST['hierarchy']){
	
	$zipContent = new FileItemRecursive($folderName);
	
}
else{
	
	$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator($folderName, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST); 

	$zipContent = [];
	foreach($files as $file){
		
		if($file->isFile())
			array_push($zipContent, new FileItem($file));
		else
			rmdir($file->getRealpath());
	}
	
	rmdir($folderName);
}

echo json_encode($zipContent);

unlink($zipFileName);


class FileItem{
	
	public $name;
	public $data;
	
	public function FileItem($file){
		
		$this->name = $file->getFilename();
		$this->data = base64_encode(file_get_contents($file->getRealpath()));
		unlink($file->getRealpath());
	}
}


class FileItemRecursive{
	
	public $type;
	public $name;
	public $children;
	public $data;
	
	public function FileItemRecursive($extractPath){
	
		if(gettype($extractPath) == 'string'){
			$this->name = $extractPath;
			$this->type = 'dir';
			$this->children = [];
			$files = new FilesystemIterator($extractPath);
			
			foreach ($files as $fileInfo){
				if($fileInfo->isDir())
					array_push($this->children, new FileItemRecursive($extractPath . '/' . $fileInfo->getFilename()));
				else
					array_push($this->children, new FileItemRecursive($fileInfo));
			}
			rmdir($extractPath);
		}
		else{
			$this->type = 'file';
			$this->name = $extractPath->getFilename();
			$this->data = base64_encode(file_get_contents($extractPath->getRealpath()));
			unlink($extractPath->getRealpath());
		}
			
	}
}


?>
