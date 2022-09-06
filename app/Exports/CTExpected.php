<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithMapping;

class CTExpected implements  FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithMapping
{
    protected $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }
    public function map($row): array
    {
        return [
            $row->docket,
            $row->county,
            $row->subcounty,
            $row->agency,
            $row->partner,
            $row->expected
        ];
    }

    public function headings(): array
    {
        return [
            'Docket',
            'County',
            'Subcounty',
            'Agency',
            'Partner',
            'Expected'
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Ct Expected';
    }
}
