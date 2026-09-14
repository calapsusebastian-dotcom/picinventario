# Bodega PIC — Arquitectura, flujo y roles

Sistema de inventario para el manejo de café desde que sale de la finca hasta que se despacha al cliente final, pasando por bodega, trilla y (opcionalmente) bodegas intermedias. Laravel 13 + Livewire 3 + MySQL.

## 1. El flujo del proceso

Cada remisión de café recorre dos flujos que avanzan en paralelo y se cruzan en un punto:

### A) El flujo de datos (una remisión, 5 etapas)

Cada remisión (`InventoryRecord`) tiene 5 secciones de información, todas sobre el mismo registro. La etapa **General** es la base — las otras 4 dependen de que General esté completa, pero no dependen entre sí (se pueden llenar en cualquier orden):

| Etapa | Qué registra | Quién la llena |
|---|---|---|
| **General** | Remisión, fecha, tulas, costal, ubicación, observación | Base del registro |
| **Envío** | Calidad enviada, kg enviados, análisis de envío (factor, humedad, broca, taza...) | Jorge · Evelyn · Natalia |
| **Recepción** | Kg recibidos, análisis de recepción (mismas variables que envío, para comparar) | Bodega · Calidades · Trilladora |
| **Destino** | Cliente, negocio, destino final, taza de destino | Comercial |
| **Imov** | Número de imov (identificador de movimiento) | Natalia · Bodega |

**Contra Entrega** no es una etapa más — es una comparación automática entre lo que dice **Envío** y lo que dice **Recepción** de la misma remisión (diferencia de kg, factor, humedad, broca, taza...), para detectar mermas o inconsistencias antes de seguir.

### B) El flujo físico (dónde está el café)

Una vez que una remisión tiene `kg_recibidos`, entra a **Bodega** y de ahí se mueve por el sistema:

```
                    ┌─────────────────────┐
                    │       BODEGA         │  ← punto de entrada (todas las remisiones con kg_recibidos)
                    └──────────┬───────────┘
                               │  se elige uno de estos 4 caminos:
          ┌────────────┬───────┴───────┬──────────────────┐
          ▼            ▼               ▼                  ▼
     ┌─────────┐  ┌──────────┐  ┌───────────────┐  ┌──────────────────┐
     │ TRILLA  │  │ DESPACHO │  │ BODEGA         │  │ BODEGA           │
     │         │  │ directo  │  │ ESPECIALES     │  │ ALMACAFE         │
     └────┬────┘  └────┬─────┘  └───────┬────────┘  └─────────┬────────┘
          │            │                │  (desde aquí también pueden
          │            │                │   salir hacia Trilla o Despacho)
          ▼            │                ├──────────────┬───────────────┐
     ┌─────────┐       │                ▼              ▼               │
     │Productos│       │           ┌─────────┐   ┌──────────┐          │
     │generados│       │           │ TRILLA  │   │ DESPACHO │          │
     └────┬────┘       │           └────┬────┘   └────┬─────┘         │
          ▼            ▼                ▼             ▼               ▼
     ┌───────────────────────────────────────────────────────────────────┐
     │                          DESPACHO                                  │
     │   (aquí se asigna la remisión de despacho, destino y factura —     │
     │    es el punto donde el café realmente "sale" del sistema)         │
     └───────────────────────────────────────────────────────────────────┘
```

- **Trilla**: convierte materia prima (remisiones) en productos terminados (`TrillaProducto`) — un lote de trilla puede usar varias remisiones y generar varios productos.
- **Despacho directo**: la materia prima sale tal cual, sin pasar por trilla.
- **Bodega Especiales / Bodega Almacafe**: bodegas intermedias — desde ahí una remisión puede seguir hacia Trilla o Despacho, igual que desde Bodega.
- **Despacho**: paso final, sea de un producto de trilla o de materia prima directa. Aquí se registra la remisión de despacho, destino y número de factura.

**Stock** e **Informes** son vistas de solo lectura sobre todo este flujo: Stock muestra cuánto queda de cada producto/calidad en cada punto; Informes muestra totales y tendencias por mes/cliente/producto.

## 2. Roles y permisos

El acceso se controla por el array `roles` de cada usuario (`App\Models\User`):

- **`admin`**: acceso a todo — Tablero, Informes, Contra Entrega, las 3 bodegas, Stock, Roles, Productos, Clientes, Ubicaciones, y las 5 etapas del flujo de datos.
- **Un rol por etapa** (`general`, `envio`, `recepcion`, `destino`, `imov`): acceso solo a esa pestaña del flujo de datos, para cualquier remisión.
- **`trilla`** / **`despacho`**: acceso al módulo de Trilla o Despacho respectivamente — son módulos aparte del flujo de datos principal.

Un usuario no-admin solo ve en el menú lateral las secciones para las que tiene rol asignado.

## 3. Acciones operativas

### Crear una remisión nueva

Solo un admin puede crear una remisión (botón "Nuevo registro" en el Tablero). Se llena por secciones — **General** primero y es obligatoria; el formulario no deja avanzar a Envío/Recepción/Destino/Imov hasta que General esté completa, pero esas 4 sí se pueden llenar en cualquier orden entre sí.

### Enviar remisiones desde una bodega

Al seleccionar una o más remisiones en Bodega, Bodega Especiales o Bodega Almacafe y darle "Enviar a trilla", el sistema pide primero el **número de remisión del envío físico** (el documento que acompaña el camión hacia la trilladora) antes de confirmar — es un dato aparte del número de remisión original de cada registro, y se guarda en `remision_envio_trilla`.

### Revertir acciones

