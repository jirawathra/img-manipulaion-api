<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class ResizeImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // return false;
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        // return [
        //     'image' => ['required'],
        //     'w' => ['required', 'regex: /^\d+(\.\d+)?%$/'],
        //     'h' => ['regex: /^\d+(\.\d+)?%$/'],
        //     'album_id' => 'exists:\App\Models\Album,id'
        // ];

        $rules = [
            'image' => ['required'],
            'w' => ['required', 'regex: /^\d+(\.\d+)?%?$/'],
            'h' => ['regex: /^\d+(\.\d+)?%?$/'],
            'album_id' => 'exists:\App\Models\Album,id'
        ];

        // 'album_id' => 'exists:\App\Models\Album,id' 
        // will check the existing of 'id' in the Album Model

        // becuase image can be uploaded file or from url

        // $image = $this->post('image');
        $image = $this->all()['image'] ?? false;
        // echo '<pre>';
        // var_dump($image);
        // echo '</pre>';
        // exit;

        if ($image && $image instanceof UploadedFile) {
            // add one more rule for 'image'
            $rules['image'][] = 'image';
        } else {
            // it's an url, so add one more rule for 'url'
            $rules['image'][] = 'url';
        }


        // echo '<pre>';
        // var_dump($rules);
        // echo '</pre>';
        // exit;

        return $rules;
    }
}
