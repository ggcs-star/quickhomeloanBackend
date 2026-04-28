<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reel;
use App\Models\ReelLike;
use App\Models\ReelComment;
use App\Models\ReelView;

class ReelController extends Controller
{

    public function index(Request $request)
    {
        $perPage = $request->per_page ?? 10;

      
        $user = auth()->user();

        $reels = Reel::where('is_active', 1)
            ->orderBy('_id', 'desc')
            ->paginate($perPage);

        $reels->getCollection()->transform(function ($reel) use ($user) {

            $data = $reel->toArray();


            $data['likes_count'] = $reel->likes()->count();
            $data['comments_count'] = $reel->comments()->count();


            $data['is_liked'] = false;

            if ($user) {
                $data['is_liked'] = $reel->likes()
                    ->where('user_id', (string) $user->_id)
                    ->exists();
            }

            return $data;
        });

        return response()->json([
            'status' => true,
            'data' => $reels->items(),
            'pagination' => [
                'current_page' => $reels->currentPage(),
                'last_page' => $reels->lastPage(),
                'per_page' => $reels->perPage(),
                'total' => $reels->total(),
            ]
        ]);
    }

    public function show($id)
    {
        $reel = Reel::with(['comments'])
            ->withCount(['likes', 'comments'])
            ->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $reel
        ]);
    }


    public function addView(Request $request, $id)
    {
        $ip = $request->ip();

        $exists = ReelView::where('reel_id', $id)
            ->where('ip_address', $ip)
            ->exists();

        if (!$exists) {
            ReelView::create([
                'reel_id' => $id,
                'ip_address' => $ip
            ]);

            Reel::where('_id', $id)->increment('views');
        }

        return response()->json(['status' => true]);
    }


    public function toggleLike($id)
    {
        $userId = auth()->id() ?? 'guest_' . request()->ip();

        $like = ReelLike::where('reel_id', $id)
            ->where('user_id', $userId)
            ->first();

        if ($like) {
            $like->delete();
            $liked = false;
        } else {
            ReelLike::create([
                'reel_id' => $id,
                'user_id' => $userId
            ]);
            $liked = true;
        }

        return response()->json([
            'status' => true,
            'liked' => $liked
        ]);
    }


    public function addComment(Request $request, $id)
    {
        $request->validate([
            'comment' => 'required|string'
        ]);

        ReelComment::create([
            'reel_id' => $id,
            'user_id' => auth()->id() ?? 'guest',
            'comment' => $request->comment
        ]);

        return response()->json(['status' => true]);
    }
    public function getComments($id)
    {
        $comments = ReelComment::where('reel_id', $id)
            ->latest()
            ->get(['user_id', 'comment', 'created_at']);

        return response()->json([
            'status' => true,
            'data' => $comments
        ]);
    }
}