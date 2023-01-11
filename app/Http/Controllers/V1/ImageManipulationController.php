<?php

namespace App\Http\Controllers\V1;

use App\Models\Album;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ImageManipulation;
use Illuminate\Http\UploadedFile;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use App\Http\Requests\ResizeImageRequest;
use App\Http\Resources\V1\ImageManipulationResource;

class ImageManipulationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //display all image
        // ImageManipulationResource::collection(ImageManipulation::all());
        //display all image with pagination
        // return ImageManipulationResource::collection(ImageManipulation::paginate());
        return ImageManipulationResource::collection(ImageManipulation::where('user_id', $request->user()->id)->paginate());
    }

    public function byAlbum(Request $request, Album $album)
    {

        if ($request->user()->id != $album->user_id) {
            return abort(403, 'Unathorized!');
        }

        //display all image(s) in album
        $condition = [
            'album_id' => $album->id
        ];

        return ImageManipulationResource::collection(ImageManipulation::where($condition)->paginate());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreImageManipulationRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function resize(ResizeImageRequest $request)
    {
        // get whole data from $request
        $all = $request->all();

        // echo '<pre>';
        // var_dump($all);
        // echo '</pre>';
        // exit;
        $image = $all['image'];
        // echo '<pre>';
        // var_dump($image);
        // echo '</pre>';
        // exit;
        unset($all['image']);

        $data = [
            'type' => ImageManipulation::TYPE_RESIZE,
            'data' => json_encode($all),
            'user_id' => null
        ];

        // album_id is optional, so check here
        if (isset($all['album_id'])) {
            // TODO
            $album = Album::find($all['album']);
            if ($request->user()->id != $album->user_id) {
                return abort(403, 'Unathorized!');
            }

            $data['album_id'] = $all['album_id'];
        }

        // image saving and resizing

        // prepare directory for image
        $imageDir = 'images/' . Str::random() . '/';
        // echo '<pre>';
        // var_dump($imageDir);
        // echo '</pre>';
        // exit;
        $absolutePath = public_path($imageDir);
        // echo '<pre>';
        // var_dump($imageDir);
        // var_dump($absolutePath);
        // echo '</pre>';
        // exit;
        // /public/images/
        File::makeDirectory($absolutePath);


        if ($image instanceof UploadedFile) {
            // file case
            $data['name'] = $image->getClientOriginalName();
            // echo '<pre>';
            // var_dump($data['name']);
            // echo '</pre>';
            // exit;
            $fileName = pathinfo($data['name'], PATHINFO_FILENAME);
            $extension = $image->getClientOriginalExtension();
            // echo '<pre>';
            // var_dump($fileName);
            // var_dump($extension);
            // echo '</pre>';
            // exit;
            $originalPath = $absolutePath . $data['name'];
            // echo '<pre>';
            // var_dump($originalPath);
            // echo '</pre>';
            // exit;
            $image->move($originalPath, $data['name']);
        } else {
            // url case
            $data['name'] = pathinfo($image, PATHINFO_BASENAME);
            // echo '<pre>';
            // var_dump($data['name']);
            // echo '</pre>';
            // exit;
            $fileName = pathinfo($image, PATHINFO_FILENAME);
            $extension = pathinfo($image, PATHINFO_EXTENSION);
            // echo '<pre>';
            // var_dump($fileName);
            // var_dump($extension);
            // echo '</pre>';
            // exit;
            $originalPath = $absolutePath . $data['name'];
            // echo '<pre>';
            // var_dump($originalPath);
            // echo '</pre>';
            // exit;
            copy($image, $originalPath);
        }

        $data['path'] = $imageDir . $data['name'];

        // start resizing section
        $w = $all['w'];
        $h = $all['h'] ?? false;

        list($width, $height, $image) = $this->getImageWidthAndHeight($w, $h, $originalPath);
        $resizedFileName = $fileName . '-resized.' . $extension;
        $image->resize($width, $height)->save($absolutePath . $resizedFileName);
        $data['output_path'] = $imageDir . $resizedFileName;

        $imageManipulation = ImageManipulation::create($data);
        return new ImageManipulationResource($imageManipulation);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ImageManipulation  $imageManipulation
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, ImageManipulation $image)
    {
        if ($request->user()->id != $image->user_id) {
            return abort(403, 'Unathorized!');
        }
        return new ImageManipulationResource($image);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ImageManipulation  $imageManipulation
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, ImageManipulation $image)
    {

        if ($request->user()->id != $image->user_id) {
            return abort(403, 'Unathorized!');
        }

        $image->delete();
        return response('', 204);
    }
    
    protected function getImageWidthAndHeight($w, $h, $originalPath)
    {
        // make image instance form the original path
        $image = Image::make($originalPath);
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        if (str_ends_with($w, '%')) {
            $ratioW = (float) str_replace('%', '', $w);
            $ratioH = $h ? (float) str_replace('%', '', $h) : $ratioW;

            $newWidth = $originalWidth * $ratioW / 100;
            $newHeight = $originalHeight * $ratioH / 100;
        } else {
            $newWidth = (float)$w;
            $newHeight = $h ? (float)$h : $originalHeight * $newWidth / $originalWidth;
        }

        return [$newWidth, $newHeight, $image];
    }
}
