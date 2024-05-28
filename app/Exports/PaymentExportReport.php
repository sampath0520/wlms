<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PaymentExportReport implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $increment = 1;
        $modifiedData = $this->data->map(function ($item) use (&$increment) {
            return [
                $increment++, // Increment the value and add it as the ID
                $item->first_name . ' ' . $item->last_name,
                $item->course,
                $item->price,
                $item->payment_date->format('d-m-Y'),
            ];
        });

        return $modifiedData;
    }

    public function headings(): array
    {
        return [
            '#',
            'Student Name',
            'Course Name',
            'Amount',
            'Paid Date',
        ];
    }
}
