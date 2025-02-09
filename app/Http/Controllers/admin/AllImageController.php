<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\AllImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class AllImageController extends Controller
{
    protected AllImage $allImage;

    public function __construct(AllImage $allImage)
    {
        $this->allImage = $allImage;
    }

    public function store(Request $request)
    {
        // validate image upload
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:png,jpg,webp,gif,jpeg|max:2048', // Limit file size to 2MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first('image'), //  Fix error message
            ], 422); // 422 Unprocessable Entity is better for validation errors
        }

        try {
            //  Get file extension
            $image = $request->file('image');
            $ext = $image->getClientOriginalExtension();
            $imageName = time() . '.' . $ext;

            //  Store image name in DB
            $storeImage = $this->allImage->create([
                'name' => $imageName,
            ]);

            // Move original image to uploads directory
            $originalPath = public_path('uploads/all/' . $imageName);
            $image->move(public_path('uploads/all'), $imageName);

            // Create thumbnail
            $thumbnailPath = public_path('uploads/all/thumbnail/' . $imageName);
            $manager = new ImageManager(new Driver()); // Correct way to instantiate ImageManager
            $thumbnail = $manager->read($originalPath);
            $thumbnail->cover(300, 300); // Resizes to exactly 300x300
            $thumbnail->save($thumbnailPath);

            return response()->json([
                'status'  => true,
                'message' => 'Image stored successfully',
                'data'    => $storeImage,
            ], 201); // Use 201 Created for new resources
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
