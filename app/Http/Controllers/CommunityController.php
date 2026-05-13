<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CommunityPost;
use App\Models\CommunityComment;

class CommunityController extends Controller
{
    public function getPosts(Request $request)
    {
        $posts = CommunityPost::orderBy('created_at', 'desc')->paginate(20);

        foreach ($posts as $post) {
            $post->is_liked_by_user = in_array(
                (string)$request->user()->_id,
                $post->likes ?? []
            );

            $post->is_saved_by_user = in_array(
                (string)$request->user()->_id,
                $post->saved_by ?? []
            );
        }

        return response()->json([
            'status' => true,
            'message' => 'Posts fetched successfully',
            'data' => $posts
        ]);
    }

    public function getPost($id, Request $request)
    {
        $post = CommunityPost::findOrFail($id);

        $comments = CommunityComment::where('post_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();

        $post->is_liked_by_user = in_array(
            (string)$request->user()->_id,
            $post->likes ?? []
        );

        $post->is_saved_by_user = in_array(
            (string)$request->user()->_id,
            $post->saved_by ?? []
        );

        return response()->json([
            'status' => true,
            'message' => 'Post fetched successfully',
            'data' => [
                'post' => $post,
                'comments' => $comments
            ]
        ]);
    }

    // CREATE POST
    public function createPost(Request $request)
    {
        $request->validate([
            'content' => 'required|string'
        ]);

        $user = $request->user();

        $post = CommunityPost::create([
            'user_id' => (string)$user->_id,
            'user_name' => $user->full_name ?? $user->name ?? 'User',
            'user_photo' => $user->profile_photo ?? null,
            'content' => $request->input('content'),

            'likes' => [],
            'likes_count' => 0,

            'shares' => [],
            'shares_count' => 0,

            'saved_by' => [],
            'saved_count' => 0,

            'comments_count' => 0,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Post created successfully',
            'data' => $post
        ], 201);
    }

    // LIKE POST
    public function toggleLike($id, Request $request)
    {
        $post = CommunityPost::findOrFail($id);

        $userId = (string)$request->user()->_id;

        if (in_array($userId, $post->likes ?? [])) {

            $post->likes = array_values(
                array_diff($post->likes ?? [], [$userId])
            );

            $post->likes_count = max(
                0,
                ($post->likes_count ?? 0) - 1
            );

            $liked = false;

        } else {

            $post->likes = array_merge(
                $post->likes ?? [],
                [$userId]
            );

            $post->likes_count = ($post->likes_count ?? 0) + 1;

            $liked = true;
        }

        $post->save();

        return response()->json([
            'status' => true,
            'message' => $liked ? 'Post liked' : 'Post unliked',
            'data' => [
                'liked' => $liked,
                'likes_count' => $post->likes_count
            ]
        ]);
    }

    // SHARE POST
    public function sharePost($id, Request $request)
    {
        $post = CommunityPost::findOrFail($id);

        $userId = (string)$request->user()->_id;

        if (!in_array($userId, $post->shares ?? [])) {

            $post->shares = array_merge(
                $post->shares ?? [],
                [$userId]
            );

            $post->shares_count = ($post->shares_count ?? 0) + 1;

            $post->save();
        }

        return response()->json([
            'status' => true,
            'message' => 'Post shared successfully',
            'data' => [
                'shares_count' => $post->shares_count
            ]
        ]);
    }

    // SAVE POST
  public function toggleSave($id, Request $request)
{
    $post = CommunityPost::findOrFail($id);

    $userId = (string)$request->user()->_id;

    if (in_array($userId, $post->saved_by ?? [])) {

        // UNSAVE
        $post->saved_by = array_values(
            array_diff(
                $post->saved_by ?? [],
                [$userId]
            )
        );

        $post->saved_count = max(
            0,
            ($post->saved_count ?? 0) - 1
        );

        $saved = false;

    } else {

        // SAVE
        $post->saved_by = collect(
            $post->saved_by ?? []
        )
        ->push($userId)
        ->unique()
        ->values()
        ->toArray();

        $post->saved_count =
            ($post->saved_count ?? 0) + 1;

        $saved = true;
    }

    $post->save();

    return response()->json([
        'status' => true,
        'message' => $saved
            ? 'Post saved'
            : 'Post unsaved',

        'data' => [
            'saved' => $saved,
            'saved_count' => $post->saved_count,
            'saved_by' => $post->saved_by
        ]
    ]);
}
  public function getSavedPosts(Request $request)
{
    $userId = (string)$request->user()->_id;

    $posts = CommunityPost::whereRaw([
        'saved_by' => [
            '$in' => [$userId]
        ]
    ])
    ->orderBy('created_at', 'desc')
    ->paginate(20);

    foreach ($posts as $post) {

        $post->is_liked_by_user = in_array(
            $userId,
            $post->likes ?? []
        );

        $post->is_saved_by_user = in_array(
            $userId,
            $post->saved_by ?? []
        );

        $post->comments = CommunityComment::where(
            'post_id',
            (string)$post->_id
        )
        ->orderBy('created_at', 'asc')
        ->get();
    }

    return response()->json([
        'status' => true,
        'message' => 'Saved posts fetched successfully',
        'data' => $posts
    ]);
}

    // STORE COMMENT
    public function storeComment(Request $request)
    {
        $request->validate([
            'post_id' => 'required',
            'comment' => 'required|string'
        ]);

        $user = $request->user();

        $comment = CommunityComment::create([
            'post_id' => $request->input('post_id'),

            'user_id' => (string)$user->_id,

            'user_name' => $user->full_name ?? $user->name ?? 'User',

            'user_photo' => $user->profile_photo ?? null,

            'comment' => $request->input('comment'),

            'is_admin_reply' => false,

            'parent_id' => $request->input('parent_id'),

            'likes' => [],

            'likes_count' => 0,
        ]);

        // Update comment count
        $post = CommunityPost::find($request->input('post_id'));

        if ($post) {
            $post->comments_count = ($post->comments_count ?? 0) + 1;
            $post->save();
        }

        return response()->json([
            'status' => true,
            'message' => 'Comment added successfully',
            'data' => $comment
        ], 201);
    }

    // LIKE COMMENT
    public function toggleCommentLike($id, Request $request)
    {
        $comment = CommunityComment::findOrFail($id);

        $userId = (string)$request->user()->_id;

        if (in_array($userId, $comment->likes ?? [])) {

            $comment->likes = array_values(
                array_diff($comment->likes ?? [], [$userId])
            );

            $comment->likes_count = max(
                0,
                ($comment->likes_count ?? 0) - 1
            );

            $liked = false;

        } else {

            $comment->likes = array_merge(
                $comment->likes ?? [],
                [$userId]
            );

            $comment->likes_count = ($comment->likes_count ?? 0) + 1;

            $liked = true;
        }

        $comment->save();

        return response()->json([
            'status' => true,
            'message' => $liked ? 'Comment liked' : 'Comment unliked',
            'data' => [
                'liked' => $liked,
                'likes_count' => $comment->likes_count
            ]
        ]);
    }

    // DELETE COMMENT
    public function deleteComment($id)
    {
        $comment = CommunityComment::findOrFail($id);

        $post = CommunityPost::find($comment->post_id);

        if ($post) {
            $post->comments_count = max(
                0,
                ($post->comments_count ?? 0) - 1
            );

            $post->save();
        }

        $comment->delete();

        return response()->json([
            'status' => true,
            'message' => 'Comment deleted successfully'
        ]);
    }
    // MY POSTS
public function myPosts(Request $request)
{
    $userId = (string)$request->user()->_id;

    $posts = CommunityPost::where('user_id', $userId)
        ->orderBy('created_at', 'desc')
        ->paginate(20);

    foreach ($posts as $post) {

        $post->is_liked_by_user = in_array(
            $userId,
            $post->likes ?? []
        );

        $post->is_saved_by_user = in_array(
            $userId,
            $post->saved_by ?? []
        );
    }

    return response()->json([
        'status' => true,
        'message' => 'My posts fetched successfully',
        'data' => $posts
    ]);
}


// MY COMMENTS
public function myComments(Request $request)
{
    $userId = (string)$request->user()->_id;

    $comments = CommunityComment::where('user_id', $userId)
        ->orderBy('created_at', 'desc')
        ->paginate(20);

    return response()->json([
        'status' => true,
        'message' => 'My comments fetched successfully',
        'data' => $comments
    ]);
}
}
