@php
    $themePresets = [
        ['name' => 'SKUAD Digital', 'primary' => '#0f766e', 'secondary' => '#0f172a', 'accent' => '#f59e0b'],
        ['name' => 'Konten Kreator', 'primary' => '#7c3aed', 'secondary' => '#1e1b4b', 'accent' => '#f97316'],
        ['name' => 'Jurnalis Digital', 'primary' => '#1d4ed8', 'secondary' => '#0f172a', 'accent' => '#06b6d4'],
        ['name' => 'Affiliate UMKM', 'primary' => '#ea580c', 'secondary' => '#431407', 'accent' => '#22c55e'],
        ['name' => 'AI & Coding', 'primary' => '#0891b2', 'secondary' => '#164e63', 'accent' => '#a3e635'],
    ];
@endphp
<div class="program-theme-form" data-program-theme-form>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label" for="name">Nama program</label><input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $program->name ?? '') }}" placeholder="SKUAD" required data-program-theme-name>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label" for="slug">Slug</label><input class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug', $program->slug ?? '') }}" placeholder="skuad">@error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label" for="type">Tipe program</label><select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required data-program-theme-type>@foreach(['ekstrakurikuler' => 'Ekstrakurikuler', 'pelatihan' => 'Pelatihan', 'komunitas' => 'Komunitas', 'umkm' => 'UMKM/Mitra'] as $value => $label)<option value="{{ $value }}" @selected(old('type', $program->type ?? 'ekstrakurikuler') === $value)>{{ $label }}</option>@endforeach</select>@error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-2"><label class="form-label" for="primary_color">Utama</label><input class="form-control form-control-color w-100 @error('primary_color') is-invalid @enderror" id="primary_color" name="primary_color" type="color" value="{{ old('primary_color', $program->primary_color ?? '#0f766e') }}" required data-program-theme-primary>@error('primary_color')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-2"><label class="form-label" for="secondary_color">Sekunder</label><input class="form-control form-control-color w-100 @error('secondary_color') is-invalid @enderror" id="secondary_color" name="secondary_color" type="color" value="{{ old('secondary_color', $program->secondary_color ?? '#0f172a') }}" required data-program-theme-secondary>@error('secondary_color')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-2"><label class="form-label" for="accent_color">Aksen</label><input class="form-control form-control-color w-100 @error('accent_color') is-invalid @enderror" id="accent_color" name="accent_color" type="color" value="{{ old('accent_color', $program->accent_color ?? '#f59e0b') }}" required data-program-theme-accent>@error('accent_color')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label" for="logo">Logo program</label><input class="form-control @error('logo') is-invalid @enderror" id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp">@error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror @if(isset($program) && $program->logo_path)<small class="text-secondary d-block mt-1">Logo tersimpan: {{ basename($program->logo_path) }}</small>@endif</div>
                <div class="col-md-6"><label class="form-label" for="banner">Banner program</label><input class="form-control @error('banner') is-invalid @enderror" id="banner" name="banner" type="file" accept="image/png,image/jpeg,image/webp">@error('banner')<div class="invalid-feedback">{{ $message }}</div>@enderror @if(isset($program) && $program->banner_path)<small class="text-secondary d-block mt-1">Banner tersimpan: {{ basename($program->banner_path) }}</small>@endif</div>
                <div class="col-12"><label class="form-label" for="description">Deskripsi</label><textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" placeholder="Gambaran singkat program" data-program-theme-description>{{ old('description', $program->description ?? '') }}</textarea>@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><input type="hidden" name="is_active" value="0"><div class="form-check form-switch"><input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $program->is_active ?? true))><label class="form-check-label fw-semibold" for="is_active">Program aktif</label></div>@error('is_active')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            </div>
        </div>
        <aside class="col-lg-5">
            <div class="program-theme-preview" data-program-theme-preview>
                <div class="program-theme-preview-hero">
                    <span data-program-theme-preview-type>Ekstrakurikuler</span>
                    <h3 data-program-theme-preview-name>{{ old('name', $program->name ?? 'Nama Program') }}</h3>
                    <p data-program-theme-preview-description>{{ old('description', $program->description ?? 'Preview ini membantu memastikan karakter warna program terlihat premium dan teks tetap terbaca.') }}</p>
                    <button type="button">Contoh tombol</button>
                </div>
                <div class="program-theme-preview-stats">
                    <article><strong>∞</strong><small>Fleksibel</small></article>
                    <article><strong>A+</strong><small>Showcase</small></article>
                    <article><strong>95%</strong><small>Aktif</small></article>
                </div>
            </div>
            <div class="program-theme-presets" aria-label="Preset tema program">
                <p class="skuad-eyebrow mb-2">Preset cepat</p>
                @foreach($themePresets as $preset)
                    <button type="button" data-program-theme-preset data-primary="{{ $preset['primary'] }}" data-secondary="{{ $preset['secondary'] }}" data-accent="{{ $preset['accent'] }}">
                        <span><i style="background:{{ $preset['primary'] }}"></i><i style="background:{{ $preset['secondary'] }}"></i><i style="background:{{ $preset['accent'] }}"></i></span>
                        {{ $preset['name'] }}
                    </button>
                @endforeach
            </div>
            <p class="small text-secondary mt-3 mb-0">Tahap ini menyiapkan karakter visual per program. Nanti setelah context switcher aktif, dashboard akan membaca tema dari program/batch yang sedang dipilih.</p>
        </aside>
    </div>
</div>
<div class="d-flex justify-content-end gap-2 mt-4"><x-ui.button :href="route('super-admin.programs.index')" variant="ghost">Batal</x-ui.button><x-ui.button type="submit" icon="bi-check-lg">Simpan</x-ui.button></div>
