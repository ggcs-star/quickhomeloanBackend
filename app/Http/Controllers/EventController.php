<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use Carbon\Carbon;

class EventController extends Controller
{
    // 📌 Create Event / Task
    public function store(Request $request)
    {
        $request->validate([
            'title'          => 'required|string|max:255',
            'event_date'     => 'required|date',
            'type'           => 'nullable|in:event,task',
            'start_time'     => 'nullable|date',
            'end_time'       => 'nullable|date|after_or_equal:start_time',
            'reminder_time'  => 'nullable|date',
            'repeat_type'    => 'nullable|in:none,daily,weekly,monthly,yearly',
        ]);

        $event = Event::create([
            'user_id'       => auth()->id(),
            'type'          => $request->type ?? 'event',
            'title'         => $request->title,
            'description'   => $request->description,
            'event_date'    => Carbon::parse($request->event_date),
            'start_time'    => $request->start_time ? Carbon::parse($request->start_time) : null,
            'end_time'      => $request->end_time ? Carbon::parse($request->end_time) : null,
            'is_all_day'    => $request->is_all_day ?? false,
            'reminder_time' => $request->reminder_time ? Carbon::parse($request->reminder_time) : null,
            'repeat_type'   => $request->repeat_type ?? 'none',
            'is_completed'  => false,
            'is_notified'   => false,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Created successfully',
            'data'    => $event
        ]);
    }

    // 📌 List Events (with filter)
    public function index(Request $request)
    {
        $query = Event::where('user_id', auth()->id());

        // filter by type
        if ($request->type) {
            $query->where('type', $request->type);
        }

        // filter by date
        if ($request->date) {
            $query->whereDate('event_date', Carbon::parse($request->date));
        }

        $events = $query->orderBy('event_date', 'asc')->get();

        return response()->json([
            'status' => true,
            'data'   => $events
        ]);
    }

    // 📌 Show Single
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
            'data'   => $event
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
            'type'          => $request->type ?? $event->type,
            'title'         => $request->title ?? $event->title,
            'description'   => $request->description ?? $event->description,
            'event_date'    => $request->event_date ? Carbon::parse($request->event_date) : $event->event_date,
            'start_time'    => $request->start_time ? Carbon::parse($request->start_time) : $event->start_time,
            'end_time'      => $request->end_time ? Carbon::parse($request->end_time) : $event->end_time,
            'is_all_day'    => $request->is_all_day ?? $event->is_all_day,
            'reminder_time' => $request->reminder_time ? Carbon::parse($request->reminder_time) : $event->reminder_time,
            'repeat_type'   => $request->repeat_type ?? $event->repeat_type,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Updated successfully',
            'data'    => $event
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