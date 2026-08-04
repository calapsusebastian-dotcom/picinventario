<?php

namespace App\Livewire;

use App\Livewire\Forms\InventoryRecordForm;
use App\Models\InventoryRecord;
use App\Support\InventoryStages;
use Livewire\Component;

class InventoryStagePage extends Component
{
    public string $stage;

    public string $search = '';

    public bool $showDrawer = false;
    public ?int $editingId = null;

    public InventoryRecordForm $form;

    public function mount(string $stage): void
    {
        abort_unless(in_array($stage, InventoryStages::ORDER, true), 404);
        abort_unless(auth()->user()->canAccessStage($stage), 403);

        $this->stage = $stage;
    }

    public function openCreate(): void
    {
        $this->form->reset();
        $this->editingId = null;
        $this->showDrawer = true;
    }

    public function openEdit(int $id): void
    {
        $record = InventoryRecord::findOrFail($id);
        $this->form->setFromModel($record);
        $this->editingId = $id;
        $this->showDrawer = true;
    }

    public function closeDrawer(): void
    {
        $this->showDrawer = false;
    }

    public function save(): void
    {
        $data = $this->form->toDatabaseArray();

        if ($this->editingId) {
            InventoryRecord::findOrFail($this->editingId)->update($data);
        } else {
            InventoryRecord::create($data);
        }

        $this->showDrawer = false;
    }

    public function render()
    {
        $index = InventoryStages::index($this->stage);

        $allRecords = InventoryRecord::orderByDesc('fecha')->orderByDesc('id')->get();

        $pending = $allRecords->filter(function (InventoryRecord $record) use ($index) {
            $status = $record->stageStatus();

            $unlocked = $this->stage === 'general' || $status[0];
            $done = $status[$index];

            return $unlocked && ! $done;
        })->filter(function (InventoryRecord $record) {
            if ($this->search === '') {
                return true;
            }

            $haystack = mb_strtolower(implode(' ', [
                $record->remision, $record->cliente, $record->destino, $record->negocio, $record->calidad_enviada,
            ]));

            return str_contains($haystack, mb_strtolower($this->search));
        })->values();

        $completedCount = $allRecords->filter(fn (InventoryRecord $r) => $r->stageStatus()[$index])->count();

        return view('livewire.inventory-stage-page', [
            'records' => $pending,
            'pendingCount' => $pending->count(),
            'completedCount' => $completedCount,
            'stageLabel' => InventoryStages::label($this->stage),
            'stageRoleLabel' => InventoryStages::roleLabel($this->stage),
        ]);
    }
}
