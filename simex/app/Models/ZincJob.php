<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ZincJob extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = ['inputs' => 'array'];

    public function company()
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    public function rfq()
    {
        return $this->belongsTo(Rfq::class);
    }

    /** Warnings against the production limits confirmed for the line (rack envelope, barrel load). */
    public function warnings(): array
    {
        $i = $this->inputs ?? [];
        $out = [];
        $limits = config('simex.zinc_limits');
        $dims = array_filter([(float) ($i['length_mm'] ?? 0), (float) ($i['width_mm'] ?? 0), (float) ($i['height_mm'] ?? 0)]);
        if (($i['loading'] ?? '') === 'dispozitiv' && count($dims) === 3) {
            rsort($dims);
            $max = [$limits['rack']['length_mm'], $limits['rack']['width_mm'], $limits['rack']['depth_mm']];
            foreach ($dims as $k => $d) {
                if ($d > $max[$k]) {
                    $out[] = "Piesa depășește gabaritul confirmat pentru dispozitive ({$max[0]} × {$max[1]} × {$max[2]} mm).";
                    break;
                }
            }
        }
        if (($i['loading'] ?? '') === 'tambur') {
            if ((float) ($i['piece_weight_kg'] ?? 0) > $limits['barrel']['load_kg']) {
                $out[] = "O piesă cântărește mai mult decât încărcarea maximă a unui tambur ({$limits['barrel']['load_kg']} kg).";
            }
        }

        return $out;
    }
}
