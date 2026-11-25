<x-layouts.app :title="$material->exists ? 'Editar '.$material->name : 'Nuevo insumo'">
    <form method="POST" action="{{ $material->exists ? route('admin.stock.update', $material) : route('admin.stock.store') }}" class="card max-w-3xl space-y-6">
        @csrf
        @if ($material->exists) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="label" for="name">Nombre</label>
                <input class="input" id="name" name="name" value="{{ old('name', $material->name) }}" placeholder="Ej.: Melamina Blanco 18 mm" required>
                @error('name')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="material_category_id">Categoría</label>
                <select class="input" id="material_category_id" name="material_category_id" required>
                    @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(old('material_category_id', $material->material_category_id) == $c->id)>{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="label" for="supplier_id">Proveedor</label>
                <select class="input" id="supplier_id" name="supplier_id">
                    <option value="">Sin proveedor</option>
                    @foreach ($suppliers as $s)<option value="{{ $s->id }}" @selected(old('supplier_id', $material->supplier_id) == $s->id)>{{ $s->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="label" for="sku">Código interno</label>
                <input class="input" id="sku" name="sku" value="{{ old('sku', $material->sku) }}">
                @error('sku')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="unit">Unidad</label>
                <select class="input" id="unit" name="unit">
                    @foreach ($units as $u)<option value="{{ $u->value }}" @selected(old('unit', $material->unit?->value) === $u->value)>{{ $u->label() }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="label" for="price">Precio por unidad ($)</label>
                <input class="input" id="price" type="number" step="0.01" min="0" name="price" value="{{ old('price', $material->current_price ?? 0) }}" required>
                @error('price')<p class="error">{{ $message }}</p>@enderror
                @if ($material->exists)<p class="mt-1 text-xs text-stone-500">Si lo cambiás, queda guardado en el historial de precios.</p>@endif
            </div>
            <div>
                <label class="label" for="min_stock">Stock mínimo</label>
                <input class="input" id="min_stock" type="number" step="0.001" min="0" name="min_stock" value="{{ old('min_stock', $material->min_stock ?? 0) }}" required>
            </div>
            @unless ($material->exists)
                <div>
                    <label class="label" for="initial_stock">Stock inicial</label>
                    <input class="input" id="initial_stock" type="number" step="0.001" min="0" name="initial_stock" value="{{ old('initial_stock', 0) }}">
                </div>
            @endunless
        </div>

        <fieldset class="rounded-md border border-stone-200 p-4">
            <legend class="px-1 text-sm font-semibold">Placa (para el despiece y corteOPT)</legend>
            <p class="mb-3 text-xs text-stone-500">Completalo solo si es una placa. Material, espesor y color son obligatorios para exportar a corteOPT.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="corteopt_material">Material corteOPT</label>
                    <select class="input" id="corteopt_material" name="corteopt_material">
                        <option value="">No es una placa</option>
                        @foreach ($corteoptMaterials as $cm)<option value="{{ $cm->value }}" @selected(old('corteopt_material', $material->corteopt_material?->value) === $cm->value)>{{ $cm->label() }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="thickness_mm">Espesor (mm)</label>
                    <input class="input" id="thickness_mm" type="number" step="0.1" name="thickness_mm" value="{{ old('thickness_mm', $material->thickness_mm) }}">
                    @error('thickness_mm')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="board_color_id">Color</label>
                    <select class="input" id="board_color_id" name="board_color_id">
                        <option value="">—</option>
                        @foreach ($colors as $c)<option value="{{ $c->id }}" @selected(old('board_color_id', $material->board_color_id) == $c->id)>{{ $c->name }}</option>@endforeach
                    </select>
                    @error('board_color_id')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="characteristic">Característica</label>
                    <input class="input" id="characteristic" name="characteristic" value="{{ old('characteristic', $material->characteristic) }}" placeholder="hidrófugo, 2 caras, texturado">
                </div>
                <div>
                    <label class="label" for="brand">Marca</label>
                    <input class="input" id="brand" name="brand" value="{{ old('brand', $material->brand) }}" placeholder="Faplac, Egger">
                </div>
                <div>
                    <label class="label" for="cutlist_alias">Nombre del material en SketchUp</label>
                    <input class="input" id="cutlist_alias" name="cutlist_alias" value="{{ old('cutlist_alias', $material->cutlist_alias) }}">
                </div>
                <div>
                    <label class="label" for="sheet_length_mm">Largo de placa (mm)</label>
                    <input class="input" id="sheet_length_mm" type="number" name="sheet_length_mm" value="{{ old('sheet_length_mm', $material->sheet_length_mm) }}" placeholder="2750">
                </div>
                <div>
                    <label class="label" for="sheet_width_mm">Ancho de placa (mm)</label>
                    <input class="input" id="sheet_width_mm" type="number" name="sheet_width_mm" value="{{ old('sheet_width_mm', $material->sheet_width_mm) }}" placeholder="1830">
                </div>
            </div>
            <label class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" name="has_grain" value="1" @checked(old('has_grain', $material->has_grain))> Tiene veta</label>
        </fieldset>

        <label class="flex items-center gap-2 text-sm"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $material->is_active ?? true))> Activo</label>
        <div class="flex gap-3"><button class="btn-primary">Guardar</button><a href="{{ $material->exists ? route('admin.stock.show', $material) : route('admin.stock.index') }}" class="btn">Cancelar</a></div>
    </form>
</x-layouts.app>
