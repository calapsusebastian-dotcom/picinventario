<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

class RoleManager extends Component
{
    public const ROLE_LABELS = [
        'admin' => 'Administrador',
        'general' => 'General',
        'envio' => 'Envío',
        'recepcion' => 'Recepción',
        'destino' => 'Destino',
        'imov' => 'Imov',
        'trilla' => 'Trilla',
        'despacho' => 'Despacho',
    ];

    public string $search = '';

    public bool $showDrawer = false;
    public ?int $editingUserId = null;
    public ?int $confirmDeleteId = null;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    /** @var array<int, string> */
    public array $roles = ['general'];

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    public function openCreate(): void
    {
        $this->editingUserId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->roles = ['general'];
        $this->showDrawer = true;
    }

    public function openEdit(int $id): void
    {
        $user = User::findOrFail($id);

        $this->editingUserId = $id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->roles = $user->roles;
        $this->showDrawer = true;
    }

    public function closeDrawer(): void
    {
        $this->showDrawer = false;
    }

    public function save(): void
    {
        // Guard against an admin locking themselves out.
        if ($this->editingUserId === auth()->id() && ! in_array('admin', $this->roles, true)) {
            $this->addError('roles', 'No puedes quitarte el rol de administrador a ti mismo.');

            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingUserId)],
            'password' => [$this->editingUserId ? 'nullable' : 'required', 'string', 'min:8'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in(array_keys(self::ROLE_LABELS))],
        ]);

        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'roles' => $validated['roles'],
            ]);

            if ($validated['password'] !== '' && $validated['password'] !== null) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();
        } else {
            User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'roles' => $validated['roles'],
                'email_verified_at' => now(),
            ]);
        }

        $this->showDrawer = false;
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    public function delete(int $id): void
    {
        if ($id === auth()->id()) {
            $this->confirmDeleteId = null;

            return;
        }

        User::destroy($id);
        $this->confirmDeleteId = null;
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('name')
            ->get();

        return view('livewire.role-manager', [
            'users' => $users,
        ]);
    }
}
