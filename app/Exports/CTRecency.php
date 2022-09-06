<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithMapping;

class CTRecency implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithMapping
{
    protected $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }
    public function map($row): array
    {
        return [
            $row->recency,
            $row->docket,
            $row->year,
            $row->month,
            $row->county,
            $row->subcounty,
            $row->agency,
            $row->partner
        ];
    }

    public function headings(): array
    {
        return [
            'Recency',
            'Docket',
            'Year',
            'Month',
            'County',
            'Subcounty',
            'Agency',
            'Partner'
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Ct Recency';
    }
}
