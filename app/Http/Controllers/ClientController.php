<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        $search = request('search');

        $clients = Client::query()
            ->where('user_id', $user->id)
            ->when($search, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        $settings = $user?->setting;

        return view('clients.index', [
            'clients' => $clients,
            'settings' => $settings,
            'user' => $user,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('clients.create', [
            'client' => new Client(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = Auth::id();

        if (! $userId) {
            abort(403);
        }

        $data = $this->validatedData($request);
        $data['user_id'] = $userId;

        Client::create($data);

        return redirect()->route('clients.index')->with('status', 'Клиент успешно создан.');
    }

    public function show(Client $client): View
    {
        $this->authorizeClient($client);

        return view('clients.show', compact('client'));
    }

    public function edit(Client $client): View
    {
        $this->authorizeClient($client);

        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $this->authorizeClient($client);

        $data = $this->validatedData($request, $client);

        $client->update($data);

        return redirect()->route('clients.show', $client)->with('status', 'Данные клиента обновлены.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->authorizeClient($client);

        $client->delete();

        return redirect()->route('clients.index')->with('status', 'Клиент удален.');
    }

    protected function validatedData(Request $request, ?Client $client = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'birthday' => ['nullable', 'date'],
            'tags' => ['nullable', 'string'],
            'allergies' => ['nullable', 'string'],
            'preferences' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'last_visit_at' => ['nullable', 'date'],
            'loyalty_level' => ['nullable', 'string', 'max:255'],
        ]);

        $data['tags'] = $this->stringToArray($request->input('tags'));
        $data['allergies'] = $this->stringToArray($request->input('allergies'));
        $data['preferences'] = $this->stringToArray($request->input('preferences'));

        if ($request->filled('last_visit_at')) {
            $data['last_visit_at'] = $request->input('last_visit_at');
        } else {
            $data['last_visit_at'] = null;
        }

        foreach (['phone', 'email', 'loyalty_level', 'notes'] as $nullableField) {
            if (array_key_exists($nullableField, $data) && $data[$nullableField] === '') {
                $data[$nullableField] = null;
            }
        }

        if (empty($data['birthday'])) {
            $data['birthday'] = null;
        }

        return $data;
    }

    protected function stringToArray(?string $value): ?array
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return collect(preg_split('/[\n,]+/', $value))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    protected function authorizeClient(Client $client): void
    {
        $userId = Auth::id();

        if ($userId && $client->user_id !== $userId) {
            abort(403);
        }

        if (! $userId) {
            abort(403);
        }
    }
}
