<?php
namespace App\Http\Constants;
class Upload{
           //Upload document to storage
           public static function upload_document($file,$name){
            $path = $file->storeAs(
                'public/images', $name
            );
            return $path;
        }
        //Delete document from storage
        public static function delete_document($path){
            if(Storage::exists($path)){
                Storage::delete($path);
            }
                return true;
        }
        //Check if the file has a valid size(less than 5MB)
        public static function isValidFileSize($file){            

            //Size in MBs
            $size = $file->getSize()/(1024*1024);

            //Check if the file size is less than limit
            if($size > 5)
                return false;
            return true;
        }
        //Check if the file is in valid format
        public static function isValidFileFormat($file){
            //Check if the file has required extensions
            if($file->extension() === 'jpg' || $file->extension() === 'png')
                return true;      
            return false;
        }
}