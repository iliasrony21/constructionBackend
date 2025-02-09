<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\AllImage;
use App\Models\Admin\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ServiceController extends Controller
{
    protected Service $service;
    protected AllImage $allImage;

    public function __construct(Service $service,AllImage $allImage)
    {
        $this->service = $service;
        $this->allImage = $allImage;
    }

    // Fetch all services
    public function index()
    {
        return response()->json([
            'status' => true,
            'data'   => $this->service->latest()->get(),
        ]);
    }

    // Store a new service
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'             => 'required|string|max:255',
            'slug'              => 'nullable|string|unique:services,slug',
            'short_description' => 'nullable|string',
            'content'           => 'nullable|string',
            'status'            => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $service = $this->service->create([
                'title'             => $request->title,
                'slug'              => $request->slug ? Str::slug($request->slug) : Str::slug($request->title),
                'short_description' => $request->short_description,
                'content'           => $request->content,
                'status'            => $request->status,
            ]);

            if ($request->has('imageId') && $request->imageId > 0) {
                $allImage = $this->allImage->find($request->imageId);

                if ($allImage) {
                    $extArray = explode('.', $allImage->name);
                    $ext = last($extArray);
                    $fileName = time() . $service->id . '.' . $ext;

                    // Ensure directories exist
                    if (!file_exists(public_path('uploads/services/small'))) {
                        mkdir(public_path('uploads/services/small'), 0777, true);
                    }
                    if (!file_exists(public_path('uploads/services/large'))) {
                        mkdir(public_path('uploads/services/large'), 0777, true);
                    }

                    // Image paths
                    $sourcePath = public_path('uploads/all/' . $allImage->name);
                    $destPathSmall = public_path('uploads/services/small/' . $fileName);
                    $destPathLarge = public_path('uploads/services/large/' . $fileName);

                    $manager = new ImageManager(new Driver());

                    //  Crop small image to EXACTLY 500x600 (FULL COVER)
                    $image = $manager->read($sourcePath);
                    $image->scale(500, 600); // Ensures full cover without distortion
                    // $image->contain(500, 600);
                    $image->save($destPathSmall);

                    // Resize large image (width 1200, auto height)
                    $image = $manager->read($sourcePath);
                    $image->scale(1200); // Keeps aspect ratio
                    $image->save($destPathLarge);

                    // store service with new image
                    $service->image = $fileName;
                    $service->save();
                }
            }


            return response()->json([
                'status'  => true,
                'message' => 'Service stored successfully',
            ], 201); // 201 is the proper status code for "Created"
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    // Update a new service
    // public function update(Request $request, $id)
    // {

    //     $validator = Validator::make($request->all(), [
    //         'title'             => 'required|string|max:255',
    //         'slug'              => 'nullable|string|unique:services,slug,' . $id,
    //         'short_description' => 'nullable|string',
    //         'content'           => 'nullable|string',
    //         'status'            => 'required|boolean',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => $validator->errors(),
    //         ], 422);
    //     }

    //     try {
    //         // Find the service first
    //         $service = $this->service->findOrFail($id);

    //         // Update the service
    //         $service->update([
    //             'title'             => $request->title,
    //             'slug'              => $request->slug ? Str::slug($request->slug) : Str::slug($request->title),
    //             'short_description' => $request->short_description,
    //             'content'           => $request->content,
    //             'status'            => $request->status,
    //         ]);

    //         if($request->imageId > 0)
    //         {
    //             $AllImage =$this->allImage->find($request->imageId);
    //             if($AllImage != null)
    //             {
    //                 $extArray = explode('.',$AllImage->name);
    //                 $ext = last($extArray);

    //                 $fileName = time('now').$service->id.'.'.$ext;
    //                 // create small thumbnail here
    //                 $sourcePath = public_path('uploads/all/' . $AllImage->name);
    //                 $destPath = public_path('uploads/services/small/' .  $fileName);
    //                 $manage = new ImageManager(Driver::class);
    //                 $image = $manage->read($sourcePath);
    //                 $image->scaleDown(500,600);
    //                 $image->save($destPath);
    //                 // create large image here
    //                 $destPath = public_path('uploads/services/large/' .  $fileName);
    //                 $manage = new ImageManager(Driver::class);
    //                 $image = $manage->read($sourcePath);
    //                 $image->scaleDown(1200);
    //                 $image->save($destPath);
    //                 $service->image = $fileName;
    //                 $service->save();
    //             }
    //         }

    //         return response()->json([
    //             'status'  => true,
    //             'message' => 'Service updated successfully',
    //             'data'    => $service,
    //         ], 200);
    //     } catch (\Throwable $e) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'Something went wrong',
    //             'error'   => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function update(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'title'             => 'required|string|max:255',
        'slug'              => 'nullable|string|unique:services,slug,' . $id,
        'short_description' => 'nullable|string',
        'content'           => 'nullable|string',
        'status'            => 'required|boolean',
        'imageId'           => 'nullable|integer|exists:all_images,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status'  => false,
            'message' => $validator->errors(),
        ], 422);
    }

    try {
        $service = $this->service->findOrFail($id);

        $service->update([
            'title'             => $request->title,
            'slug'              => $request->slug ? Str::slug($request->slug) : Str::slug($request->title),
            'short_description' => $request->short_description,
            'content'           => $request->content,
            'status'            => $request->status,
        ]);

        if ($request->has('imageId') && $request->imageId > 0) {
            $oldImage = $service->image;
            $allImage = $this->allImage->find($request->imageId);

            if ($allImage) {
                $extArray = explode('.', $allImage->name);
                $ext = last($extArray);
                $fileName = time() . $service->id . '.' . $ext;

                // Ensure directories exist
                if (!file_exists(public_path('uploads/services/small'))) {
                    mkdir(public_path('uploads/services/small'), 0777, true);
                }
                if (!file_exists(public_path('uploads/services/large'))) {
                    mkdir(public_path('uploads/services/large'), 0777, true);
                }

                // Image paths
                $sourcePath = public_path('uploads/all/' . $allImage->name);
                $destPathSmall = public_path('uploads/services/small/' . $fileName);
                $destPathLarge = public_path('uploads/services/large/' . $fileName);

                $manager = new ImageManager(new Driver());

                // ✅ Crop small image to EXACTLY 500x600 (FULL COVER)
                $image = $manager->read($sourcePath);
                $image->scale(500, 600); // Ensures full cover without distortion
                // $image->contain(500, 600);
                $image->save($destPathSmall);

                // ✅ Resize large image (width 1200, auto height)
                $image = $manager->read($sourcePath);
                $image->scale(1200); // Keeps aspect ratio
                $image->save($destPathLarge);

                // Update service with new image
                $service->image = $fileName;
                $service->save();
                if($oldImage != "")
                {
                  File::delete(public_path('uploads/services/small/' . $oldImage));
                  File::delete(public_path('uploads/services/large/' . $oldImage));
                }
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'Service updated successfully',
        ], 200);
    } catch (\Throwable $e) {
        return response()->json([
            'status'  => false,
            'message' => 'Something went wrong',
            'error'   => $e->getMessage(),
        ], 500);
    }
}
    // show method for service
    public function show($id)
    {
        $service = $this->service->findOrFail($id);
        if($service == null)
        {
            return response()->json([
                'status'  => false,
                'message' => 'Service is not found',
            ], 422);
        }
        return response()->json([
            'status'  => true,
            'data'    => $service,
        ], 200);

    }
    // Delete method for service
    public function destroy($id)
    {
        $service = $this->service->findOrFail($id);
        if($service == null)
        {
            return response()->json([
                'status'  => false,
                'message' => 'Service is not found',
            ], 422);
        }
        $service->delete();
        return response()->json([
            'status'  => true,
            'message'    => 'Service Deleted Successfully',
        ], 200);

    }

}
