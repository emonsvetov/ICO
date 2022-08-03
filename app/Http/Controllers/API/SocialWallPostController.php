<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\SocialWallPostRequest;
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

    public function index(Request $request)
    {
        return $this->socialWallPostService->getIndexData($request->all());
    }

    public function store(SocialWallPostRequest $request)
    {
        $newSocialWallPost = $this->socialWallPostService->create($request->validated());

        if ( ! $newSocialWallPost) {
            return response(['errors' => 'Social Wall Post Creation failed'], 422);
        }

        return response(['socialWallPost' => $newSocialWallPost]);
    }

}
