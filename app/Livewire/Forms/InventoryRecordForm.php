<?php

namespace App\Livewire\Forms;

use App\Models\InventoryRecord;
use Livewire\Form;

class InventoryRecordForm extends Form
{
    // General
    public ?string $anio = '2026';
    public ?string $mes = 'Agosto';
    public ?string $fecha = '';
    public ?string $remision = '';
    public ?string $tulas = '';
    public ?string $costal = '';

    // Envio
    public ?string $calidad_enviada = '';
    public ?string $kg_enviados = '';
    public ?string $analisis_enviado_por = 'Jorge';
    public ?string $as_env = '';
    public ?string $pas_env = '';
    public ?string $pg_env = '';
    public ?string $broca_env = '';
    public ?string $humedad_env = '';
    public ?string $factor_env = '';
    public ?string $taza_env = '';
    public ?string $puntaje_taza_env = '';

    // Recepcion
    public ?string $analisis_recibido_por = 'Bodega';
    public ?string $kg_recibidos = '';
    public ?string $as_rec = '';
    public ?string $pas_rec = '';
    public ?string $pg_rec = '';
    public ?string $broca_rec = '';
    public ?string $humedad_rec = '';
    public ?string $factor_rec = '';
    public ?string $taza_rec = '';
    public ?string $puntaje_taza_rec = '';

    // Destino
    public ?string $destino = '';
    public ?string $cliente = '';
    public ?string $negocio = '';
    public string $estatus = 'En bodega';
    public ?string $existencia = '';

    // Imov
    public ?string $imov = '';

    public function rules(): array
    {
        return [
            'anio' => ['nullable', 'integer'],
            'mes' => ['nullable', 'string'],
            'fecha' => ['nullable', 'date'],
            'remision' => ['nullable', 'string', 'max:255'],
            'tulas' => ['nullable', 'integer'],
            'costal' => ['nullable', 'integer'],

            'calidad_enviada' => ['nullable', 'string', 'max:255'],
            'kg_enviados' => ['nullable', 'numeric'],
            'analisis_enviado_por' => ['nullable', 'string', 'max:255'],
            'as_env' => ['nullable', 'numeric'],
            'pas_env' => ['nullable', 'numeric'],
            'pg_env' => ['nullable', 'numeric'],
            'broca_env' => ['nullable', 'numeric'],
            'humedad_env' => ['nullable', 'numeric'],
            'factor_env' => ['nullable', 'numeric'],
            'taza_env' => ['nullable', 'string', 'max:255'],
            'puntaje_taza_env' => ['nullable', 'numeric'],

            'analisis_recibido_por' => ['nullable', 'string', 'max:255'],
            'kg_recibidos' => ['nullable', 'numeric'],
            'as_rec' => ['nullable', 'numeric'],
            'pas_rec' => ['nullable', 'numeric'],
            'pg_rec' => ['nullable', 'numeric'],
            'broca_rec' => ['nullable', 'numeric'],
            'humedad_rec' => ['nullable', 'numeric'],
            'factor_rec' => ['nullable', 'numeric'],
            'taza_rec' => ['nullable', 'string', 'max:255'],
            'puntaje_taza_rec' => ['nullable', 'numeric'],

            'destino' => ['nullable', 'string', 'max:255'],
            'cliente' => ['nullable', 'string', 'max:255'],
            'negocio' => ['nullable', 'string', 'max:255'],
            'estatus' => ['required', 'string', 'in:En bodega,Despachado,En tránsito,Reservado'],
            'existencia' => ['nullable', 'numeric'],

            'imov' => ['nullable', 'integer'],
        ];
    }

    public function setFromModel(InventoryRecord $record): void
    {
        $this->anio = (string) $record->anio;
        $this->mes = $record->mes;
        $this->fecha = $record->fecha?->format('Y-m-d');
        $this->remision = $record->remision;
        $this->tulas = (string) $record->tulas;
        $this->costal = (string) $record->costal;

        $this->calidad_enviada = $record->calidad_enviada;
        $this->kg_enviados = (string) $record->kg_enviados;
        $this->analisis_enviado_por = $record->analisis_enviado_por;
        $this->as_env = (string) $record->as_env;
        $this->pas_env = (string) $record->pas_env;
        $this->pg_env = (string) $record->pg_env;
        $this->broca_env = (string) $record->broca_env;
        $this->humedad_env = (string) $record->humedad_env;
        $this->factor_env = (string) $record->factor_env;
        $this->taza_env = $record->taza_env;
        $this->puntaje_taza_env = (string) $record->puntaje_taza_env;

        $this->analisis_recibido_por = $record->analisis_recibido_por;
        $this->kg_recibidos = (string) $record->kg_recibidos;
        $this->as_rec = (string) $record->as_rec;
        $this->pas_rec = (string) $record->pas_rec;
        $this->pg_rec = (string) $record->pg_rec;
        $this->broca_rec = (string) $record->broca_rec;
        $this->humedad_rec = (string) $record->humedad_rec;
        $this->factor_rec = (string) $record->factor_rec;
        $this->taza_rec = $record->taza_rec;
        $this->puntaje_taza_rec = (string) $record->puntaje_taza_rec;

        $this->destino = $record->destino;
        $this->cliente = $record->cliente;
        $this->negocio = $record->negocio;
        $this->estatus = $record->estatus;
        $this->existencia = (string) $record->existencia;

        $this->imov = (string) $record->imov;
    }

    /**
     * Validate and return data ready to persist. Empty-string inputs (every
     * field in this form is a plain text/number input, never truly absent)
     * are normalized to null so numeric/date columns don't choke on ''.
     */
    public function toDatabaseArray(): array
    {
        return collect($this->validate())
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all();
    }

    /**
     * Which of the 5 workflow stages (general/envio/recepcion/destino/imov) are complete.
     */
    public function stageStatus(): array
    {
        return [
            (bool) ($this->remision && $this->fecha),
            (bool) $this->kg_enviados,
            (bool) $this->kg_recibidos,
            (bool) $this->cliente,
            (bool) $this->imov,
        ];
    }
}
