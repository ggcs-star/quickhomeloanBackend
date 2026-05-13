<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use Carbon\Carbon;

class EventController extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'start_datetime' => 'required|date|after:now',  
            'end_datetime' => 'nullable|date|after_or_equal:start_datetime',
            'type' => 'nullable|in:event,task',
            'reminder_time' => 'nullable|date|after_or_equal:now',  
            'repeat_type' => 'nullable|in:none,daily,weekly,monthly,yearly',
        ]);

        $event = Event::create([
            'user_id' => auth()->id(),
            'type' => $request->type ?? 'event',
            'title' => $request->title,
            'description' => $request->description,
            'start_datetime' => Carbon::parse($request->start_datetime),
            'end_datetime' => $request->end_datetime
                ? Carbon::parse($request->end_datetime)
                : null,
            'is_all_day' => $request->is_all_day ?? false,
            'reminder_time' => $request->reminder_time
                ? Carbon::parse($request->reminder_time)
                : null,
            'repeat_type' => $request->repeat_type ?? 'none',
            'is_completed' => false,
            'is_notified' => false,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Created successfully',
            'data' => $event
        ]);
    }


    public function index(Request $request)
    {
        $query = Event::where('user_id', auth()->id());


        if ($request->type) {
            $query->where('type', $request->type);
        }


        if ($request->date) {
            $date = Carbon::parse($request->date);

            $query->where(function ($q) use ($date) {
                $q->whereDate('start_datetime', '<=', $date)
                    ->whereDate('end_datetime', '>=', $date);
            });
        }

        $events = $query->orderBy('start_datetime', 'asc')->get();

        $events->transform(function ($event) {
            if ($event->start_datetime) {
                $event->start_datetime = $event->start_datetime->setTimezone('Asia/Kolkata')->toDateTimeString();
            }
            if ($event->end_datetime) {
                $event->end_datetime = $event->end_datetime->setTimezone('Asia/Kolkata')->toDateTimeString();
            }
            if ($event->reminder_time) {
                $event->reminder_time = $event->reminder_time->setTimezone('Asia/Kolkata')->toDateTimeString();
            }
            return $event;
        });

        return response()->json([
            'status' => true,
            'data' => $events
        ]);
    }

    public function show($id)
    {
        $event = Event::where('user_id', auth()->id())->find($id);

        if (!$event) {
            return response()->json([
                'status' => false,
                'message' => 'Not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $event
        ]);
    }

    // 📌 Update
    public function update(Request $request, $id)
    {
        $event = Event::where('user_id', auth()->id())->find($id);

        if (!$event) {
            return response()->json([
                'status' => false,
                'message' => 'Not found'
            ], 404);
        }

        $event->update([
            'type' => $request->type ?? $event->type,
            'title' => $request->title ?? $event->title,
            'description' => $request->description ?? $event->description,
            'start_datetime' => $request->start_datetime
                ? Carbon::parse($request->start_datetime)
                : $event->start_datetime,
            'end_datetime' => $request->end_datetime
                ? Carbon::parse($request->end_datetime)
                : $event->end_datetime,
            'is_all_day' => $request->is_all_day ?? $event->is_all_day,
            'reminder_time' => $request->reminder_time
                ? Carbon::parse($request->reminder_time)
                : $event->reminder_time,
            'repeat_type' => $request->repeat_type ?? $event->repeat_type,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Updated successfully',
            'data' => $event
        ]);
    }

    // 📌 Delete
    public function destroy($id)
    {
        $event = Event::where('user_id', auth()->id())->find($id);

        if (!$event) {
            return response()->json([
                'status' => false,
                'message' => 'Not found'
            ], 404);
        }

        $event->delete();

        return response()->json([
            'status' => true,
            'message' => 'Deleted successfully'
        ]);
    }

    // 📌 Mark Task Complete
    public function markComplete($id)
    {
        $event = Event::where('user_id', auth()->id())->find($id);

        if (!$event || $event->type !== 'task') {
            return response()->json([
                'status' => false,
                'message' => 'Task not found'
            ], 404);
        }

        $event->update(['is_completed' => true]);

        return response()->json([
            'status' => true,
            'message' => 'Task completed',
            'data' => $event
        ]);
    }
}