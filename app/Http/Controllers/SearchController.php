<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Reel;
use App\Models\Podcast;
use App\Models\Calculator;
use App\Models\Event;
use App\Models\CommunityPost;
use App\Models\Lender;
use App\Models\EducationModule;
use App\Models\EducationContent;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('q');
        $type = $request->input('type', 'all');
        
        if (strlen($query) < 2) {
            return response()->json([
                'status' => false,
                'message' => 'Please enter at least 2 characters',
                'data' => []
            ]);
        }
        
        $results = [];

        if ($type == 'all' || $type == 'courses') {
            $courses = Course::where('title', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'type' => 'course',
                        'title' => $item->title,
                        'description' => $item->description,
                        'url' => "/courses/{$item->id}/modules",
                        'icon' => 'BookOpen'
                    ];
                });
            $results = array_merge($results, $courses->toArray());
        }
        
       if ($type == 'all' || $type == 'reels') {
            $reels = Reel::where('title', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'type' => 'reel',
                        'title' => $item->title,
                        'description' => $item->description,
                        'url' => "/reels",
                        'icon' => 'Video'
                    ];
                });
            $results = array_merge($results, $reels->toArray());
        }

        if ($type == 'all' || $type == 'podcasts') {
            $podcasts = Podcast::where('title', 'like', "%{$query}%")
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'type' => 'podcast',
                        'title' => $item->title,
                        'description' => $item->description ?? '',
                        'url' => "/podcasts",
                        'icon' => 'Mic'
                    ];
                });
            $results = array_merge($results, $podcasts->toArray());
        }
        
        if ($type == 'all' || $type == 'community') {
            $posts = CommunityPost::where('content', 'like', "%{$query}%")
                ->orWhere('user_name', 'like', "%{$query}%")
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'type' => 'post',
                        'title' => $item->user_name,
                        'description' => substr($item->content, 0, 100),
                        'url' => "/community",
                        'icon' => 'MessageSquare'
                    ];
                });
            $results = array_merge($results, $posts->toArray());
        }
        
        if ($type == 'all' || $type == 'calculators') {
            $calculators = Calculator::where('name', 'like', "%{$query}%")
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'type' => 'calculator',
                        'title' => $item->name,
                        'description' => $item->description ?? 'Financial calculator',
                        'url' => "tools/calculators",
                        'icon' => 'Calculator'
                    ];
                });
            $results = array_merge($results, $calculators->toArray());
        }
        
        if ($type == 'all' || $type == 'events') {
            $events = Event::where('title', 'like', "%{$query}%")
                ->where('user_id', auth()->id())
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'type' => 'event',
                        'title' => $item->title,
                        'description' => $item->description ?? 'Event reminder',
                        'url' => "/tools/calendar",
                        'icon' => 'Calendar'
                    ];
                });
            $results = array_merge($results, $events->toArray());
        }
        
        if ($type == 'all' || $type == 'lenders') {
            $lenders = Lender::where('name', 'like', "%{$query}%")
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'type' => 'lender',
                        'title' => $item->name,
                        'description' => $item->description ?? 'Lender partner',
                        'url' => "/",
                        'icon' => 'Building'
                    ];
                });
            $results = array_merge($results, $lenders->toArray());
        }
        
        if ($type == 'all' || $type == 'modules') {
            $modules = EducationModule::where('title', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'type' => 'module',
                        'title' => $item->title,
                        'description' => $item->description,
                        'url' => "/modules/{$item->id}/audio",
                        'icon' => 'Folder'
                    ];
                });
            $results = array_merge($results, $modules->toArray());
        }
        
        if ($type == 'all' || $type == 'contents') {
            $contents = EducationContent::where('title', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->get()
                ->map(function($item) {
                    $url = $item->type == 'audio' ? "/modules/{$item->module_id}/audio" : "/modules/{$item->module_id}/video";
                    return [
                        'id' => $item->id,
                        'type' => $item->type,
                        'title' => $item->title,
                        'description' => $item->description,
                        'url' => $url,
                        'icon' => $item->type == 'audio' ? 'Headphones' : 'Play'
                    ];
                });
            $results = array_merge($results, $contents->toArray());
        }
        
        usort($results, function($a, $b) use ($query) {
            $aScore = stripos($a['title'], $query) !== false ? 1 : 0;
            $bScore = stripos($b['title'], $query) !== false ? 1 : 0;
            return $bScore - $aScore;
        });
        
        return response()->json([
            'status' => true,
            'data' => [
                'query' => $query,
                'total' => count($results),
                'results' => $results
            ]
        ]);
    }
    
    public function suggestions(Request $request)
    {
        $query = $request->input('q');
        
        if (strlen($query) < 1) {
            return response()->json(['data' => []]);
        }
        
        $suggestions = [];
        
        $courses = Course::where('title', 'like', "%{$query}%")
            ->limit(2)
            ->get(['title']);
        foreach ($courses as $course) {
            $suggestions[] = $course->title;
        }
        
        $reels = Reel::where('title', 'like', "%{$query}%")
            ->limit(2)
            ->get(['title']);
        foreach ($reels as $reel) {
            $suggestions[] = $reel->title;
        }
        
        $podcasts = Podcast::where('title', 'like', "%{$query}%")
            ->limit(2)
            ->get(['title']);
        foreach ($podcasts as $podcast) {
            $suggestions[] = $podcast->title;
        }
        
        $calculators = Calculator::where('name', 'like', "%{$query}%")
            ->limit(2)
            ->get(['name']);
        foreach ($calculators as $calc) {
            $suggestions[] = $calc->name;
        }
        
        $modules = EducationModule::where('title', 'like', "%{$query}%")
            ->limit(2)
            ->get(['title']);
        foreach ($modules as $module) {
            $suggestions[] = $module->title;
        }
        
        $suggestions = array_unique($suggestions);
        
        return response()->json([
            'status' => true,
            'data' => array_slice($suggestions, 0, 10)
        ]);
    }
}