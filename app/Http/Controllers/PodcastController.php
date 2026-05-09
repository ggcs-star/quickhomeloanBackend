<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Podcast;

class PodcastController extends Controller
{
    public function getPodcasts(Request $request)
    {
        $podcasts = Podcast::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        foreach ($podcasts as $podcast) {
            $podcast->thumbnail_url = $this->getThumbnailUrl($podcast);
            $podcast->embed_url = $this->getEmbedUrl($podcast->youtube_url);
        }

        return response()->json([
            'status' => true,
            'message' => 'Podcasts fetched successfully',
            'data' => $podcasts
        ]);
    }

    public function getPodcast($id, Request $request)
    {
        $podcast = Podcast::where('is_active', true)
            ->findOrFail($id);

        $podcast->thumbnail_url = $this->getThumbnailUrl($podcast);
        $podcast->embed_url = $this->getEmbedUrl($podcast->youtube_url);

        return response()->json([
            'status' => true,
            'message' => 'Podcast fetched successfully',
            'data' => $podcast
        ]);
    }

    public function getFeaturedPodcasts(Request $request)
    {
        $podcasts = Podcast::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        foreach ($podcasts as $podcast) {
            $podcast->thumbnail_url = $this->getThumbnailUrl($podcast);
            $podcast->embed_url = $this->getEmbedUrl($podcast->youtube_url);
        }

        return response()->json([
            'status' => true,
            'message' => 'Featured podcasts fetched successfully',
            'data' => $podcasts
        ]);
    }

    private function getThumbnailUrl($podcast)
    {

        if ($podcast->thumbnail && !empty($podcast->thumbnail)) {

            if (
                filter_var(
                    $podcast->thumbnail,
                    FILTER_VALIDATE_URL
                )
            ) {
                return $podcast->thumbnail;
            }
            return asset('storage/' . $podcast->thumbnail);
        }

        $videoId = $this->getYouTubeVideoId(
            $podcast->youtube_url
        );

        if ($videoId) {

            return "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg";
        }

        return null;
    }

    private function getEmbedUrl($youtubeUrl)
    {
        $videoId = $this->getYouTubeVideoId(
            $youtubeUrl
        );

        if ($videoId) {

            return "https://www.youtube.com/embed/{$videoId}?rel=0&modestbranding=1";
        }

        return null;
    }


    private function getYouTubeVideoId($url)
    {
        $patterns = [
            '/(?:youtube\.com\/watch\?v=)([a-zA-Z0-9_-]+)/',
            '/(?:youtu\.be\/)([a-zA-Z0-9_-]+)/',
            '/(?:youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/',
            '/(?:youtube\.com\/v\/)([a-zA-Z0-9_-]+)/'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}