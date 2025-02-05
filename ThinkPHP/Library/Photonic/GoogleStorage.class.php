<?php
namespace Photonic;
use Think\Controller;

use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Storage\NotFoundException;

class GoogleStorage extends Controller {
    public $bucket_name = 'root';
    function _initialize(){
        $GOOGLE_STORAGE_BUCKET_NAME = C('GOOGLE_STORAGE_BUCKET_NAME');
        $this->bucket_name = $GOOGLE_STORAGE_BUCKET_NAME ? $GOOGLE_STORAGE_BUCKET_NAME : str_replace('.', '', $_SERVER['HTTP_HOST']);
        // dump($this->bucket_name);exit();
        putenv("GOOGLE_APPLICATION_CREDENTIALS=".$_SERVER['DOCUMENT_ROOT'].'/google-account-credentials.json');
        $this->storage = new StorageClient();
        $this->bucket = $this->storage->bucket($this->bucket_name);
    }

    public function show_files($file_path='', $signedUrl=false){
        $folderObjects = $this->bucket->objects(['prefix' => $file_path]);
        // 自定義排序規則
        $objectsArray = iterator_to_array($folderObjects);
        usort($objectsArray, function ($object1, $object2) {
            // 按照 updated 欄位的值進行比較
            return strcmp($object2->info()['updated'], $object1->info()['updated']);
        });

        $files = [];
        foreach ($objectsArray as $object) {
            $info = $object->info();
            // dump($info);exit;
            $file_paths = explode('/', $info['name']);
            $file_name = end($file_paths);

            $dateTime = new \DateTime($info['updated'], new \DateTimeZone('UTC'));
            $dateTime->setTimezone(new \DateTimeZone('Asia/Taipei'));
            $signedUrl = $signedUrl ? $object->signedUrl(new \DateTime('tomorrow')) : '';
            array_push($files, [
                'name' => $file_name,
                'path' => $info['name'],
                'signedUrl' => $signedUrl,
                'updated' => $dateTime->format('Y-m-d H:i:s'),
            ]);
        }
        // dump($files); exit;
        return $files;
    }
    public function show_image($file_path=''){
        $objectUrl = '';
        if(!$file_path) { return $objectUrl; }
        $object = $this->bucket->object($file_path);
        if($object->exists()){
            $objectUrl = $object->signedUrl(new \DateTime('tomorrow'));
        }
        return $objectUrl; 
    }

    public function upload_base64($file_base64='', $file_name='', $file_path=''){
        if(!$file_base64){ $this->error('請上傳檔案'); }
        if(!$file_name){ $this->error('請上傳檔案'); }
        $upload_path = $file_path.'/'.$file_name;
        $file_base64 = substr($file_base64, strpos($file_base64, ",") + 1);
        $this->bucket->upload(
            base64_decode($file_base64),
            [
                'name' => $upload_path,
                // 'predefinedAcl' => 'publicRead' /*設定權限*/
            ]
        );
        return $upload_path;
        // $this->success('操作成功');
    }
    public function upload($file_key, $file_path=''){
    	if(!$file_path) { $this->error('請提供路徑'); }
        
        $file = $_FILES[$file_key] ?? null;
        if(!$file){ $this->error('請上傳檔案'); }
        if(gettype($file['name'])=='array'){ /*複數檔案上傳*/
            $upload_path = [];
            foreach ($file['name'] as $key => $value) {
                if(!$file['name'][$key]){ continue; }
                if(!$file['tmp_name'][$key]){ continue; }
                $upload_path_one = $file_path.'/'.$file['name'][$key];
                $this->bucket->upload(
                    fopen($file['tmp_name'][$key], 'r'),
                    [
                        'name' => $upload_path_one,
                        // 'predefinedAcl' => 'publicRead' /*設定權限*/
                    ]
                );
                array_push($upload_path, $upload_path_one);
            }
        }else{ /*單檔上傳*/
            if(!$file['name']){ $this->error('請上傳檔案'); }
            if(!$file['tmp_name']){ $this->error('請上傳檔案'); }
            $upload_path = $file_path.'/'.$file['name'];
            $this->bucket->upload(
                fopen($file['tmp_name'], 'r'),
                [
                    'name' => $upload_path,
                    // 'predefinedAcl' => 'publicRead' /*設定權限*/
                ]
            );
        }

        return $upload_path;
        // $this->success('操作成功');
    }

    public function download($file_path=''){
    	if(!$file_path) { $this->error('請提供路徑'); }
        // dump($file_path);exit;
    	$object = $this->bucket->object($file_path);
        if(!$object->exists()) { $this->error('檔案不存在'); }
        $file_paths = explode('/', $file_path);
        $file_name = end($file_paths);
        // $object->downloadToFile($_SERVER['DOCUMENT_ROOT'].'/XXX/'.$file_name); /*載到伺服器上的指定路徑*/
        $objectUrl = $object->signedUrl(new \DateTime('tomorrow'));
        $info = $object->info();
        // dump($info);exit;
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $file_name); 
        header('Content-Transfer-Encoding: binary');
        header('Connection: Keep-Alive');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . $info['size']);
        ob_clean();
        flush();
        readfile($objectUrl);
        exit;
    }

    public function delete($file_path=''){
        if(!$file_path) { $this->error('請提供路徑'); }
        $object = $this->bucket->object($file_path);
        if($object->exists()){
            $object->delete();
            $this->success('操作成功');
        }else{
            $this->error('檔案不存在');
        }
    }
}
?>