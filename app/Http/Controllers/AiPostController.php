<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Exception;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class AiPostController extends Controller
{
    static function generatePost() 
    {

        try {
            if (($open = fopen(storage_path() . "/app/public/post-titles.csv", "r")) !== FALSE) {

                while (($data = fgetcsv($open, 1000, ",")) !== FALSE) {
                    $postTitles[] = $data;
                }
    
                fclose($open);
            }
    
            echo "<pre>";
            print_r($postTitles);
    
            foreach($postTitles as $title) {

                print_r($title[0]);
                print_r($title[1]);
    
                $postTitle = trim($title[1]);
    
                $imageContent = file_get_contents($title[0]);
                print_r($imageContent);
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($imageContent);
                $ext = $mimeType ? str_replace('image/', '', $mimeType) : 'png';
                
                print_r($ext);
                echo "\n";
    
                $imgPath = time() . '-' . Str::slug($postTitle, '-') . '.' . $ext;
    
                Storage::put('public/post-images/' . $imgPath, $imageContent);
    
                $result = OpenAI::completions()->create([
                    "model" => "text-davinci-003",
                    "temperature" => 0.7,
                    "top_p" => 1,
                    "frequency_penalty" => 0,
                    "presence_penalty" => 0,
                    'max_tokens' => 3000,
                    'prompt' => sprintf('Write me a HTML5 formated post about: %s', $postTitle),
                ]);
            
                $content = trim($result['choices'][0]['text']);
        
                print_r($content);
    
                $post = Post::create([
                    'postTitle' => $postTitle,
                    'postCategory' => trim($title[2]),
                    'postContent'  =>  $content,
                    'postImage' => $imgPath,
                    'slug' => Str::slug($postTitle, '-'),
                    'seoDescription' =>  substr($content, 0, 160)
                ]);
    
            }


        } catch(Exception $e) {
            echo "\n\n Unable to create post " . $e->getMessage();
        }

      

    
    }
}
