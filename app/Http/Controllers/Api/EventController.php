<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index()
    {
        $query = Event::query();
        $relations = ['user','attendees','attendees.user'];

        foreach ($relations as $relation){
            $query->when(
                $this->shouldIncludeRelation($relation),
                fn($q) => $q -> with($relation)
            );
        }

        return EventResource::collection(
            $query->latest()->paginate());
    }

    protected function shouldIncludeRelation(string $relation): bool
    {
        $include = request()->query('include');

        if (!$include){
            return false;
        }

        $relations = array_map('trim', explode(',', $include));

        return in_array($relation,$relations);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time'
        ]);

        $event = Event::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'user_id' => 1,
        ]);

        return new EventResource($event);
    }

    public function show(Event $event)
    {
        $event->load('user','attendees');
        return new EventResource($event);
    }

    public function update(Request $request, String $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time'
        ]);

        $event = Event::find($id);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $event->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
        ]);

        return new EventResource($event);
    }

    public function destroy(string $id)
    {
        $event =  Event::find($id);

        if (!$event) {
            return response()->json(['message' => 'Event not Found'], 400);
        }

        $event->delete();

        return response()->json(['message' => 'Event Successfully Deleted'], 200);
    }
}
