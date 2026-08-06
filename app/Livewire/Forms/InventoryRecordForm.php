<?php

namespace App\Livewire\Forms;

use App\Models\InventoryRecord;
use Livewire\Form;

class InventoryRecordForm extends Form
{
    /**
     * Which fields belong to each of the 5 workflow stages, so a stage page
     * (or the admin's active tab) can require all of its own fields without
     * forcing fields from stages other roles haven't reached yet.
     */
    public const STAGE_FIELDS = [
        'general' => ['anio', 'mes', 'fecha', 'remision', 'tulas', 'costal'],
        'envio' => [
            'calidad_enviada', 'kg_enviados', 'analisis_enviado_por',
            'as_env', 'pas_env', 'pg_env', 'broca_env', 'humedad_env', 'factor_env', 'taza_env', 'puntaje_taza_env',
        ],
        'recepcion' => [
            'analisis_recibido_por', 'kg_recibidos',
            'as_rec', 'pas_rec', 'pg_rec', 'broca_rec', 'humedad_rec', 'factor_rec', 'taza_rec', 'puntaje_taza_rec',
        ],
        'destino' => ['destino', 'cliente', 'negocio', 'estatus', 'existencia'],
        'imov' => ['imov'],
    ];

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
            'as_env' => ['nullable', 'numeric', 'max:300'],
            'pas_env' => ['nullable', 'numeric', 'max:300'],
            'pg_env' => ['nullable', 'numeric', 'max:300'],
            'broca_env' => ['nullable', 'numeric', 'max:300'],
            'humedad_env' => ['nullable', 'numeric', 'max:100'],
            'factor_env' => ['nullable', 'numeric', 'max:9999.99'],
            'taza_env' => ['nullable', 'string', 'max:255'],
            'puntaje_taza_env' => ['nullable', 'numeric', 'max:100'],

            'analisis_recibido_por' => ['nullable', 'string', 'max:255'],
            'kg_recibidos' => ['nullable', 'numeric'],
            'as_rec' => ['nullable', 'numeric', 'max:300'],
            'pas_rec' => ['nullable', 'numeric', 'max:300'],
            'pg_rec' => ['nullable', 'numeric', 'max:300'],
            'broca_rec' => ['nullable', 'numeric', 'max:300'],
            'humedad_rec' => ['nullable', 'numeric', 'max:100'],
            'factor_rec' => ['nullable', 'numeric', 'max:9999.99'],
            'taza_rec' => ['nullable', 'string', 'max:255'],
            'puntaje_taza_rec' => ['nullable', 'numeric', 'max:100'],

            'destino' => ['nullable', 'string', 'max:255'],
            'cliente' => ['nullable', 'string', 'max:255'],
            'negocio' => ['nullable', 'string', 'max:255'],
            'estatus' => ['required', 'string', 'in:En bodega,Despachado,En tránsito,Reservado'],
            'existencia' => ['nullable', 'numeric'],

            'imov' => ['nullable', 'integer'],
        ];
    }

    /**
     * The base rules(), but every field belonging to $stage is upgraded
     * from nullable to required — so completing that stage can't be saved
     * half-filled, without touching fields other stages own.
     */
    public function rulesForStage(string $stage): array
    {
        $rules = $this->rules();

        foreach (self::STAGE_FIELDS[$stage] ?? [] as $field) {
            $rules[$field] = collect($rules[$field] ?? ['nullable'])
                ->reject(fn ($rule) => $rule === 'nullable')
                ->prepend('required')
                ->values()
                ->all();
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'required' => 'Este campo es obligatorio.',
            'numeric' => 'Debe ser un número.',
            'integer' => 'Debe ser un número entero.',
            'date' => 'Debe ser una fecha válida.',
            'in' => 'Selecciona un valor válido.',
            'max' => 'No puede ser mayor a :max.',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'anio' => 'año', 'mes' => 'mes', 'fecha' => 'fecha', 'remision' => 'remisión',
            'tulas' => 'N° tulas', 'costal' => 'N° costal',

            'calidad_enviada' => 'calidad enviada', 'kg_enviados' => 'kg enviados', 'analisis_enviado_por' => 'análisis enviado por',
            'as_env' => 'almendra sana', 'pas_env' => 'pasilla', 'pg_env' => 'primer grupo', 'broca_env' => 'broca',
            'humedad_env' => 'humedad', 'factor_env' => 'factor', 'taza_env' => 'taza', 'puntaje_taza_env' => 'puntaje de taza',

            'analisis_recibido_por' => 'análisis recibido por', 'kg_recibidos' => 'kg recibidos',
            'as_rec' => 'almendra sana', 'pas_rec' => 'pasilla', 'pg_rec' => 'primer grupo', 'broca_rec' => 'broca',
            'humedad_rec' => 'humedad', 'factor_rec' => 'factor', 'taza_rec' => 'taza', 'puntaje_taza_rec' => 'puntaje de taza',

            'destino' => 'destino', 'cliente' => 'cliente', 'negocio' => 'negocio', 'estatus' => 'estatus', 'existencia' => 'existencia',

            'imov' => 'imov',
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
