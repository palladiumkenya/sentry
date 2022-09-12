<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithMapping;

class IndexPosTriangulation implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithMapping
{
    protected $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }
    public function map($row): array
    {
        return [
            $row->name,
            $row->code,
            $row->county,
            $row->subCounty,
            $row->agency,
            $row->partner,
            $row->metric_date,
            $row->dwh_metric_date,
            $row->EMRValue,
            $row->DWHValue,
            $row->Variance,
            $row->Percent_variance,
        ];
    }

    public function headings(): array
    {
        return [
            'Name',
            'Code',
            'County',
            'Subcounty',
            'Agency',
            'Partner',
            'EMR metric date',
            'DWH metric date',
            'EMR value',
            'DWH value',
            'Difference',
            '% Variance',
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'HTS INDEX POS';
    }
}
