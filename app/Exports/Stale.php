<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithMapping;

class Stale implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithMapping
{
    protected $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }
    public function map($row): array
    {
        return [
            $row->FacilityCode,
            $row->FacilityName,
            $row->SDP,
            $row->County,
            $row->avg_visits,
            $row->current_no_of_visits,
            $row->DateUploaded,
            $row->DateQueryExecuted,
            $row->percentage_of_avg_visits
        ];
    }

    public function headings(): array
    {
        return [
            'Facility Code',
            'Facility Name',
            'SDP',
            'County',
            'AVG Visits',
            'Current No of Visits',
            'Date Uploaded',
            'Date Query Executed',
            '% of AVG Visits'
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Stale Databases';
    }

}
