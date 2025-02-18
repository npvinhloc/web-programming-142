<?php

class FilesController extends \BaseController {

	private $GClient;
	private $DClient;

	public function __construct(){
		if(!isset($_SESSION))
			session_start();

		$G = new googleDriveServiceInfo();
		$D = new dropboxServiceInfo();
		$this->DClient = $D->getClient();
		$this->GClient = $G->getClient();
	}

	public function test(){
		$path = $_POST['path'];
		return $this->dropboxDownloadFile($path);

	}

	public function dropboxDownloadFile($dropbox_path){
		$download = $this->DClient->createShareableLink($dropbox_path);
		$download = substr($download, 0, -1).'1';
		return $download;
	}

	public function dropboxDeleteFile($dropbox_path){
		$this->DClient->delete($dropbox_path);
		$result = "Delete file Dropbox succesfully";
		return $return;
	}

	public function MoveDroptoGDrive()
		{
			$Fpath=$_POST['path'];
			$Fname=$_POST['sfname'];
			$driveFile = new Google_Service_Drive_DriveFile($this->GClient);
			//var_dump($Fname);
			$contentDrop=$this->dropboxGetFileContent($Fpath,$Fname);

			$driveFile->setTitle($contentDrop['fileName']);
			$driveFile->setMimeType($contentDrop['mimeType']);
			$driveFile->setEditable(true);


			try{

			$DriveService = new Google_Service_Drive($this->GClient);
			}
			catch(Exception $e){
				return Redirect::to('/GAuthStart');
			}
			$createdFile = $DriveService->files->insert($driveFile, array('data'=>$contentDrop['contents'],'uploadType'=>'media'));
			return Redirect::to('home')->withFlashMessage('Moved Successfully');

		}

	public function dropboxGetFileContent($dropbox_path, $filename){
		$local_path = base_path('app/'.$filename);
		$fileMetadata = $this->DClient->getFile($dropbox_path, fopen($local_path, "a+"));
		$result=array();
		$file_contents = file_get_contents($local_path);
		$result=[
				'mimeType' => $fileMetadata['mime_type'],
				'fileName' => $filename,
				'localPath' => $local_path,
				'contents' => $file_contents
				];
		unlink($local_path);
		return $result;
	}

	public function googleListFileByName($file_name){
		$DriveService = new Google_Service_Drive($this->GClient);
		$query = 'title = "'.$file_name.'" and trashed = false';
		$params=[
				'q' => $query
				];
		$files = $DriveService->files->listFiles($params);
		$list = $files['items'];
		foreach ($list as $item){
			return $item['id'];
		}
	}

	

	public function MoveGDrivetoDrop()
	{
		
	
		$GId=$_POST['GDrivepath'];
		$GFileName=$_POST['GDrivename'];
		$Gcontent=$this->googleGetFileContent($GId);
		$Gpath='/'.$Gcontent['fileName'];
		$this->dropboxUploadFileContent($Gpath,$Gcontent);
	

		return Redirect::to('home')->withFlashMessage('Moved Successfully');

	}
	
	public function googleGetFileContent($id){
		try{
		$DriveService = new Google_Service_Drive($this->GClient);
		}
		catch(Exception $e){
			return Redirect::to('/GAuthStart');
		}
		$params = array();
		$file = $DriveService->files->get($id, $params);
		$downloadUrl = $file->getDownloadUrl();
		if($downloadUrl){
			$request = new Google_Http_Request($downloadUrl, 'GET', null, null);
		    $httpRequest = $DriveService->getClient()->getAuth()->authenticatedRequest($request);
		    if ($httpRequest->getResponseHttpCode() == 200) {
		        $data=$httpRequest->getResponseBody();
		        $result = [
		        		  'mimeType' => $file->getMimeType(),
						  'fileName' => $file->getTitle(),
						  'contents' => $data
		        		  ];
		        return $result;
		    }
		}
	}	
	public function dropboxUploadFileContent($dropbox_path, $file_info){
		$result = $this->DClient->uploadFileFromString($dropbox_path, Dropbox\WriteMode::add(), $file_info['contents']);
		return $result;
	}

	public function googleUploadFileContent(){
		$driveFile = new Google_Service_Drive_DriveFile($this->GClient);
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mimeType = finfo_file($finfo, $_FILES['GfileField']['tmp_name']);
		finfo_close($finfo);
		
		$driveFile->setTitle($_POST['Gname']);
		$driveFile->setMimeType($mimeType);
		$driveFile->setEditable(true);

		$data = file_get_contents($_FILES['GfileField']['tmp_name']);

		try{
		$DriveService = new Google_Service_Drive($this->GClient);
		}
		catch(Exception $e){
			return Redirect::to('/GAuthStart');
		}
		$createdFile = $DriveService->files->insert($driveFile, array('data'=>$data,'uploadType'=>'media'));
		return Redirect::to('home');
	}

	public function makeHome(){
		$DriveService = new Google_Service_Drive($this->GClient);
		$DropboxClient = $this->DClient;

		return View::make('home')->with('client', $DropboxClient)->with('Gclient', $DriveService);
	}

	public function DropboxUploadFile()
	{
		try{
			$uploadPath = $_POST['path'];
			$f = fopen($_FILES['fileField']['tmp_name'], "rb");
			$content = file_get_contents($_FILES['fileField']['tmp_name']);
			$result = $this->DClient->uploadFile($uploadPath, Dropbox\WriteMode::add(), $f);
			fclose($f);
			return Redirect::to('home');
		} catch(Dropbox\Exception_InvalidAccessToken $e){
			return Redirect::to('/DAuthStart');
		}

	}
	public function DeleteFile()
	{
		var_dump($_POST['id']);
		$DriveService = new Google_Service_Drive($this->GClient);
		 try {
		    $DriveService->files->delete($_POST['id']);
		  } catch (Exception $e) {
		    print "An error occurred: " . $e->getMessage();
		  }
		  $result= "Delete file Dropbox succesfully";
		  return $result;
	}

	public function DeleteFileDropbox()
	{
		 $this->DClient->delete($_POST['DropId']);
		 return Redirect::to('/home');
	}

}