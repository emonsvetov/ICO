<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\SocialWallPostRequest;
use App\Models\Organization;
use App\Models\Program;
use App\Models\SocialWallPost;
use App\Services\SocialWallPostService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SocialWallPostController extends Controller
{
    private SocialWallPostService $socialWallPostService;

    public function __construct(SocialWallPostService $socialWallPostService)
    {
        $this->socialWallPostService = $socialWallPostService;
    }

    public function index(Organization $organization, Program $program, Request $request)
    {
        $user = auth()->guard('api')->user();
        return $this->socialWallPostService->getIndexData($organization, $program, $user, $request->all());
    }

    public function store(SocialWallPostRequest $request)
    {
        $newSocialWallPost = $this->socialWallPostService->create($request->validated());

        if ( ! $newSocialWallPost) {
            return response(['errors' => 'Social Wall Post Creation failed'], 422);
        }

        return response(['socialWallPost' => $newSocialWallPost]);
    }

    public function uploadImage(Request $request)
    {
        if ($request->hasFile('upload')) {
            $image = $request->file('upload');
            $imageName = $image->getClientOriginalName();
            $image->move(public_path('upload'), $imageName);
            return response()->json(['success' => true, 'message' => 'Image uploaded successfully']);
        }
        return response()->json(['success' => false, 'message' => 'No image file found']);

    }
    public function like(Organization $organization, Program $program, Request $request)
    {
        $user = auth()->guard('api')->user();
        return $this->socialWallPostService->like($organization, $program, $user, $request->all());
    }

    public function delete(Organization $organization, Program $program, SocialWallPost $socialWallPost)
    {
        $user = auth()->guard('api')->user();
        $socialWallPost->update(['updated_by' => $user->id]);
        $socialWallPost->delete();
        return response(['success' => true]);
    }

}
