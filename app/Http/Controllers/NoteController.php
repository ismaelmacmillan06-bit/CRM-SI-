<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    const COLORS = ['amber', 'red', 'blue', 'green', 'purple', 'gray'];

    public function index()
    {
        $user = auth()->user();
        $notes = $user->hasRole('admin')
            ? Note::with('user')->orderByDesc('updated_at')->get()
            : Note::where('user_id', $user->id)->orderByDesc('updated_at')->get();

        return view('notes.index', compact('notes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'   => 'nullable|string|max:120',
            'content' => 'required|string|max:5000',
            'color'   => 'nullable|string|in:' . implode(',', self::COLORS),
        ]);
        $data['user_id'] = auth()->id();
        $data['color']   = $data['color'] ?? 'amber';

        Note::create($data);

        return back()->with('success', 'Nota guardada.');
    }

    public function update(Request $request, Note $note)
    {
        $this->authorizeNote($note);

        $data = $request->validate([
            'title'   => 'nullable|string|max:120',
            'content' => 'required|string|max:5000',
            'color'   => 'nullable|string|in:' . implode(',', self::COLORS),
        ]);
        $data['color'] = $data['color'] ?? $note->color;

        $note->update($data);

        return back()->with('success', 'Nota actualizada.');
    }

    public function destroy(Note $note)
    {
        $this->authorizeNote($note);

        $note->delete();

        return back()->with('success', 'Nota eliminada.');
    }

    private function authorizeNote(Note $note): void
    {
        abort_unless(auth()->user()->hasRole('admin') || $note->user_id === auth()->id(), 403);
    }
}
