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
            $post->is_liked_by_user = in_array((string)$request->user()->_id, $post->likes ?? []);
            $post->is_saved_by_user = in_array((string)$request->user()->_id, $post->saved_by ?? []);
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
        
        $post->is_liked_by_user = in_array((string)$request->user()->_id, $post->likes ?? []);
        $post->is_saved_by_user = in_array((string)$request->user()->_id, $post->saved_by ?? []);
        
        return response()->json([
            'status' => true,
            'message' => 'Post fetched successfully',
            'data' => [
                'post' => $post,
                'comments' => $comments
            ]
        ]);
    }

    public function createPost(Request $request)
    {
        $request->validate([
            'content' => 'required|string'
        ]);

        $user = $request->user();
        $contentText = $request->input('content');

        $post = new CommunityPost();
        $post->user_id = (string)$user->_id;
        $post->user_name = $user->full_name ?? 'User';
        $post->user_photo = $user->profile_photo ?? null;
        $post->content = $contentText;
        $post->likes = [];
        $post->likes_count = 0;
        $post->shares = [];
        $post->shares_count = 0;
        $post->saved_by = [];
        $post->saved_count = 0;
        $post->comments_count = 0;
        $post->save();
        
        return response()->json([
            'status' => true,
            'message' => 'Post created successfully',
            'data' => $post
        ], 201);
    }

    public function toggleLike($id, Request $request)
    {
        $post = CommunityPost::findOrFail($id);
        $userId = (string)$request->user()->_id;
        
        if (in_array($userId, $post->likes ?? [])) {
            $post->likes = array_values(array_diff($post->likes ?? [], [$userId]));
            $post->likes_count = ($post->likes_count ?? 0) - 1;
            $liked = false;
        } else {
            $post->likes = array_merge($post->likes ?? [], [$userId]);
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

    public function sharePost($id, Request $request)
    {
        $post = CommunityPost::findOrFail($id);
        $userId = (string)$request->user()->_id;
        
        if (!in_array($userId, $post->shares ?? [])) {
            $post->shares = array_merge($post->shares ?? [], [$userId]);
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

    public function toggleSave($id, Request $request)
    {
        $post = CommunityPost::findOrFail($id);
        $userId = (string)$request->user()->_id;
        
        if (in_array($userId, $post->saved_by ?? [])) {
            $post->saved_by = array_values(array_diff($post->saved_by ?? [], [$userId]));
            $post->saved_count = ($post->saved_count ?? 0) - 1;
            $saved = false;
        } else {
            $post->saved_by = array_merge($post->saved_by ?? [], [$userId]);
            $post->saved_count = ($post->saved_count ?? 0) + 1;
            $saved = true;
        }
        $post->save();
        
        return response()->json([
            'status' => true,
            'message' => $saved ? 'Post saved' : 'Post unsaved',
            'data' => [
                'saved' => $saved,
                'saved_count' => $post->saved_count
            ]
        ]);
    }

    public function getSavedPosts(Request $request)
    {
        $userId = (string)$request->user()->_id;
        
        $posts = CommunityPost::where('saved_by', 'all', [$userId])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return response()->json([
            'status' => true,
            'message' => 'Saved posts fetched successfully',
            'data' => $posts
        ]);
    }

    public function storeComment(Request $request)
    {
        $request->validate([
            'post_id' => 'required',
            'comment' => 'required|string'
        ]);
        
        $user = $request->user();
        $commentText = $request->comment;

        $comment = new CommunityComment();
        $comment->post_id = $request->post_id;
        $comment->user_id = (string)$user->_id;
        $comment->user_name = $user->full_name ?? 'User';
        $comment->user_photo = $user->profile_photo ?? null;
        $comment->comment = $commentText;
        $comment->is_admin_reply = false;
        $comment->parent_id = $request->parent_id ?? null;
        $comment->likes = [];
        $comment->likes_count = 0;
        $comment->save();
        
        CommunityPost::where('_id', $request->post_id)->increment('comments_count');
        
        return response()->json([
            'status' => true,
            'message' => 'Comment added successfully',
            'data' => $comment
        ], 201);
    }

    public function toggleCommentLike($id, Request $request)
    {
        $comment = CommunityComment::findOrFail($id);
        $userId = (string)$request->user()->_id;
        
        if (in_array($userId, $comment->likes ?? [])) {
            $comment->likes = array_values(array_diff($comment->likes ?? [], [$userId]));
            $comment->likes_count = ($comment->likes_count ?? 0) - 1;
            $liked = false;
        } else {
            $comment->likes = array_merge($comment->likes ?? [], [$userId]);
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

    public function deleteComment($id, Request $request)
    {
        $comment = CommunityComment::findOrFail($id);
        CommunityPost::where('_id', $comment->post_id)->decrement('comments_count');
        $comment->delete();
        
        return response()->json([
            'status' => true,
            'message' => 'Comment deleted successfully'
        ]);
    }
}