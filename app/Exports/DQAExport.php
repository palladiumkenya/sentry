<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DQAExport implements FromArray, WithMultipleSheets
{
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
            new Stale($this->sheets[0]),
            new Incomplete($this->sheets[1]),
            new HTSRecency($this->sheets[2]),
            new CTRecency($this->sheets[3]),
            new CTExpected($this->sheets[4]),
            new HTSExpected($this->sheets[5]),
            // new ReportLeadsExport($this->sheets['leads']),
            // new ReportVideoExport($this->sheets['video'])
        ];

        return $sheets;
    }
}
