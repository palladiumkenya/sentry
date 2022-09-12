<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TriangulationExport implements FromArray, WithMultipleSheets
{
    /**
    * @return \Illuminate\Support\Collection
    */  
    protected $sheets;

    public function __construct(array $sheets)
    {
        $this->sheets = $sheets;
    }

    public function array(): array
    {
        return $this->sheets;
    }

    public function sheets(): array
    {
        $sheets = [
            new IndexPosTriangulation($this->sheets[0]),
            new RetentionVLTriangulation($this->sheets[1]),
            new RetentionVL1000Triangulation($this->sheets[2]),
        ];

        return $sheets;
    }
}
