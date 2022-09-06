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
            $row->{'MFL Code'},
            $row->FacilityName,
            $row->CTPartner,
            $row->CTAgency,
            $row->DateReceived,
            $row->ExpectedPatients,
            $row->Received
        ];
    }

    public function headings(): array
    {
        return [
            'MFL Code',
            'Facility',
            'Agency',
            'Partner',
            'Date Received',
            'Expected Patients',
            'Received'
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
