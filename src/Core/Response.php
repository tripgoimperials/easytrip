<?php 
namespace App\Core;

final class Response{
    public static funtion success(mixed $data,int $statusCode = 200) :never{
         http_response_code($statusCode);

         header("Content-Type:application/json");
          
         echo json_encode([
            'success' =>$statusCode >= 200 && $statusCode < 300,
            'data' => $data;
         ]);
         exit;
  }  
  
   public static funtion error(string $message,int $statusCode = 400) :never{
         http_response_code($statusCode);

         header("Content-Type:application/json");
          
         echo json_encode([
            'success' =>false,
            'message' => $message;
         ]);
         exit;
  } 
}