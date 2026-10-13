<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientPhoto;
use App\Support\ImageCompressor;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PatientPhotoController extends Controller
{
    public function store(Request $r, Patient $patient)
    {
        $r->validate([
            'photos' => 'required|array|max:10',
            'photos.*' => 'file|image|mimes:jpg,jpeg,png,webp|max:15360',
            'title' => 'nullable|string|max:150',
        ], [
            'photos.*.max' => __('Rasm hajmi 15 MB dan oshmasligi kerak.'),
            'photos.*.mimes' => __('Faqat JPG, PNG yoki WebP rasm yuklash mumkin.'),
            'photos.*.image' => __('Faqat rasm yuklash mumkin.'),
        ]);

        $saved = 0;
        $before = 0;
        $after = 0;
        foreach ($r->file('photos') as $file) {
            $res = ImageCompressor::process($file);
            $path = 'patient_photos/'.Tenant::id().'/'.$patient->id.'/'.Str::uuid().'.'.$res['ext'];
            Storage::disk('local')->put($path, $res['data']);
            PatientPhoto::create([
                'patient_id' => $patient->id, 'path' => $path, 'title' => $r->input('title') ?: null,
                'width' => $res['width'], 'height' => $res['height'], 'original_size' => $res['original'], 'size' => $res['size'],
                'uploaded_by' => $r->user()->id,
            ]);
            $saved++;
            $before += $res['original'];
            $after += $res['size'];
        }
        $note = $before > $after ? ' '.__('Hajm :a MB dan :b MB gacha kamaytirildi.', ['a' => round($before / 1048576, 2), 'b' => round($after / 1048576, 2)]) : '';

        return back()->with('success', __('Rasmlar yuklandi: :n', ['n' => $saved]).$note);
    }

    public function show(PatientPhoto $photo)
    {
        abort_unless(Storage::disk('local')->exists($photo->path), 404);

        return response()->file(Storage::disk('local')->path($photo->path), ['Cache-Control' => 'private, max-age=86400']);
    }

    public function destroy(PatientPhoto $photo)
    {
        Storage::disk('local')->delete($photo->path);
        $photo->delete();

        return back()->with('success', __("Rasm o'chirildi."));
    }
}
