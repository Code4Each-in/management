<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\Controller;
use App\Models\Captchas;

class WebsiteApiController extends Controller
{
    public function getrandomchar(Request $request)
    {

        $permitted_chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ';

        $image = imagecreatetruecolor(200, 50);
        imageantialias($image, true);
        $colors = [];
        $red = rand(125, 175);
        $green = rand(125, 175);
        $blue = rand(125, 175);
        for ($i = 0; $i < 5; $i++) {
            $colors[] = imagecolorallocate($image, $red - 20 * $i, $green - 20 * $i, $blue - 20 * $i);
        }
        imagefill($image, 0, 0, $colors[0]);
        for ($i = 0; $i < 10; $i++) {
            imagesetthickness($image, rand(2, 10));
            $line_color = $colors[rand(1, 4)];
            // imagerectangle($image, rand(-10, 190), rand(-10, 10), rand(-10, 190), rand(40, 60), $line_color);
        }
        $black = imagecolorallocate($image, 0, 0, 0);
        $white = imagecolorallocate($image, 255, 255, 255);
        $textcolors = [$black, $white];

        // Update the path to your font file
        $fonts = public_path('Conquest-8MxyM.ttf');

        $string_length = 6;
        $captcha_string = $this->generateString($permitted_chars, $string_length);
        $string = $captcha_string;
        for ($i = 0; $i < $string_length; $i++) {
            $letter_space = 170 / $string_length;
            $initial = 15;

            imagettftext($image, 24, rand(-15, 15), $initial + $i * $letter_space, rand(25, 45), $textcolors[rand(0, 1)], $fonts, $captcha_string[$i]);
        }

        ob_start();
        imagepng($image);
        $imageData =base64_encode(ob_get_clean());
        imagedestroy($image);
        
        $captchas =Captchas::create([
            'captcha_string' => $string,
            'created_at' => date("Y-m-d"),
        ]);

        $response = [
            'status' => 200,
            'string' => $string,
            'image' => $imageData,
            'captcha_id' => $captchas->id
        ];
        return Response()->json($response);

    }

    public function generateString($input, $strength = 10)
    {
        $input_length = strlen($input);
        $random_string = '';
        for ($i = 0; $i < $strength; $i++) {
            $random_character = $input[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }

        return $random_string;
    }

    public function deleteCaptcha($input, $strength = 10)
    {
        $holiday = Captchas::where('created_at','<', "2023-09-15")->delete();
        dd("deleted Successfully!");
    }
    }
    