- **Bodega Especiales / Bodega Almacafe → Bodega**: "Reversar a bodega" quita el flag correspondiente y la remisión vuelve a aparecer en Bodega normal.
- **Despacho pendiente → Bodega**: una remisión enviada directo a despacho pero sin remisión de despacho asignada todavía se puede reversar y vuelve a quedar disponible en Bodega.
- **Deshacer un despacho ya hecho**: tanto para un producto de trilla despachado como para materia prima despachada directo, se puede "revertir" — limpia la remisión de despacho, destino y factura, y el registro vuelve a la cola de pendientes de despacho.

### Gestión de catálogos (solo admin)

- **Usuarios y roles** (`/usuarios`): crear/editar/eliminar usuarios y asignarles uno o más roles (`admin`, `general`, `envio`, `recepcion`, `destino`, `imov`, `trilla`, `despacho`). Un admin no puede quitarse a sí mismo el rol de admin ni eliminar su propia cuenta.
- **Productos, Clientes, Ubicaciones**: catálogos simples (solo un nombre), con creación/edición/eliminación — alimentan los menús desplegables del resto del sistema.

## 4. Arquitectura técnica

- **Backend**: Laravel 13, cada módulo (Tablero, Bodega, Trilla, Despacho, etc.) es un componente Livewire 3 independiente con su propia clase en `app/Livewire/`.
- **Frontend**: Blade + el sistema de diseño compartido en `resources/css/inventory-board.css` (clase raíz `.pic-board`), sin build de JS aparte de Vite para compilar CSS.
- **Base de datos**: MySQL. La tabla central es `inventory_records` (una fila por remisión, con todos los campos de las 5 etapas más los flags de flujo físico: `enviado_a_trilla`, `enviado_a_despacho`, `enviado_a_bodega_especial`, `enviado_a_bodega_almacafe`, `remision_despacho`). `trillas` y `trilla_productos` cubren el módulo de Trilla, unidos a `inventory_records` por la tabla pivote `trilla_inventory_record` (cuánto kg de cada remisión usó cada lote).
- **Listados grandes**: cada página de tabla pushea sus filtros a SQL (`filteredQuery()` + `paginate()`), en vez de cargar todo a memoria — patrón repetido en Tablero, Bodega, Bodega Especial/Almacafe y Contra Entrega.
- **Exportes**: PDF del Tablero (formato corporativo, vía `barryvdh/laravel-dompdf`) y CSV de Contra Entrega.

## 5. Mapa de páginas

| Ruta | Página | Acceso |
|---|---|---|
| `/inventario` | Tablero (vista general de todas las remisiones) | admin |
| `/inventario/{etapa}` | General / Envío / Recepción / Destino / Imov | admin o el rol de esa etapa |
| `/bodega` | Bodega | admin |
| `/bodega-especial` | Bodega Especiales | admin |
| `/bodega-almacafe` | Bodega Almacafe | admin |
| `/trilla` | Trilla | admin o rol `trilla` |
| `/despacho` | Despacho | admin o rol `despacho` |
| `/contra-entrega` | Contra Entrega (envío vs recepción) | admin |
| `/stock` | Stock (productos trillados + materia prima) | admin |
| `/informes` | Informes (totales, por mes, por cliente, por producto) | admin |
| `/usuarios`, `/productos`, `/clientes`, `/ubicaciones` | Catálogos y administración | admin |

## 6. Servidor MCP

Bodega PIC expone sus datos vía un servidor MCP remoto (`POST /mcp`, protegido por token — `App\Http\Middleware\EnsureMcpToken`) para que un asistente de IA pueda consultar el inventario en lenguaje natural. **Todas las herramientas son de solo lectura** — ninguna modifica datos.

19 herramientas, organizadas por módulo (`app/Mcp/*.php`):

| Archivo | Herramientas |
|---|---|
| `InventarioTools.php` | `buscar_remision`, `saldo_bodega`, `resumen_kpis`, `listar_pendientes_despacho` |
| `BusquedaTools.php` | `listar_remisiones` (búsqueda con filtros) |
| `TrillaTools.php` | `listar_lotes_trilla`, `detalle_lote_trilla` |
| `DespachoTools.php` | `buscar_despacho` |
| `ContraEntregaTools.php` | `contra_entrega_remision`, `contra_entrega_listado`, `contra_entrega_resumen`, `contra_entrega_por_cliente`, `contra_entrega_por_ubicacion`, `contra_entrega_por_mes` |
| `StockTools.php` | `stock_productos_trillados`, `stock_materia_prima` |
| `InformesTools.php` | `informe_resumen`, `informe_desglose` |
| `CatalogoTools.php` | `listar_catalogos` |

Configuración clave en `.env` (producción): `MCP_AUTH_TOKEN`, `MCP_SESSION_DRIVER=file`, `MCP_HTTP_INTEGRATED_STATELESS=true` (necesario en hosting compartido — evita que conexiones largas agoten los procesos PHP disponibles).

## 7. Despliegue

Producción corre en Hostinger (`pedidoscoocentral.online`) sobre el mismo repo de GitHub (`calapsusebastian-dotcom/picinventario`, rama `main`). Flujo típico:

```bash
ssh usuario@pedidoscoocentral.online
cd ruta/al/proyecto
git pull origin main
composer install --no-dev --optimize-autoloader   # solo si cambiaron dependencias
php artisan migrate --force                        # solo si hay migración nueva (nunca elimina datos)
php artisan route:clear && php artisan route:cache  # solo si cambiaron rutas
php artisan mcp:discover                            # solo si cambiaron herramientas MCP
php artisan view:clear
```

Los assets de CSS/JS ya vienen compilados y committeados en `public/build/`, así que producción nunca necesita correr `npm run build`.
